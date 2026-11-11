<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('inventory.stock.view');

        $search = trim((string) $request->string('search'));
        $items = Item::query()
            ->with(['category:id,name', 'baseUnit:id,name', 'itemUnits.unit:id,name'])
            ->withCount('batches')
            ->withSum('batches as stock_total', 'qty_on_hand')
            ->withSum(['batches as available_total' => static fn (Builder $query) => $query->available()], 'qty_on_hand')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $items->getCollection()->transform(function (Item $item): Item {
            $stockTotal = bcadd((string) ($item->stock_total ?? '0'), '0', 3);
            $stockByUnit = $item->itemUnits
                ->filter(fn (ItemUnit $itemUnit): bool => bccomp((string) $itemUnit->factor_to_base, '0', 3) > 0)
                ->map(fn (ItemUnit $itemUnit): array => [
                    'name' => $itemUnit->unit->name,
                    'quantity' => $this->formatQuantity(bcdiv($stockTotal, (string) $itemUnit->factor_to_base, 3)),
                ])
                ->values();

            if ($stockByUnit->isEmpty()) {
                $stockByUnit->push([
                    'name' => $item->baseUnit->name,
                    'quantity' => $this->formatQuantity($stockTotal),
                ]);
            }

            $item->setAttribute('stock_by_unit', $stockByUnit);

            return $item;
        });

        return view('inventory.stock.index', ['items' => $items]);
    }

    public function show(Item $item, StockService $stockService): View
    {
        Gate::authorize('inventory.stock.view');

        $item->load(['baseUnit:id,name']);
        $batches = ItemBatch::query()
            ->where('item_id', $item->getKey())
            ->orderBy('expiry_date')
            ->orderBy('batch_no')
            ->orderBy('id')
            ->paginate(20);

        return view('inventory.stock.show', [
            'item' => $item,
            'batches' => $batches,
            'stockTotal' => bcadd((string) $item->batches()->sum('qty_on_hand'), '0', 3),
            'availableTotal' => $stockService->available($item),
        ]);
    }

    private function formatQuantity(string $quantity): string
    {
        [$whole, $fraction] = explode('.', bcadd($quantity, '0', 3));

        return number_format((int) $whole).'.'.$fraction;
    }
}
