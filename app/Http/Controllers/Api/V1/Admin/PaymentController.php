<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Tenant\MaintenanceBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Society admin in the mobile app: who has paid and who still owes, flat
 * by flat. Bills are picked per billing run (their title, e.g. "October
 * 2026 Maintenance" - one per flat), so the admin sees one month at a
 * time. Society Admin, or a role granted "Payment Status" under App Permission
 * (app.menu middleware in routes/api.php).
 */
class PaymentController extends ApiController
{
    /** Billing runs to choose from, newest first. */
    public function periods(): JsonResponse
    {
        $periods = MaintenanceBill::query()
            ->selectRaw('title, max(due_date) as due_date, count(*) as bills')
            ->groupBy('title')
            ->orderByDesc('due_date')
            ->limit(36)
            ->get()
            ->map(fn ($row) => ['title' => $row->title, 'due_date' => $row->due_date, 'bills' => (int) $row->bills]);

        return $this->ok($periods);
    }

    /**
     * ?title= one billing run (default: the newest), ?status=pending|paid,
     * ?search= flat number. Pending bills first, then by flat.
     */
    public function index(Request $request): JsonResponse
    {
        $title = $request->string('title')->trim()->value()
            ?: MaintenanceBill::query()->orderByDesc('due_date')->value('title');

        $bills = MaintenanceBill::with(['flat.block', 'payments', 'flat.residents' => fn ($query) => $query->active()->with('user')])
            ->when($title, fn ($query) => $query->where('title', $title))
            ->get();

        $summary = [
            'total_billed' => round((float) $bills->sum('amount'), 2),
            'total_collected' => round((float) $bills->sum('paid_amount'), 2),
            'total_pending' => round((float) $bills->sum('balance'), 2),
            'paid_count' => $bills->where('status', 'paid')->count(),
            'pending_count' => $bills->where('status', '!=', 'paid')->count(),
        ];

        $status = $request->string('status')->trim()->value();
        $search = mb_strtolower($request->string('search')->trim()->value());

        $items = $bills
            ->when($status === 'paid', fn ($all) => $all->where('status', 'paid'))
            ->when($status === 'pending', fn ($all) => $all->where('status', '!=', 'paid'))
            ->when($search !== '', fn ($all) => $all->filter(fn (MaintenanceBill $bill) => str_contains(mb_strtolower($bill->flat?->display_label ?? ''), $search)))
            ->sortBy([
                fn ($a, $b) => ($a->status === 'paid') <=> ($b->status === 'paid'),
                fn ($a, $b) => strnatcmp($a->flat?->display_label ?? '', $b->flat?->display_label ?? ''),
            ])
            ->values()
            ->map(function (MaintenanceBill $bill) {
                $lastPayment = $bill->payments->sortByDesc('payment_date')->first();

                return [
                    'bill_id' => $bill->id,
                    'title' => $bill->title,
                    'flat_number' => $bill->flat?->flat_number,
                    'flat_label' => $bill->flat?->display_label,
                    'resident_name' => $bill->flat?->residents->first()?->user?->name,
                    'amount' => (float) $bill->amount,
                    'paid' => (float) $bill->paid_amount,
                    'balance' => (float) $bill->balance,
                    'status' => $bill->status,
                    'due_date' => $bill->due_date?->toDateString(),
                    'paid_on' => $lastPayment?->payment_date?->toDateString(),
                    'payment_method' => $lastPayment?->payment_method,
                ];
            });

        return $this->ok([
            'title' => $title,
            'summary' => $summary,
            'items' => $items,
        ]);
    }
}
