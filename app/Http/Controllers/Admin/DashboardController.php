<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected PlatformStatsService $stats) {}

    public function index(): View
    {
        $visibleWidgets = $this->stats->visibleWidgetKeys(auth()->user()?->role);

        return view('admin.dashboard', [
            'stats' => $this->stats->summary(),
            'visibleWidgets' => $visibleWidgets,
            'revenue' => in_array('revenue_chart', $visibleWidgets, true) ? $this->stats->monthlyRevenue() : [],
        ]);
    }
}
