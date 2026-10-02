<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Society;
use App\Models\SuperAdmin;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Razorpay\Api\Api;
use Throwable;

/**
 * Super Admin: a society's own Razorpay account, which its residents' bill
 * payments go into (see App\Services\RazorpayService). Keys are checked
 * against Razorpay before they are saved, so a typo can't break payments.
 * The secret is stored encrypted and never shown again - leaving the field
 * blank keeps the saved one.
 */
class SocietyPaymentGatewayController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function edit(int $id): View
    {
        $society = Society::findOrFail($id);

        return view('admin.societies.payment_gateway', compact('society'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);

        $data = $request->validate([
            'razorpay_key_id' => ['required', 'string', 'max:64', 'regex:/^rzp_(test|live)_[A-Za-z0-9]+$/'],
            'razorpay_key_secret' => [$society->razorpay_key_secret ? 'nullable' : 'required', 'string', 'max:255'],
        ], [
            'razorpay_key_id.regex' => 'The Key ID should look like rzp_test_XXXXXXXX or rzp_live_XXXXXXXX.',
        ]);

        $keyId = trim($data['razorpay_key_id']);
        $secret = filled($data['razorpay_key_secret'] ?? null) ? trim($data['razorpay_key_secret']) : $society->razorpay_key_secret;

        // A wrong key or secret would only show up when a resident tries to
        // pay - ask Razorpay now instead.
        try {
            (new Api($keyId, $secret))->order->all(['count' => 1]);
        } catch (Throwable $e) {
            return back()->withInput($request->except('razorpay_key_secret'))->withErrors([
                'razorpay_key_secret' => 'Razorpay did not accept this Key ID and Secret: '.$e->getMessage(),
            ]);
        }

        $oldKeyId = $society->razorpay_key_id;
        $society->razorpay_key_id = $keyId;
        $society->razorpay_key_secret = $secret;
        $society->save();

        $this->tenantService->clearTenantCache($society->id);

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            'society.razorpay_updated',
            'society_management',
            Society::class,
            $society->id,
            ['razorpay_key_id' => $oldKeyId],
            ['razorpay_key_id' => $keyId],
        );

        return back()->with('success', $society->razorpayTestMode()
            ? 'Razorpay test keys saved. Residents can now make test payments (no real money).'
            : 'Razorpay live keys saved. Residents can now pay their bills online.');
    }

    /** Turns online payment off for the society. */
    public function destroy(int $id): RedirectResponse
    {
        $society = Society::findOrFail($id);
        $oldKeyId = $society->razorpay_key_id;

        $society->razorpay_key_id = null;
        $society->razorpay_key_secret = null;
        $society->save();

        $this->tenantService->clearTenantCache($society->id);

        AuditLog::log(
            $this->currentSuperAdmin(),
            $society,
            'society.razorpay_removed',
            'society_management',
            Society::class,
            $society->id,
            ['razorpay_key_id' => $oldKeyId],
            null,
        );

        return back()
            ->with('success', 'Razorpay keys removed. Residents of this society can no longer pay online.');
    }

    private function currentSuperAdmin(): ?SuperAdmin
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->first() : null;
    }
}
