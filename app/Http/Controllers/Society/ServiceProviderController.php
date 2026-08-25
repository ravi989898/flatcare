<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceProviderController extends Controller
{
    public function index(): View
    {
        $providers = ServiceProvider::orderBy('sort_order')->get();

        return view('society.service-providers.index', compact('providers'));
    }

    public function store(Request $request): RedirectResponse
    {
        ServiceProvider::create($this->validateProvider($request));

        return redirect()
            ->route('society.service-providers.index')
            ->with('success', 'Service provider added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        ServiceProvider::findOrFail($id)->update($this->validateProvider($request));

        return redirect()
            ->route('society.service-providers.index')
            ->with('success', 'Service provider updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        ServiceProvider::findOrFail($id)->delete();

        return redirect()
            ->route('society.service-providers.index')
            ->with('success', 'Service provider removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProvider(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'service_type' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
