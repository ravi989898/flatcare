<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SuperAdmin;
use App\Support\FaviconGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function edit(): View
    {
        $settings = PlatformSetting::current();

        return view('admin.settings.branding', compact('settings'));
    }

    /**
     * Replace the platform logo. The old file is deleted only after the
     * new one is stored, so a failed upload never leaves the platform
     * without a logo.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $settings = PlatformSetting::current();
        $previousPath = $settings->logo_path;

        $newPath = $request->file('logo')->store('branding', 'public');

        $settings->update([
            'logo_path' => $newPath,
            'updated_by_super_admin_id' => $this->currentSuperAdminId(),
        ]);

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        PlatformSetting::forgetCache();

        return redirect()
            ->route('admin.settings.branding.edit')
            ->with('success', 'Logo updated successfully.');
    }

    /**
     * Revert to the default text/icon brand mark.
     */
    public function destroy(): RedirectResponse
    {
        $settings = PlatformSetting::current();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $settings->update([
            'logo_path' => null,
            'updated_by_super_admin_id' => $this->currentSuperAdminId(),
        ]);

        PlatformSetting::forgetCache();

        return redirect()
            ->route('admin.settings.branding.edit')
            ->with('success', 'Logo removed — using the default brand mark.');
    }

    /**
     * Replace the app icon. Immediately regenerates public/favicon.ico and
     * every PNG/apple-touch-icon variant from it, so the new icon shows up
     * everywhere those static files are already linked from — the public
     * site's <link rel="icon"> tags and, via the browser's automatic
     * /favicon.ico request, the admin panel and auth pages too — with
     * no other page needing to change.
     */
    public function updateIcon(Request $request): RedirectResponse
    {
        $request->validate([
            'icon' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $settings = PlatformSetting::current();
        $previousPath = $settings->icon_path;

        $newPath = $request->file('icon')->store('branding', 'public');

        FaviconGenerator::generate(Storage::disk('public')->path($newPath), public_path());

        $settings->update([
            'icon_path' => $newPath,
            'updated_by_super_admin_id' => $this->currentSuperAdminId(),
        ]);

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        PlatformSetting::forgetCache();

        return redirect()
            ->route('admin.settings.branding.edit')
            ->with('success', 'App icon updated — it may take a moment to refresh in your browser tab (icons are cached aggressively).');
    }

    /**
     * Revert the app icon to the bundled FlatCare default.
     */
    public function destroyIcon(): RedirectResponse
    {
        $settings = PlatformSetting::current();

        if ($settings->icon_path) {
            Storage::disk('public')->delete($settings->icon_path);
        }

        FaviconGenerator::generate(resource_path('branding/default-app-icon.png'), public_path());

        $settings->update([
            'icon_path' => null,
            'updated_by_super_admin_id' => $this->currentSuperAdminId(),
        ]);

        PlatformSetting::forgetCache();

        return redirect()
            ->route('admin.settings.branding.edit')
            ->with('success', 'App icon removed — reverted to the default FlatCare icon.');
    }

    private function currentSuperAdminId(): ?int
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->value('id') : null;
    }
}
