<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Services\ReceiptPrintService;
use App\Modules\Pos\Services\SaleLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function receipt(Sale $sale, ReceiptPrintService $receipts): View
    {
        abort_unless(auth()->user()?->can('pos.sale.view'), 403);

        return $receipts->render($sale, request()->boolean('copy'));
    }

    public function cancel(Request $request, Sale $sale, SaleLifecycleService $lifecycle): RedirectResponse
    {
        abort_unless(auth()->user()?->can('pos.sale.cancel'), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $lifecycle->cancel($sale, $validated['reason']);

        return back()->with('success', "Penjualan {$sale->invoice_no} berhasil dibatalkan.");
    }
}
