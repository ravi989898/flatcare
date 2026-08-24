<?php

namespace App\Http\Controllers;

use App\Models\TrialInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public "Start free trial" form on the landing page. This does not create
 * a login — it queues a lead for Super Admin to review under
 * Admin > Inquiries and follow up with directly.
 */
class TrialInquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'society_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        TrialInquiry::create($validated);

        return back()->with('trial_inquiry_sent', true);
    }
}
