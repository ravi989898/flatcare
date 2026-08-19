<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SocietyDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('society')->user();
        $society = request()->attributes->get('society');

        $roles = $user->roles()->pluck('display_name', 'name');
        $permissionCount = $user->allPermissions()->count();

        return view('society.dashboard', compact('user', 'society', 'roles', 'permissionCount'));
    }
}
