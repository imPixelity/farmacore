<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Jobs\Inventory\RecalculateStockPolicy;
use App\Modules\Inventory\Services\StockPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockPolicyController extends Controller
{
    public function index(StockPolicyService $policies): View
    {
        Gate::authorize('inventory.stock.manage');

        return view('inventory.stock-policies.index', [
            'policies' => $policies->policies(),
        ]);
    }

    public function update(Request $request, StockPolicyService $policies): RedirectResponse
    {
        Gate::authorize('inventory.stock.manage');

        $validated = $request->validate([
            'policies' => ['required', 'array'],
            'policies.fast' => ['required', 'array'],
            'policies.medium' => ['required', 'array'],
            'policies.slow' => ['required', 'array'],
            'policies.*.min_stock_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'policies.*.safety_factor' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99.99'],
        ]);

        $policies->savePolicies($validated['policies']);
        RecalculateStockPolicy::dispatch();

        return redirect()->route('inventory.stock-policies.index')
            ->with('success', 'Kebijakan stok disimpan. Perhitungan ulang barang dijadwalkan.');
    }
}
