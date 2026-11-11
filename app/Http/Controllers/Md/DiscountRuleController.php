<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\DiscountRuleRequest;
use App\Modules\Md\Models\DiscountRule;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Services\DiscountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DiscountRuleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('md.price.manage');

        return view('md.discounts.index', [
            'discountRules' => DiscountRule::query()
                ->with(['item:id,code,name', 'category:id,name'])
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('md.price.manage');

        return view('md.discounts.create', $this->formData(null));
    }

    public function store(DiscountRuleRequest $request, DiscountService $discounts): RedirectResponse
    {
        $discounts->saveRule($request->validated());

        return redirect()->route('md.discounts.index')->with('success', 'Aturan diskon berhasil dibuat.');
    }

    public function edit(DiscountRule $discountRule): View
    {
        Gate::authorize('md.price.manage');

        return view('md.discounts.edit', $this->formData($discountRule));
    }

    public function update(DiscountRuleRequest $request, DiscountRule $discountRule, DiscountService $discounts): RedirectResponse
    {
        $discounts->saveRule($request->validated(), $discountRule);

        return redirect()->route('md.discounts.index')->with('success', 'Aturan diskon berhasil diperbarui.');
    }

    public function updateStatus(DiscountRule $discountRule, DiscountService $discounts): RedirectResponse
    {
        Gate::authorize('md.price.manage');
        $discounts->setRuleActive($discountRule, ! $discountRule->is_active);

        return redirect()->route('md.discounts.index')->with('success', 'Status aturan diskon berhasil diperbarui.');
    }

    /** @return array{discountRule: DiscountRule|null, items: mixed, categories: mixed, salesTypes: array<string, string>} */
    private function formData(?DiscountRule $discountRule): array
    {
        return [
            'discountRule' => $discountRule,
            'items' => Item::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'categories' => ItemCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'salesTypes' => [
                'resep' => 'Resep',
                'obat_bebas' => 'Obat bebas',
                'produk_lain' => 'Produk lainnya',
            ],
        ];
    }
}
