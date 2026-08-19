<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SuperAdmin;
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

    private function currentSuperAdminId(): ?int
    {
        $userId = auth()->id();

        return $userId ? SuperAdmin::where('user_id', $userId)->value('id') : null;
    }
}
