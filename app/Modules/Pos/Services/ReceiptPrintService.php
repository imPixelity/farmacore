<?php

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Models\Sale;
use Illuminate\Contracts\View\View;

class ReceiptPrintService
{
    public function render(Sale $sale, bool $copy = false): View
    {
        $sale->loadMissing(['cashier', 'patient', 'items.item', 'services', 'payments']);

        return view('pos.receipt', [
            'sale' => $sale,
            'copy' => $copy,
        ]);
    }
}
