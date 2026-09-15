<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Requests\Society\ServiceProviderRequest;
use App\Models\Tenant\ServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceProviderController extends Controller
{
    public function index(): View
    {
        $providers = ServiceProvider::orderBy('sort_order')->get();

        return view('society.service-providers.index', compact('providers'));
    }

    public function store(ServiceProviderRequest $request): RedirectResponse
    {
        ServiceProvider::create($request->validated());

        return redirect()
            ->route('society.service-providers.index')
            ->with('success', 'Service provider added.');
    }

    public function update(ServiceProviderRequest $request, int $id): RedirectResponse
    {
        ServiceProvider::findOrFail($id)->update($request->validated());

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
}
