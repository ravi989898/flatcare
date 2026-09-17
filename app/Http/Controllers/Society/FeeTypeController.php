<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\FeeTypeRequest;
use App\Models\Tenant\FeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The catalog of one-off charge types (function/event usage, hall booking,
 * renovation fund, transfer fee, ...) that Society\ExtraChargeController
 * lets Society Admin raise against a flat.
 */
class FeeTypeController extends Controller
{
    public function index(): View
    {
        $feeTypes = FeeType::orderBy('sort_order')->get();

        return view('society.fee-types.index', compact('feeTypes'));
    }

    public function store(FeeTypeRequest $request): RedirectResponse
    {
        FeeType::create($request->validated());

        return redirect()
            ->route('society.fee-types.index')
            ->with('success', 'Fee type added.');
    }

    public function update(FeeTypeRequest $request, int $id): RedirectResponse
    {
        FeeType::findOrFail($id)->update($request->validated());

        return redirect()
            ->route('society.fee-types.index')
            ->with('success', 'Fee type updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        FeeType::findOrFail($id)->delete();

        return redirect()
            ->route('society.fee-types.index')
            ->with('success', 'Fee type removed.');
    }
}
