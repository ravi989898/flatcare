<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant\MaintenanceBill;
use App\Support\ListQuery;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Search / block / status filter, sort and paging for the bill lists
 * (Payments and Extra Charges). A bill's status depends on its payments
 * and late fee (MaintenanceBill::getStatusAttribute), so this filters the
 * loaded collection rather than the query.
 */
trait ListsBills
{
    /**
     * @param  Collection<int, MaintenanceBill>  $bills  with flat.block and payments loaded
     * @param  Closure(MaintenanceBill): float  $amount  the bill's billed amount
     * @return array{0: LengthAwarePaginator, 1: array<string, float|int>, 2: Collection<string, int>}
     */
    private function listBills(Collection $bills, Request $request, Closure $amount): array
    {
        if ($term = ListQuery::search($request)) {
            $needle = mb_strtolower($term);
            $bills = $bills->filter(fn (MaintenanceBill $bill) => str_contains(mb_strtolower(implode(' ', [
                $bill->title, $bill->feeType?->name, $bill->flat?->flat_number, $bill->flat?->block?->name,
            ])), $needle));
        }

        if ($blockId = $request->integer('block_id')) {
            $bills = $bills->filter(fn (MaintenanceBill $bill) => (int) $bill->flat?->block_id === $blockId);
        }

        if ($flatId = $request->integer('flat_id')) {
            $bills = $bills->where('flat_id', $flatId);
        }

        // Tab counts ignore the status filter itself, so every tab shows
        // how many it would list.
        $statusCounts = $bills->countBy('status');

        if (in_array($status = $request->string('status')->value(), MaintenanceBill::STATUSES, true)) {
            $bills = $bills->filter(fn (MaintenanceBill $bill) => $bill->status === $status);
        }

        $summary = [
            'total_due' => $bills->sum($amount),
            'total_collected' => $bills->sum('paid_amount'),
            'overdue_count' => $bills->where('status', 'overdue')->count(),
        ];

        $bills = ListQuery::sortCollection($bills, $request, [
            'due' => fn (MaintenanceBill $bill) => $bill->due_date->timestamp,
            'title' => fn (MaintenanceBill $bill) => $bill->feeType?->name ?? $bill->title,
            'flat' => fn (MaintenanceBill $bill) => $bill->flat?->flat_number,
            'amount' => $amount,
            'balance' => 'balance',
        ], 'due');

        return [ListQuery::paginateCollection($bills, $request, ListQuery::perPage($request, 25)), $summary, $statusCounts];
    }
}
