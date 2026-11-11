<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\TaxRuleRequest;
use App\Http\Requests\Md\TaxSettingRequest;
use App\Modules\Md\Models\TaxRule;
use App\Modules\Md\Models\TaxSetting;
use App\Modules\Md\Services\PricingService;
use App\Modules\Md\Services\TaxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(): View
    {
        Gate::authorize('md.price.manage');

        return view('md.taxes.index', [
            'taxSettings' => TaxSetting::query()->orderByDesc('effective_from')->orderByDesc('id')->get(),
            'taxRules' => TaxRule::query()->orderBy('item_type')->get()->keyBy('item_type'),
            'itemTypes' => [
                'obat' => 'Obat',
                'alkes' => 'Alat kesehatan',
                'non_obat' => 'Non-obat',
            ],
        ]);
    }

    public function storeSetting(
        TaxSettingRequest $request,
        TaxService $taxService,
        PricingService $pricingService,
    ): RedirectResponse {
        $data = $request->validated();
        $taxService->addRate($data);

        if (Carbon::parse($data['effective_from'])->isToday() || Carbon::parse($data['effective_from'])->isPast()) {
            $pricingService->recalculateTaxableItems();
        }

        return redirect()->route('md.taxes.index')->with('success', 'Tarif pajak ditambahkan ke histori.');
    }

    public function storeRule(TaxRuleRequest $request, TaxService $service): RedirectResponse
    {
        $service->saveRule($request->validated());

        return redirect()->route('md.taxes.index')->with('success', 'Default status pajak berhasil disimpan.');
    }

    public function updateRule(TaxRuleRequest $request, TaxRule $taxRule, TaxService $service): RedirectResponse
    {
        $service->saveRule($request->validated(), $taxRule);

        return redirect()->route('md.taxes.index')->with('success', 'Default status pajak berhasil diperbarui.');
    }
}
