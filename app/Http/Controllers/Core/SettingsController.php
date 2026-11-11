<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(SettingsService $settings): View
    {
        return view('core.settings.index', [
            'settings' => $settings->all(),
        ]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'expiry_alert_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'payable_alert_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'login_max_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'login_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'session_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'price_rounding_step' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'usage_average_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'pharmacy_receipt_identity' => ['nullable', 'string', 'max:255'],
        ]);

        $settings->setMany($validated);

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
