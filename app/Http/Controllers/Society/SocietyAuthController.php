<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocietyLoginRequest;
use App\Models\Society;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SocietyAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('society.auth.login');
    }

    public function login(SocietyLoginRequest $request): RedirectResponse
    {
        // Validation, tenant resolution, rate limiting and the actual
        // Auth::guard('society')->attempt() all live inside the form
        // request so this action stays a thin orchestrator.
        $society = $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('tenant_society_id', $society->id);

        $user = Auth::guard('society')->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('society.dashboard'));
    }

    public function logout(Request $request, TenantService $tenantService): RedirectResponse
    {
        // The 'society' connection has to point at this user's tenant
        // database before the guard can (potentially) write a fresh
        // remember-token during logout — but this route runs before
        // SetSocietyContext, so re-resolve it here defensively.
        $societyId = $request->session()->get('tenant_society_id');
        $society = $societyId ? Society::find($societyId) : null;

        if ($society) {
            $tenantService->setTenant($society);
        }

        Auth::guard('society')->logout();

        $request->session()->forget('tenant_society_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('society.login');
    }
}
