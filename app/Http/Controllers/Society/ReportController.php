<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\MaintenanceBill;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Payment Report: which maintenance bills are paid vs. still outstanding,
     * filterable by status and due-date range. Mirrors PaymentController::index's
     * approach of loading bills with their payments and filtering in-memory,
     * since status/balance are computed attributes (see MaintenanceBill),
     * not stored columns a query could filter on directly.
     */
    public function payments(Request $request): View
    {
        $bills = $this->filteredBills($request);

        $summary = [
            'total_billed' => $bills->sum(fn ($bill) => $bill->totalAmount()),
            'total_collected' => $bills->sum('paid_amount'),
            'total_pending' => $bills->sum('balance'),
        ];

        return view('society.reports.payments', [
            'bills' => ListQuery::paginateCollection($bills, $request, ListQuery::perPage($request, 25)),
            'summary' => $summary,
        ]);
    }

    /**
     * CSV export of the same filtered report (opens directly in Excel).
     * Streamed rather than built in memory so a large bill history doesn't
     * have to fit in one string before the browser starts downloading.
     */
    public function exportPayments(Request $request): StreamedResponse
    {
        $bills = $this->filteredBills($request);

        $filename = 'payment-report-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($bills) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bill', 'Block', 'Flat', 'Amount', 'Paid', 'Balance', 'Due Date', 'Status']);

            foreach ($bills as $bill) {
                fputcsv($handle, [
                    $bill->title,
                    $bill->flat?->block?->name ?? '',
                    $bill->flat?->display_label ?? '',
                    number_format($bill->totalAmount(), 2, '.', ''),
                    number_format($bill->paid_amount, 2, '.', ''),
                    number_format($bill->balance, 2, '.', ''),
                    $bill->due_date->format('d M Y'),
                    ucfirst(str_replace('_', ' ', $bill->status)),
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return Collection<int, MaintenanceBill>
     */
    private function filteredBills(Request $request): Collection
    {
        $bills = MaintenanceBill::maintenanceOnly()->with(['flat.block', 'payments'])->get();

        if ($term = ListQuery::search($request)) {
            $needle = mb_strtolower($term);
            $bills = $bills->filter(fn ($bill) => str_contains(
                mb_strtolower($bill->title.' '.$bill->flat?->flat_number.' '.$bill->flat?->block?->name),
                $needle,
            ))->values();
        }

        if ($status = $request->string('status')->trim()->value()) {
            $bills = $bills->filter(fn ($bill) => $bill->status === $status)->values();
        }

        if ($from = $request->string('from')->trim()->value()) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $bills = $bills->filter(fn ($bill) => $bill->due_date->gte($fromDate))->values();
        }

        if ($to = $request->string('to')->trim()->value()) {
            $toDate = Carbon::parse($to)->endOfDay();
            $bills = $bills->filter(fn ($bill) => $bill->due_date->lte($toDate))->values();
        }

        return ListQuery::sortCollection($bills, $request, [
            'due' => fn ($bill) => $bill->due_date->timestamp,
            'title' => 'title',
            'flat' => fn ($bill) => $bill->flat?->flat_number,
            'amount' => fn ($bill) => $bill->totalAmount(),
            'balance' => 'balance',
        ], 'due');
    }
}
