<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\WaterExtraChargeRequest;
use App\Models\Tenant\WaterExtraCharge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Recurring charges (festival fund, lift AMC installment, ...) that
 * WaterBillingService folds automatically into every flat's water-reading
 * bill while the billing month falls within the charge's [start_date,
 * end_date] — unlike Society\ExtraChargeController's one-off "Raise
 * Charge", which bills once against a fixed due date for one flat.
 *
 * Always applies society-wide (flat_id left null) — the form only collects
 * amount/start_date/end_date.
 */
class WaterExtraChargeController extends Controller
{
    private const DEFAULT_TITLE = 'Extra Charge';

    public function index(): View
    {
        $charges = WaterExtraCharge::with(['createdBy', 'updatedBy'])
            ->latest('start_date')
            ->paginate(20);

        return view('society.water-extra-charges.index', compact('charges'));
    }

    public function create(): View
    {
        return view('society.water-extra-charges.create');
    }

    public function store(WaterExtraChargeRequest $request): RedirectResponse
    {
        WaterExtraCharge::create($request->validated() + [
            'title' => self::DEFAULT_TITLE,
            'created_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.water-extra-charges.index')
            ->with('success', 'Extra charge added.');
    }

    public function edit(int $id): View
    {
        $charge = WaterExtraCharge::findOrFail($id);

        return view('society.water-extra-charges.edit', compact('charge'));
    }

    public function update(WaterExtraChargeRequest $request, int $id): RedirectResponse
    {
        $charge = WaterExtraCharge::findOrFail($id);

        $charge->update($request->validated() + [
            'updated_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.water-extra-charges.index')
            ->with('success', 'Extra charge updated.');
    }

    public function activate(int $id): RedirectResponse
    {
        WaterExtraCharge::findOrFail($id)->update([
            'is_active' => true,
            'updated_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.water-extra-charges.index')
            ->with('success', 'Extra charge activated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        WaterExtraCharge::findOrFail($id)->update([
            'is_active' => false,
            'updated_by_user_id' => Auth::guard('society')->id(),
        ]);

        return redirect()
            ->route('society.water-extra-charges.index')
            ->with('success', 'Extra charge deactivated.');
    }
}
