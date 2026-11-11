<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\ItemRequest;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\TaxRule;
use App\Modules\Md\Models\Unit;
use App\Modules\Md\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');

        $items = Item::query()
            ->with(['category', 'baseUnit', 'orderUnit'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('md.items.index', [
            'items' => $items,
            'categories' => ItemCategory::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.items.create', $this->formData());
    }

    public function store(ItemRequest $request, MasterDataService $service): RedirectResponse
    {
        $item = $service->saveItem($request->validated());

        return redirect()->route('md.items.edit', $item)->with('success', 'Barang berhasil dibuat.');
    }

    public function edit(Item $item): View
    {
        Gate::authorize('md.item.manage');

        return view('md.items.edit', $this->formData($item->load(['itemUnits', 'category', 'baseUnit', 'orderUnit'])));
    }

    public function update(ItemRequest $request, Item $item, MasterDataService $service): RedirectResponse
    {
        $service->saveItem($request->validated(), $item);

        return redirect()->route('md.items.edit', $item)->with('success', 'Barang berhasil diperbarui.');
    }

    public function updateStatus(Item $item, MasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setItemActive($item, ! $item->is_active);

        return redirect()->route('md.items.index')->with('success', 'Status barang berhasil diperbarui.');
    }

    /** @return array{item: Item|null, categories: mixed, units: mixed} */
    private function formData(?Item $item = null): array
    {
        return [
            'item' => $item,
            'categories' => ItemCategory::query()->active()->orderBy('type')->orderBy('name')->get(),
            'units' => Unit::query()->active()->orderBy('name')->get(),
            'taxRules' => TaxRule::query()->get()->keyBy('item_type'),
        ];
    }
}
