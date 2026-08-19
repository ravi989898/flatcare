<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Handle the signed verification link from the emailed notification.
     * The 'signed' route middleware already rejects a tampered/expired URL
     * before this method ever runs.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('home')->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        RateLimiter::attempt(
            'verify-resend:'.$request->user()->id,
            $perMinute = 3,
            function () use ($request) {
                $request->user()->sendEmailVerificationNotification();
            }
        );

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
