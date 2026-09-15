<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrialInquiryRequest;
use App\Models\TrialInquiry;
use Illuminate\Http\RedirectResponse;

/**
 * Public "Start free trial" form on the landing page. This does not create
 * a login — it queues a lead for Super Admin to review under
 * Admin > Inquiries and follow up with directly.
 */
class TrialInquiryController extends Controller
{
    public function store(StoreTrialInquiryRequest $request): RedirectResponse
    {
        TrialInquiry::create($request->validated());

        return back()->with('trial_inquiry_sent', true);
    }
}
