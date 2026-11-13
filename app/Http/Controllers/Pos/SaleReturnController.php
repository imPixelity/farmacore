<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Services\SaleReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function store(Request $request, Sale $sale, SaleReturnService $returns): RedirectResponse
    {
        abort_unless(auth()->user()?->can('pos.return.manage'), 403);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'restock' => ['required', 'boolean'],
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.qty_base' => ['required', 'numeric', 'gt:0'],
        ]);
        $returns->create($sale, $validated);

        return back()->with('success', "Retur {$sale->invoice_no} berhasil dicatat.");
    }
}
