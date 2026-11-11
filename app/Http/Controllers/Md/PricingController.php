<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\MarginSettingRequest;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\MarginSetting;
use App\Modules\Md\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        Gate::authorize('md.price.manage');

        $prices = ItemPrice::query()
            ->with('item:id,code,name,hna_base')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(30);

        return view('md.pricing.index', compact('prices'));
    }

    public function margins(): View
    {
        Gate::authorize('md.price.manage');

        return view('md.margins.index', [
            'marginSettings' => MarginSetting::query()
                ->with(['item:id,code,name', 'category:id,name'])
                ->orderBy('scope_type')
                ->orderBy('scope_key')
                ->orderBy('sales_type')
                ->get(),
            'items' => Item::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'categories' => ItemCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'salesTypes' => $this->salesTypes(),
        ]);
    }

    public function storeMargin(MarginSettingRequest $request, PricingService $pricing): RedirectResponse
    {
        $pricing->saveMarginSetting($request->validated());

        return redirect()->route('md.margins.index')->with('success', 'Pengaturan margin berhasil disimpan.');
    }

    /** @return array<string, string> */
    private function salesTypes(): array
    {
        return [
            'resep' => 'Resep',
            'obat_bebas' => 'Obat bebas',
            'produk_lain' => 'Produk lainnya',
        ];
    }
}
