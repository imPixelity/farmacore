<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockOpnameRequest;
use App\Http\Requests\Inventory\StoreStockWriteOffRequest;
use App\Http\Requests\Inventory\UpdateStockOpnameRequest;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockWriteOff;
use App\Modules\Inventory\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function index(Request $request, StockOpnameService $service): View
    {
        Gate::authorize('inventory.opname.manage');

        return view('inventory.opnames.index', [
            'opnames' => $service->history($request->string('status')->toString() ?: null),
            'writeOffs' => $service->writeOffHistory(),
        ]);
    }

    public function store(StoreStockOpnameRequest $request, StockOpnameService $service): RedirectResponse
    {
        $opname = $service->createDraft($request->validated('opname_date'), $request->validated('notes'));

        return redirect()->route('inventory.opnames.show', $opname)->with('success', 'Stock opname dibuat dengan snapshot stok sistem.');
    }

    public function show(StockOpname $stockOpname): View
    {
        Gate::authorize('inventory.opname.manage');
        $stockOpname->load(['items.itemBatch.item:id,name,code', 'performer:id,name', 'approver:id,name']);

        return view('inventory.opnames.show', ['opname' => $stockOpname]);
    }

    public function update(UpdateStockOpnameRequest $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $service->updatePhysical($stockOpname, $request->validated('items'));

        return back()->with('success', 'Hasil hitung fisik dan selisih berhasil disimpan.');
    }

    public function submit(StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        Gate::authorize('inventory.opname.manage');
        $service->submit($stockOpname);

        return back()->with('success', 'Stock opname diajukan untuk persetujuan apoteker.');
    }

    public function approve(StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        Gate::authorize('inventory.opname.approve');
        $service->approve($stockOpname);

        return back()->with('success', 'Stock opname disetujui dan mutasi penyesuaian diterapkan.');
    }

    public function reject(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        Gate::authorize('inventory.opname.approve');
        $service->reject($stockOpname, $request->string('notes')->toString());

        return back()->with('success', 'Stock opname ditolak tanpa mengubah stok.');
    }

    public function writeOffCreate(): View
    {
        Gate::authorize('inventory.opname.manage');
        $batches = ItemBatch::query()->with('item:id,name,code')->where('qty_on_hand', '>', 0)->orderBy('expiry_date')->orderBy('id')->get();

        return view('inventory.write-offs.create', ['batches' => $batches]);
    }

    public function writeOffStore(StoreStockWriteOffRequest $request, StockOpnameService $service): RedirectResponse
    {
        $batch = ItemBatch::query()->findOrFail((int) $request->validated('item_batch_id'));
        $writeOff = $service->createWriteOff(
            $batch,
            $request->validated('write_off_date'),
            $request->validated('qty_base'),
            $request->validated('reason'),
            $request->validated('notes'),
        );

        return redirect()->route('inventory.opnames.index')->with('success', 'Draft write-off berhasil dibuat dan menunggu persetujuan.');
    }

    public function writeOffApprove(StockWriteOff $stockWriteOff, StockOpnameService $service): RedirectResponse
    {
        Gate::authorize('inventory.opname.approve');
        $service->approveWriteOff($stockWriteOff);

        return back()->with('success', 'Write-off disetujui dan stok dikurangi.');
    }
}
