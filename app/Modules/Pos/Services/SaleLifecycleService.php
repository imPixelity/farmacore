<?php

namespace App\Modules\Pos\Services;

use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Services\FinancialPostingService;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Pos\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleLifecycleService
{
    public function __construct(
        private StockService $stock,
        private FinancialPostingService $financialPosting,
    ) {}

    public function cancel(Sale $sale, string $reason): Sale
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages(['reason' => 'Alasan pembatalan wajib diisi maksimal 255 karakter.']);
        }

        return DB::transaction(function () use ($sale, $reason): Sale {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Penjualan hanya dapat dibatalkan satu kali.']);
            }

            $lockedSale->load(['items.batches.itemBatch', 'receivable']);
            foreach ($lockedSale->items as $item) {
                foreach ($item->batches as $allocation) {
                    $this->stock->increase(
                        $allocation->itemBatch,
                        (string) $allocation->qty_base,
                        'sale_cancel',
                        "Pembatalan {$lockedSale->invoice_no}",
                        Sale::class,
                        $lockedSale->id,
                    );
                }
            }

            FinancialTransaction::query()
                ->where('ref_type', Sale::class)
                ->where('ref_id', $lockedSale->id)
                ->where('status', 'posted')
                ->get()
                ->each(fn (FinancialTransaction $transaction): FinancialTransaction => $this->financialPosting->reverse(
                    $transaction,
                    "Pembatalan penjualan {$lockedSale->invoice_no}",
                ));

            if ($lockedSale->receivable) {
                $lockedSale->receivable->forceFill([
                    'cancelled_at' => now(),
                    'updated_by' => Auth::id(),
                ])->save();
            }

            $lockedSale->forceFill([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
            ])->save();

            return $lockedSale->fresh(['items.batches.itemBatch', 'payments', 'services', 'receivable']);
        }, attempts: 5);
    }
}
