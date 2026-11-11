<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, PurchaseOrderService $service): View
    {
        Gate::authorize('purchasing.po.manage');
        $orders = PurchaseOrder::query()->with('supplier:id,name')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->orderByDesc('order_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('purchasing.purchase-orders.index', ['orders' => $orders, 'recommendations' => $service->recommendations()]);
    }

    public function create(PurchaseOrderService $service): View
    {
        Gate::authorize('purchasing.po.manage');

        return view('purchasing.purchase-orders.form', ['order' => null, 'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']), 'items' => $service->recommendations()]);
    }

    public function store(Request $request, PurchaseOrderService $service): RedirectResponse
    {
        Gate::authorize('purchasing.po.manage');
        $order = $service->createDraft($this->validated($request));

        return redirect()->route('purchasing.purchase-orders.show', $order)->with('success', 'PO draft berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        Gate::authorize('purchasing.po.manage');

        return view('purchasing.purchase-orders.show', ['order' => $purchaseOrder->load(['supplier', 'items.item', 'items.unit'])]);
    }

    public function edit(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): View
    {
        Gate::authorize('purchasing.po.manage');
        abort_if($purchaseOrder->status !== 'draft', 403, 'PO yang sudah dikirim tidak dapat diedit.');
        $order = $purchaseOrder->load(['items.item.baseUnit', 'items.item.orderUnit', 'items.unit']);
        $recommended = $service->recommendations();
        $selected = $order->items->map(fn ($line) => $line->item)->filter();

        return view('purchasing.purchase-orders.form', ['order' => $order, 'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']), 'items' => $recommended->merge($selected)->unique('id')->values()]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        Gate::authorize('purchasing.po.manage');
        $order = $service->updateDraft($purchaseOrder, $this->validated($request));

        return redirect()->route('purchasing.purchase-orders.show', $order)->with('success', 'PO draft berhasil diperbarui.');
    }

    public function send(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        Gate::authorize('purchasing.po.manage');
        $service->send($purchaseOrder);

        return back()->with('success', 'PO berhasil dikirim dan tidak dapat diedit lagi.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'], 'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'], 'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'], 'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.unit_id' => ['required', 'integer', 'exists:units,id'], 'lines.*.qty_ordered' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999999.999'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999.9999'],
        ]);
    }
}
