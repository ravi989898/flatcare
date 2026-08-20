<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DashboardWidget;
use App\Models\RoleDefinition;
use App\Models\Society;
use App\Models\SocietyDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PlatformStatsService - Aggregates the numbers shown on the Super Admin
 * dashboard.
 *
 * Cross-tenant counts (blocks, flats, users, per-role user counts) require
 * connecting to every provisioned society's own database in turn. This is
 * the same connection-switching path SocietyAdminController already uses
 * (TenantService::switchConnection) - centralized here so future screens
 * (and the future API layer) reuse it instead of re-implementing the loop.
 */
class PlatformStatsService
{
    public function __construct(protected TenantService $tenantService) {}

    /**
     * Build the full dashboard summary, cached briefly so the dashboard
     * stays fast as the number of societies grows.
     */
    public function summary(): array
    {
        return Cache::remember('platform.stats', now()->addMinutes(15), function () {
            return array_merge(
                $this->societyCounts(),
                $this->tenantCounts(),
                [
                    'active_maintenance_configs' => 0, // no maintenance_configs table yet
                    'pending_society_setup' => $this->pendingSocietySetupCount(),
                ]
            );
        });
    }

    /**
     * Total maintenance-fee payments collected across every society, summed
     * per month, for the Revenue chart. Computed in its own tenant loop
     * (separate from summary()) since it's opted into independently via
     * Dashboard Widgets and returns a different shape.
     */
    public function monthlyRevenue(int $months = 6): array
    {
        return Cache::remember("platform.revenue.{$months}", now()->addMinutes(15), function () use ($months) {
            $buckets = collect(range(0, $months - 1))
                ->map(fn ($i) => now()->subMonths($months - 1 - $i)->format('Y-m'))
                ->mapWithKeys(fn ($ym) => [$ym => 0.0]);

            $societyIds = SocietyDatabase::where('status', 'active')->pluck('society_id');
            $since = now()->subMonths($months - 1)->startOfMonth()->toDateString();

            foreach ($societyIds as $societyId) {
                try {
                    $this->tenantService->switchConnection($societyId);
                } catch (\Throwable $e) {
                    continue;
                }

                $rows = DB::connection('society')->table('payments')
                    ->where('payment_date', '>=', $since)
                    ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
                    ->groupBy('ym')
                    ->pluck('total', 'ym');

                foreach ($rows as $ym => $total) {
                    if ($buckets->has($ym)) {
                        $buckets[$ym] = $buckets[$ym] + (float) $total;
                    }
                }
            }

            return $buckets
                ->map(fn ($total, $ym) => [
                    'label' => Carbon::createFromFormat('Y-m', $ym)->format('M Y'),
                    'total' => round($total, 2),
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Dashboard widget keys the given role is allowed to see (Settings ->
     * Dashboard Widgets). Falls back to showing everything when the role
     * isn't in the catalog or hasn't been configured yet, so an empty
     * catalog can never silently blank the whole dashboard.
     */
    public function visibleWidgetKeys(?string $roleName): array
    {
        $role = $roleName ? RoleDefinition::where('name', $roleName)->first() : null;

        if (!$role || !$role->dashboardWidgets()->exists()) {
            return DashboardWidget::pluck('key')->all();
        }

        return $role->dashboardWidgets()->wherePivot('is_visible', true)->pluck('key')->all();
    }

    public function recentActivities(int $limit = 10)
    {
        return AuditLog::with(['superAdmin', 'society'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function recentSocietyRegistrations(int $limit = 5)
    {
        return Society::latest()->take($limit)->get();
    }

    protected function societyCounts(): array
    {
        return [
            'total_societies' => Society::count(),
            'active_societies' => Society::active()->count(),
            'inactive_societies' => Society::where('status', 'inactive')->count(),
            'expired_societies' => Society::where('status', 'expired')
                ->orWhere(fn ($q) => $q->expired())
                ->count(),
        ];
    }

    protected function pendingSocietySetupCount(): int
    {
        return SocietyDatabase::where('status', '!=', 'active')->count();
    }

    /**
     * Sum blocks/flats/users/role-counts across every provisioned society's
     * own database.
     */
    protected function tenantCounts(): array
    {
        $totals = [
            'total_blocks' => 0,
            'total_flats' => 0,
            'total_registered_users' => 0,
            'total_society_admins' => 0,
            'total_committee_members' => 0,
            'total_security_users' => 0,
        ];

        $societyIds = SocietyDatabase::where('status', 'active')->pluck('society_id');

        foreach ($societyIds as $societyId) {
            try {
                $this->tenantService->switchConnection($societyId);
            } catch (\Throwable $e) {
                continue; // skip a society whose tenant DB is unreachable
            }

            $connection = DB::connection('society');

            $totals['total_blocks'] += $connection->table('blocks')->count();
            $totals['total_flats'] += $connection->table('flats')->count();
            $totals['total_registered_users'] += $connection->table('users')->count();

            $roleCounts = $connection->table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->whereIn('roles.name', ['admin', 'committee_member', 'security'])
                ->selectRaw('roles.name, count(*) as aggregate')
                ->groupBy('roles.name')
                ->pluck('aggregate', 'name');

            $totals['total_society_admins'] += $roleCounts->get('admin', 0);
            $totals['total_committee_members'] += $roleCounts->get('committee_member', 0);
            $totals['total_security_users'] += $roleCounts->get('security', 0);
        }

        return $totals;
    }
}
