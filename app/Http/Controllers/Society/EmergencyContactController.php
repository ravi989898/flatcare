<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\EmergencyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmergencyContactController extends Controller
{
    public function index(): View
    {
        $contacts = EmergencyContact::orderBy('sort_order')->get();

        return view('society.emergency-contacts.index', compact('contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContact($request);

        EmergencyContact::create($validated);

        return redirect()
            ->route('society.emergency-contacts.index')
            ->with('success', 'Emergency contact added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $contact = EmergencyContact::findOrFail($id);
        $contact->update($this->validateContact($request));

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

    /**
     * @return array<string, mixed>
     */
    private function validateContact(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'type' => 'required|string|max:50',
            'availability' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
