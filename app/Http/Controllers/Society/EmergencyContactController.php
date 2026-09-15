<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\EmergencyContactRequest;
use App\Models\Tenant\EmergencyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmergencyContactController extends Controller
{
    public function index(): View
    {
        $contacts = EmergencyContact::orderBy('sort_order')->get();

        return view('society.emergency-contacts.index', compact('contacts'));
    }

    public function store(EmergencyContactRequest $request): RedirectResponse
    {
        EmergencyContact::create($request->validated());

        return redirect()
            ->route('society.emergency-contacts.index')
            ->with('success', 'Emergency contact added.');
    }

    public function update(EmergencyContactRequest $request, int $id): RedirectResponse
    {
        $contact = EmergencyContact::findOrFail($id);
        $contact->update($request->validated());

        return redirect()
            ->route('society.emergency-contacts.index')
            ->with('success', 'Emergency contact updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        EmergencyContact::findOrFail($id)->delete();

        return redirect()
            ->route('society.emergency-contacts.index')
            ->with('success', 'Emergency contact removed.');
    }
}
