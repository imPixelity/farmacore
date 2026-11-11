<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ConfirmGoodsReceiptRequest;
use App\Http\Requests\Inventory\StoreGoodsReceiptRequest;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Services\GoodsReceiptService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Services\ReceiptMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function index(): View
    {
        Gate::authorize('inventory.receipt.view');

        $receipts = GoodsReceipt::query()
            ->with('supplier:id,name')
            ->withCount('items')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('inventory.receipts.index', ['receipts' => $receipts]);
    }

    public function create(GoodsReceiptService $goodsReceiptService): View
    {
        Gate::authorize('inventory.receipt.manage');

        $supplierId = old('supplier_id');
        $invoiceNo = old('invoice_no');

        return $this->formView(
            null,
            $goodsReceiptService->hasDuplicateInvoice((int) $supplierId, (string) $invoiceNo),
        );
    }

    public function store(StoreGoodsReceiptRequest $request, GoodsReceiptService $goodsReceiptService): RedirectResponse
    {
        $data = $request->validated();
        $confirmDuplicate = (bool) ($data['confirm_duplicate'] ?? false);
        unset($data['confirm_duplicate']);

        if ($goodsReceiptService->hasDuplicateInvoice((int) $data['supplier_id'], (string) $data['invoice_no']) && ! $confirmDuplicate) {
            return back()
                ->withInput()
                ->with('duplicate_invoice_warning', 'Nomor faktur ini sudah digunakan untuk supplier yang dipilih. Periksa data lalu pilih lanjutkan bila penerimaan memang berbeda.');
        }

        $receipt = $goodsReceiptService->createDraft($data);

        return redirect()->route('inventory.receipts.show', $receipt)
            ->with('status', 'Draft penerimaan berhasil disimpan. Stok belum berubah.');
    }

    public function show(GoodsReceipt $goodsReceipt, ReceiptMatchingService $matchingService): View
    {
        Gate::authorize('inventory.receipt.view');

        $goodsReceipt->load([
            'supplier:id,name',
            'items.item:id,code,name,base_unit_id',
            'items.item.baseUnit:id,name',
            'items.unit:id,name',
            'items.approver:id,name',
            'items.batch',
            'payable',
            'confirmer:id,name',
        ]);
        $matching = $goodsReceipt->status === 'draft'
            ? $matchingService->preview($goodsReceipt)
            : ['warnings' => [], 'status' => $goodsReceipt->matching_status, 'lines' => []];
        $cashBankAccounts = $goodsReceipt->payment_method === 'tunai'
            ? CashBankAccount::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'current_balance'])
            : collect();

        return view('inventory.receipts.show', [
            'receipt' => $goodsReceipt,
            'cashBankAccounts' => $cashBankAccounts,
            'matching' => $matching,
        ]);
    }

    public function edit(GoodsReceipt $goodsReceipt, GoodsReceiptService $goodsReceiptService): View|RedirectResponse
    {
        Gate::authorize('inventory.receipt.manage');

        if ($goodsReceipt->status !== 'draft') {
            return redirect()->route('inventory.receipts.show', $goodsReceipt)
                ->with('error', 'Penerimaan yang sudah dikonfirmasi tidak dapat diubah.');
        }

        $goodsReceipt->load('items');

        return $this->formView(
            $goodsReceipt,
            $goodsReceiptService->hasDuplicateInvoice(
                (int) old('supplier_id', $goodsReceipt->supplier_id),
                (string) old('invoice_no', $goodsReceipt->invoice_no),
                (int) $goodsReceipt->id,
            ),
        );
    }

    public function update(
        StoreGoodsReceiptRequest $request,
        GoodsReceipt $goodsReceipt,
        GoodsReceiptService $goodsReceiptService,
    ): RedirectResponse {
        $data = $request->validated();
        $confirmDuplicate = (bool) ($data['confirm_duplicate'] ?? false);
        unset($data['confirm_duplicate']);

        if ($goodsReceiptService->hasDuplicateInvoice(
            (int) $data['supplier_id'],
            (string) $data['invoice_no'],
            (int) $goodsReceipt->id,
        ) && ! $confirmDuplicate) {
            return back()
                ->withInput()
                ->with('duplicate_invoice_warning', 'Nomor faktur ini sudah digunakan untuk supplier yang dipilih. Periksa data lalu pilih lanjutkan bila penerimaan memang berbeda.');
        }

        $receipt = $goodsReceiptService->updateDraft($goodsReceipt, $data);

        return redirect()->route('inventory.receipts.show', $receipt)
            ->with('status', 'Draft penerimaan berhasil diperbarui. Stok belum berubah.');
    }

    public function confirm(
        ConfirmGoodsReceiptRequest $request,
        GoodsReceipt $goodsReceipt,
        GoodsReceiptService $goodsReceiptService,
    ): RedirectResponse {
        $validated = $request->validated();
        $cashBankAccountId = $validated['cash_bank_account_id'] ?? null;
        $receipt = $goodsReceiptService->confirm(
            $goodsReceipt,
            $cashBankAccountId === null ? null : (int) $cashBankAccountId,
            (bool) ($validated['confirm_matching'] ?? false),
        );

        return redirect()->route('inventory.receipts.show', $receipt)
            ->with('status', 'Penerimaan berhasil dikonfirmasi dan seluruh dampak transaksi sudah diposting.');
    }

    private function formView(?GoodsReceipt $receipt, bool $duplicateInvoice): View
    {
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'payment_term_days']);
        $purchaseOrders = PurchaseOrder::query()->with('supplier:id,name')->whereIn('status', ['sent', 'partially_received'])->orderByDesc('order_date')->get(['id', 'po_no', 'supplier_id', 'status']);
        $items = Item::query()
            ->where('is_active', true)
            ->with(['itemUnits.unit:id,name'])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'base_unit_id', 'is_taxable']);

        $lines = old('lines');

        if (! is_array($lines)) {
            $lines = $receipt?->items->map(fn ($item): array => [
                'item_id' => $item->item_id,
                'item_unit_id' => $item->unit_id,
                'qty_received' => $item->qty_received,
                'batch_no' => $item->batch_no,
                'expiry_date' => $item->expiry_date->toDateString(),
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'approve_expiry' => $item->approved_by !== null,
            ])->all() ?? [[]];
        }

        return view('inventory.receipts.form', [
            'receipt' => $receipt,
            'suppliers' => $suppliers,
            'items' => $items,
            'lines' => $lines,
            'duplicateInvoice' => $duplicateInvoice,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
}
