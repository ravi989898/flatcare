<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\TrialInquiry;
use App\Support\ListQuery;
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

        ListQuery::applySearch($query, $request, ['society_name', 'contact_name', 'email', 'phone']);

        $statusCounts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        ListQuery::applySort($query, $request, [
            'received' => 'created_at',
            'society' => 'society_name',
            'contact' => 'contact_name',
        ], 'received');

        $inquiries = $query->paginate(ListQuery::perPage($request, 25))->withQueryString();

        $newCount = TrialInquiry::new()->count();

        return view('admin.inquiries.index', compact('inquiries', 'newCount', 'statusCounts'));
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
