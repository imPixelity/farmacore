<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StorePurchaseReturnRequest;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\PurchaseReturn;
use App\Modules\Inventory\Services\PurchaseReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function index(Request $request, PurchaseReturnService $service): View
    {
        Gate::authorize('inventory.purchase_return.manage');

        return view('inventory.purchase-returns.index', [
            'returns' => $service->history($request->string('status')->toString() ?: null),
        ]);
    }

    public function create(PurchaseReturnService $service): View
    {
        Gate::authorize('inventory.purchase_return.manage');

        return view('inventory.purchase-returns.create', ['receipts' => $service->receiptsAvailableForReturn()]);
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnService $service): RedirectResponse
    {
        $receipt = GoodsReceipt::query()->findOrFail((int) $request->validated('goods_receipt_id'));
        $purchaseReturn = $service->createDraft(
            $receipt,
            $request->validated('return_date'),
            $request->validated('lines'),
            $request->validated('notes'),
        );

        return redirect()->route('inventory.purchase-returns.index')->with('success', 'Draft retur pembelian berhasil dibuat. Konfirmasi untuk mengurangi stok.')->with('created_return', $purchaseReturn->return_no);
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        Gate::authorize('inventory.purchase_return.manage');
        $purchaseReturn->load(['supplier', 'goodsReceipt', 'items.goodsReceiptItem.item', 'items.itemBatch', 'creator', 'confirmer']);

        return view('inventory.purchase-returns.show', ['purchaseReturn' => $purchaseReturn]);
    }

    public function confirm(PurchaseReturn $purchaseReturn, PurchaseReturnService $service): RedirectResponse
    {
        Gate::authorize('inventory.purchase_return.approve');
        $service->confirm($purchaseReturn);

        return back()->with('success', 'Retur dikonfirmasi, stok dikurangi, dan penyesuaian utang dicatat.');
    }

    public function cancel(PurchaseReturn $purchaseReturn, PurchaseReturnService $service): RedirectResponse
    {
        Gate::authorize('inventory.purchase_return.manage');
        $service->cancel($purchaseReturn);

        return back()->with('success', 'Draft retur dibatalkan.');
    }
}
