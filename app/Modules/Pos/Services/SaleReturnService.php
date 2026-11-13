<?php

namespace App\Modules\Pos\Services;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleItem;
use App\Modules\Pos\Models\SaleReturn;
use App\Modules\Pos\Models\SaleReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function __construct(
        private StockService $stock,
        private FinancialPostingService $financialPosting,
    ) {}

    /** @param array{items: array<int, array{sale_item_id: int, qty_base: string|int|float}>, reason: string, restock: bool, cash_bank_account_id: int|string} $data */
    public function create(Sale $sale, array $data): SaleReturn
    {
        $reason = trim((string) $data['reason']);
        if ($reason === '' || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages(['reason' => 'Alasan retur wajib diisi maksimal 255 karakter.']);
        }

        return DB::transaction(function () use ($sale, $data, $reason): SaleReturn {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Penjualan yang batal tidak dapat diretur.']);
            }

            $category = TransactionCategory::query()->where('source_code', 'SALE_REFUND')->where('direction', 'out')->first();
            if (! $category) {
                throw ValidationException::withMessages(['return' => 'Kategori retur penjualan belum tersedia.']);
            }
            $account = CashBankAccount::query()->find((int) $data['cash_bank_account_id']);
            if (! $account || $account->type !== 'kas' || ! $account->is_active) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas aktif wajib dipilih untuk pengembalian dana.']);
            }

            $items = [];
            $refund = '0.00';
            foreach ($data['items'] as $index => $input) {
                $saleItem = SaleItem::query()->where('sale_id', $lockedSale->id)->with('batches.itemBatch')->find((int) $input['sale_item_id']);
                if (! $saleItem) {
                    throw ValidationException::withMessages(["items.{$index}.sale_item_id" => 'Baris penjualan tidak ditemukan.']);
                }
                $quantity = (string) $input['qty_base'];
                if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity) || bccomp($quantity, '0', 3) <= 0) {
                    throw ValidationException::withMessages(["items.{$index}.qty_base" => 'Jumlah retur harus positif.']);
                }
                $alreadyReturned = (string) SaleReturnItem::query()->where('sale_item_id', $saleItem->id)->sum('qty_base');
                $remaining = bcsub((string) $saleItem->qty_base, $alreadyReturned, 3);
                if (bccomp($quantity, $remaining, 3) > 0) {
                    throw ValidationException::withMessages(["items.{$index}.qty_base" => 'Jumlah retur melebihi jumlah yang masih dapat diretur.']);
                }
                $lineTotal = bcmul($quantity, (string) $saleItem->unit_price_base, 2);
                $refund = bcadd($refund, $lineTotal, 2);
                $items[] = [$saleItem, $quantity, $lineTotal];
            }

            if ($items === [] || bccomp($refund, '0.00', 2) <= 0) {
                throw ValidationException::withMessages(['items' => 'Minimal satu barang harus diretur.']);
            }

            $return = SaleReturn::query()->create([
                'return_no' => 'RET-'.Str::ulid(),
                'sale_id' => $lockedSale->id,
                'processed_by' => Auth::id(),
                'return_date' => now(),
                'reason' => $reason,
                'refund_amount' => $refund,
                'restock' => (bool) $data['restock'],
            ]);

            foreach ($items as [$saleItem, $quantity, $lineTotal]) {
                $remaining = $quantity;
                foreach ($saleItem->batches as $allocation) {
                    $previous = (string) SaleReturnItem::query()
                        ->where('sale_item_id', $saleItem->id)
                        ->where('item_batch_id', $allocation->item_batch_id)
                        ->sum('qty_base');
                    $available = bcsub((string) $allocation->qty_base, $previous, 3);
                    $allocated = bccomp($available, $remaining, 3) < 0 ? $available : $remaining;
                    if (bccomp($allocated, '0', 3) <= 0) {
                        continue;
                    }
                    SaleReturnItem::query()->create([
                        'sale_return_id' => $return->id,
                        'sale_item_id' => $saleItem->id,
                        'item_batch_id' => $allocation->item_batch_id,
                        'qty_base' => $allocated,
                        'unit_price_base' => $saleItem->unit_price_base,
                        'line_total' => bcmul($allocated, (string) $saleItem->unit_price_base, 2),
                    ]);
                    if ($return->restock) {
                        $this->stock->increase($allocation->itemBatch, $allocated, 'sale_return', "Retur {$return->return_no}", SaleReturn::class, $return->id);
                    }
                    $remaining = bcsub($remaining, $allocated, 3);
                    if (bccomp($remaining, '0', 3) === 0) {
                        break;
                    }
                }
                if (bccomp($remaining, '0', 3) > 0) {
                    throw ValidationException::withMessages(['items' => 'Alokasi batch retur tidak konsisten dengan penjualan.']);
                }
            }

            $this->financialPosting->post([
                'cash_bank_account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => $refund,
                'description' => "Retur penjualan {$lockedSale->invoice_no}",
                'trx_date' => now(),
                'direction' => 'out',
                'ref_type' => SaleReturn::class,
                'ref_id' => $return->id,
            ]);

            return $return->fresh(['sale', 'items.saleItem', 'items.itemBatch']);
        }, attempts: 5);
    }
}
