<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\TrialInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrialInquiryController extends Controller
{
    /**
     * List "Start free trial" leads with search/status filters.
     */
    public function index(Request $request): View
    {
        $query = TrialInquiry::query();

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('society_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $inquiries = $query->latest()->paginate(15)->withQueryString();

        $newCount = TrialInquiry::new()->count();

        return view('admin.inquiries.index', compact('inquiries', 'newCount'));
    }

    /**
     * Mark a lead as contacted (records who and when).
     */
    public function markContacted(TrialInquiry $inquiry): RedirectResponse
    {
        $superAdmin = SuperAdmin::where('user_id', auth()->id())->first();

        $inquiry->update([
            'status' => 'contacted',
            'contacted_at' => now(),
            'contacted_by_super_admin_id' => $superAdmin?->id,
        ]);

        return back()->with('success', 'Marked as contacted.');
    }

    /**
     * Dismiss a lead (not a good fit / not interested).
     */
    public function dismiss(TrialInquiry $inquiry): RedirectResponse
    {
        $inquiry->update(['status' => 'dismissed']);

        return back()->with('success', 'Inquiry dismissed.');
    }

    public function destroy(TrialInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return back()->with('success', 'Inquiry deleted.');
    }
}
