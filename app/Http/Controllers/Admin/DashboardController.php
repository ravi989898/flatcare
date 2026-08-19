<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];

        $recentUsers = User::latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }
}
