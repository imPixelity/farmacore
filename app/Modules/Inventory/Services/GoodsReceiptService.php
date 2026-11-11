<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Services\PricingService;
use App\Modules\Md\Services\TaxService;
use App\Modules\Purchasing\Models\Payable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoodsReceiptService
{
    private const MAX_MONEY = '9999999999999.99';

    private const MAX_UNIT_COST = '99999999999.9999';

    public function __construct(
        private StockService $stockService,
        private PricingService $pricingService,
        private TaxService $taxService,
        private FinancialPostingService $financialPostingService,
    ) {}

    public function hasDuplicateInvoice(int $supplierId, string $invoiceNo, ?int $exceptReceiptId = null): bool
    {
        return GoodsReceipt::query()
            ->where('supplier_id', $supplierId)
            ->where('invoice_no', trim($invoiceNo))
            ->where('status', '!=', 'cancelled')
            ->when($exceptReceiptId !== null, fn (Builder $query): Builder => $query->where('id', '!=', $exceptReceiptId))
            ->exists();
    }

    /** @param array<string, mixed> $data */
    public function createDraft(array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($data): GoodsReceipt {
            $supplier = Supplier::query()->lockForUpdate()->findOrFail((int) $data['supplier_id']);

            if (! $supplier->is_active) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier sudah nonaktif.']);
            }

            $invoiceDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $data['invoice_date']);
            $lines = $this->prepareLines($data['lines'], $invoiceDate);
            $totals = $this->calculateTotals($lines, (string) $data['discount_amount'], $invoiceDate);
            $dueDate = $this->resolveDueDate($data, $supplier);

            $receipt = new GoodsReceipt;
            $receipt->fill([
                'receipt_no' => 'GR-'.Str::ulid(),
                'supplier_id' => $supplier->id,
                'invoice_no' => trim((string) $data['invoice_no']),
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate,
                'payment_method' => $data['payment_method'],
                'discount_amount' => $this->money((string) $data['discount_amount']),
                'total_before_tax' => $totals['total_before_tax'],
                'tax_amount' => $totals['tax_amount'],
                'total_after_tax' => $totals['total_after_tax'],
                'matching_status' => 'tidak_ada_po',
                'status' => 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $receipt->save();

            foreach ($lines as $line) {
                $this->createReceiptItem($receipt, $line);
            }

            return $receipt->load('items');
        }, attempts: 5);
    }

    /** @param array<string, mixed> $data */
    public function updateDraft(GoodsReceipt $goodsReceipt, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt, $data): GoodsReceipt {
            $receipt = GoodsReceipt::query()->lockForUpdate()->findOrFail($goodsReceipt->id);

            if ($receipt->status !== 'draft') {
                throw ValidationException::withMessages(['receipt' => 'Penerimaan yang sudah dikonfirmasi tidak dapat diubah.']);
            }

            $supplier = Supplier::query()->lockForUpdate()->findOrFail((int) $data['supplier_id']);

            if (! $supplier->is_active) {
                throw ValidationException::withMessages(['supplier_id' => 'Supplier sudah nonaktif.']);
            }

            $invoiceDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $data['invoice_date']);
            $data['lines'] = $this->preserveExistingExpiryApprovals($receipt, $data['lines'], $invoiceDate);
            $lines = $this->prepareLines($data['lines'], $invoiceDate);
            $totals = $this->calculateTotals($lines, (string) $data['discount_amount'], $invoiceDate);
            $dueDate = $this->resolveDueDate($data, $supplier);

            $receipt->fill([
                'supplier_id' => $supplier->id,
                'invoice_no' => trim((string) $data['invoice_no']),
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate,
                'payment_method' => $data['payment_method'],
                'discount_amount' => $this->money((string) $data['discount_amount']),
                'total_before_tax' => $totals['total_before_tax'],
                'tax_amount' => $totals['tax_amount'],
                'total_after_tax' => $totals['total_after_tax'],
                'updated_by' => Auth::id(),
            ])->save();

            $receipt->items()->delete();

            foreach ($lines as $line) {
                $this->createReceiptItem($receipt, $line);
            }

            return $receipt->load('items');
        }, attempts: 5);
    }

    public function confirm(GoodsReceipt $goodsReceipt, ?int $cashBankAccountId = null): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt, $cashBankAccountId): GoodsReceipt {
            $receipt = GoodsReceipt::query()
                ->with(['items.item'])
                ->lockForUpdate()
                ->findOrFail($goodsReceipt->id);

            if ($receipt->status === 'confirmed') {
                return $receipt;
            }

            if ($receipt->status !== 'draft') {
                throw ValidationException::withMessages(['receipt' => 'Penerimaan ini tidak dapat dikonfirmasi.']);
            }

            if ($receipt->items->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'Penerimaan harus memiliki minimal satu baris barang.']);
            }

            $lines = $receipt->items->map(fn (GoodsReceiptItem $item): array => [
                'receipt_item' => $item,
                'item' => $item->item,
                'item_id' => (int) $item->item_id,
                'unit_id' => (int) $item->unit_id,
                'qty_received' => (string) $item->qty_received,
                'qty_base' => (string) $item->qty_base,
                'batch_no' => $item->batch_no,
                'expiry_date' => $item->expiry_date->toDateString(),
                'unit_price' => (string) $item->unit_price,
                'discount_amount' => (string) $item->discount_amount,
                'line_total' => (string) $item->line_total,
                'is_taxable' => (bool) $item->item?->is_taxable,
                'approved_by' => $item->approved_by,
            ])->all();
            $invoiceDate = CarbonImmutable::parse($receipt->invoice_date->toDateString());
            $totals = $this->calculateTotals($lines, (string) $receipt->discount_amount, $invoiceDate);

            foreach ($totals['lines'] as $index => $line) {
                $this->assertExpiryApproval($line, $invoiceDate, (int) ($line['approved_by'] ?? 0));
            }

            $receipt->forceFill([
                'total_before_tax' => $totals['total_before_tax'],
                'tax_amount' => $totals['tax_amount'],
                'total_after_tax' => $totals['total_after_tax'],
                'updated_by' => Auth::id(),
            ])->save();

            $itemCosts = [];

            foreach ($totals['lines'] as $line) {
                /** @var GoodsReceiptItem $receiptItem */
                $receiptItem = $line['receipt_item'];
                /** @var Item $item */
                $item = $line['item'];
                $batch = ItemBatch::query()
                    ->where('item_id', $item->id)
                    ->where('batch_no', $line['batch_no'])
                    ->whereDate('expiry_date', $line['expiry_date'])
                    ->lockForUpdate()
                    ->first();
                $unitCostBase = $this->roundDecimal(
                    bcdiv($line['net_total'], $line['qty_base'], 8),
                    4,
                );

                if (bccomp($unitCostBase, self::MAX_UNIT_COST, 4) > 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'Harga bersih per satuan dasar melebihi batas yang dapat dicatat.',
                    ]);
                }

                if (! $batch) {
                    $batch = new ItemBatch;
                    $batch->fill([
                        'item_id' => $item->id,
                        'goods_receipt_item_id' => $receiptItem->id,
                        'batch_no' => $line['batch_no'],
                        'expiry_date' => $line['expiry_date'],
                        'unit_cost_base' => $unitCostBase,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    $batch->save();
                } else {
                    $oldQuantity = bcadd((string) $batch->qty_on_hand, '0', 3);
                    $newQuantity = bcadd($oldQuantity, $line['qty_base'], 3);
                    $oldValue = bcmul($oldQuantity, (string) ($batch->unit_cost_base ?? '0'), 8);
                    $newValue = bcadd($oldValue, $line['net_total'], 8);
                    $weightedUnitCost = $batch->unit_cost_base === null || bccomp($oldQuantity, '0', 3) <= 0
                        ? $unitCostBase
                        : $this->roundDecimal(bcdiv($newValue, $newQuantity, 8), 4);

                    $batch->forceFill([
                        'goods_receipt_item_id' => $batch->goods_receipt_item_id ?? $receiptItem->id,
                        'unit_cost_base' => $weightedUnitCost,
                        'updated_by' => Auth::id(),
                    ])->save();
                }

                $this->stockService->increase(
                    $batch,
                    $line['qty_base'],
                    'receipt',
                    'Penerimaan barang '.$receipt->receipt_no,
                    GoodsReceipt::class,
                    (int) $receipt->id,
                );

                $receiptItem->forceFill(['updated_by' => Auth::id()])->save();

                if (! isset($itemCosts[$item->id])) {
                    $itemCosts[$item->id] = ['cost' => '0.00', 'quantity' => '0.000', 'item' => $item];
                }

                $itemCosts[$item->id]['cost'] = bcadd($itemCosts[$item->id]['cost'], $line['net_total'], 2);
                $itemCosts[$item->id]['quantity'] = bcadd($itemCosts[$item->id]['quantity'], $line['qty_base'], 3);
            }

            foreach ($itemCosts as $itemCost) {
                /** @var Item $item */
                $item = Item::query()->lockForUpdate()->findOrFail($itemCost['item']->id);
                $hnaBase = $this->roundDecimal(
                    bcdiv($itemCost['cost'], $itemCost['quantity'], 8),
                    4,
                );
                $item->forceFill([
                    'hna_base' => $hnaBase,
                    'updated_by' => Auth::id(),
                ])->save();
                $this->pricingService->recalculateItem($item, $invoiceDate);
            }

            if ($receipt->payment_method === 'kredit') {
                if ($receipt->due_date === null) {
                    throw ValidationException::withMessages(['due_date' => 'Tanggal jatuh tempo wajib diisi untuk pembelian kredit.']);
                }

                Payable::query()->create([
                    'supplier_id' => $receipt->supplier_id,
                    'goods_receipt_id' => $receipt->id,
                    'invoice_no' => $receipt->invoice_no,
                    'total_amount' => $totals['total_after_tax'],
                    'paid_amount' => '0.00',
                    'due_date' => $receipt->due_date,
                    'status' => 'belum_dibayar',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            } else {
                $this->postCashPurchase($receipt, $totals['total_after_tax'], $cashBankAccountId);
            }

            $this->taxService->recordCalculatedInputTax(
                $receipt,
                $totals['tax_rate'],
                $totals['taxable_amount'],
                $totals['tax_amount'],
                $invoiceDate,
            );

            GoodsReceipt::duringConfirmation(function () use ($receipt): void {
                $receipt->forceFill([
                    'status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'updated_by' => Auth::id(),
                ])->save();
            });

            return $receipt->fresh(['supplier', 'items.item', 'payable']);
        }, attempts: 5);
    }

    /** @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    private function prepareLines(array $lines, CarbonImmutable $invoiceDate): array
    {
        $prepared = [];
        $canApproveExpiry = Auth::user()?->can('inventory.receipt.approve_expiry') ?? false;

        foreach ($lines as $index => $line) {
            $item = Item::query()->lockForUpdate()->find((int) $line['item_id']);
            $itemUnit = ItemUnit::query()
                ->where('item_id', (int) $line['item_id'])
                ->where('id', (int) $line['item_unit_id'])
                ->lockForUpdate()
                ->first();

            if (! $item?->is_active || ! $itemUnit) {
                throw ValidationException::withMessages([
                    "lines.{$index}.item_unit_id" => 'Barang aktif dan satuan yang terdaftar untuk barang tersebut wajib dipilih.',
                ]);
            }

            $quantity = $this->positiveDecimal((string) $line['qty_received'], 3, "lines.{$index}.qty_received");
            $quantityBaseRaw = bcmul($quantity, (string) $itemUnit->factor_to_base, 6);
            $quantityBase = bcadd($quantityBaseRaw, '0', 3);

            if (bccomp($quantityBaseRaw, $quantityBase, 6) !== 0
                || bccomp($quantityBase, '0', 3) <= 0
                || bccomp($quantityBase, '999999999999.999', 3) > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.qty_received" => 'Hasil konversi satuan harus positif dengan maksimal tiga angka desimal.',
                ]);
            }

            $expiryDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $line['expiry_date']);
            $previousApproval = isset($line['_previous_approved_by']) ? (int) $line['_previous_approved_by'] : null;
            $approvedBy = $this->assertExpiryApproval($line, $invoiceDate, $previousApproval, $canApproveExpiry, $expiryDate);
            $unitPrice = $this->positiveDecimal((string) $line['unit_price'], 2, "lines.{$index}.unit_price");
            $discountAmount = $this->money((string) ($line['discount_amount'] ?? '0'));
            $lineTotal = $this->roundDecimal(bcmul($quantity, $unitPrice, 8), 2);

            if (bccomp($lineTotal, self::MAX_MONEY, 2) > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.unit_price" => 'Nilai baris melebihi batas nominal yang dapat dicatat.',
                ]);
            }

            if (bccomp($discountAmount, $lineTotal, 2) > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.discount_amount" => 'Diskon baris tidak boleh melebihi nilai barang pada baris tersebut.',
                ]);
            }

            $prepared[] = [
                'item' => $item,
                'item_id' => (int) $item->id,
                'unit_id' => (int) $itemUnit->unit_id,
                'qty_received' => $quantity,
                'qty_base' => $quantityBase,
                'batch_no' => trim((string) $line['batch_no']),
                'expiry_date' => $expiryDate->toDateString(),
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'line_total' => bcsub($lineTotal, $discountAmount, 2),
                'is_taxable' => (bool) $item->is_taxable,
                'approved_by' => $approvedBy,
                'match_status' => 'tidak_dipesan',
            ];
        }

        return $prepared;
    }

    /** @param array<int, array<string, mixed>> $lines
     * @return array{lines: array<int, array<string, mixed>>, total_before_tax: string, taxable_amount: string, tax_rate: string, tax_amount: string, total_after_tax: string}
     */
    private function calculateTotals(array $lines, string $invoiceDiscount, CarbonImmutable $invoiceDate): array
    {
        $invoiceDiscount = $this->money($invoiceDiscount);
        $subtotal = '0.00';

        foreach ($lines as $line) {
            $subtotal = bcadd($subtotal, (string) $line['line_total'], 2);
        }

        if (bccomp($invoiceDiscount, $subtotal, 2) > 0) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Diskon faktur tidak boleh melebihi subtotal setelah diskon baris.',
            ]);
        }

        $allocations = [];
        $allocatedTotal = '0.00';

        foreach ($lines as $index => $line) {
            $allocation = bccomp($subtotal, '0', 2) > 0
                ? bcadd(bcdiv(bcmul($invoiceDiscount, (string) $line['line_total'], 8), $subtotal, 8), '0', 2)
                : '0.00';
            $allocations[$index] = $allocation;
            $allocatedTotal = bcadd($allocatedTotal, $allocation, 2);
        }

        $remainingDiscount = bcsub($invoiceDiscount, $allocatedTotal, 2);

        foreach ($lines as $index => $line) {
            if (bccomp($remainingDiscount, '0', 2) <= 0) {
                break;
            }

            $remainingCapacity = bcsub((string) $line['line_total'], $allocations[$index], 2);
            $extraAllocation = bccomp($remainingDiscount, $remainingCapacity, 2) <= 0
                ? $remainingDiscount
                : $remainingCapacity;
            $allocations[$index] = bcadd($allocations[$index], $extraAllocation, 2);
            $remainingDiscount = bcsub($remainingDiscount, $extraAllocation, 2);
        }

        if (bccomp($remainingDiscount, '0', 2) !== 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Diskon faktur tidak dapat dialokasikan ke baris barang.']);
        }

        $taxableAmount = '0.00';
        $totalBeforeTax = '0.00';

        foreach ($lines as $index => $line) {
            $line['invoice_discount_amount'] = $allocations[$index];
            $line['net_total'] = bcsub((string) $line['line_total'], $allocations[$index], 2);
            $totalBeforeTax = bcadd($totalBeforeTax, $line['net_total'], 2);

            if ($line['is_taxable']) {
                $taxableAmount = bcadd($taxableAmount, $line['net_total'], 2);
            }

            $lines[$index] = $line;
        }

        if (bccomp($totalBeforeTax, self::MAX_MONEY, 2) > 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Total penerimaan melebihi batas nominal yang dapat dicatat.']);
        }

        if (bccomp($totalBeforeTax, '0', 2) <= 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Total penerimaan setelah diskon harus lebih dari nol.']);
        }

        $taxRate = '0.00';
        $taxAmount = '0.00';

        if (bccomp($taxableAmount, '0', 2) > 0) {
            $taxRate = $this->taxService->rateForDate($invoiceDate) ?? throw ValidationException::withMessages([
                'tax_setting' => 'Atur tarif PPN yang berlaku sebelum mengonfirmasi penerimaan barang kena pajak.',
            ]);
            $taxAmount = $this->roundDecimal(bcdiv(bcmul($taxableAmount, $taxRate, 8), '100', 8), 2);
        }

        $totalAfterTax = bcadd($totalBeforeTax, $taxAmount, 2);

        if (bccomp($totalAfterTax, self::MAX_MONEY, 2) > 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Total penerimaan setelah PPN melebihi batas nominal yang dapat dicatat.']);
        }

        return [
            'lines' => $lines,
            'total_before_tax' => $totalBeforeTax,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_after_tax' => $totalAfterTax,
        ];
    }

    /** @param array<string, mixed> $line */
    private function assertExpiryApproval(
        array $line,
        CarbonImmutable $invoiceDate,
        ?int $approvedBy,
        ?bool $canApprove = null,
        ?CarbonImmutable $expiryDate = null,
    ): ?int {
        $expiryDate ??= CarbonImmutable::parse((string) $line['expiry_date']);
        $requiresApproval = $expiryDate->lt(today()) || $expiryDate->lt($invoiceDate);

        if (! $requiresApproval) {
            return null;
        }

        $canApprove ??= Auth::user()?->can('inventory.receipt.approve_expiry') ?? false;
        $approvalRequested = filter_var($line['approve_expiry'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($approvedBy !== null && $approvedBy > 0) {
            return $approvedBy;
        }

        if (! $approvalRequested || ! $canApprove) {
            throw ValidationException::withMessages([
                'expiry_date' => 'ED yang sudah lewat atau lebih awal dari tanggal faktur memerlukan persetujuan apoteker.',
            ]);
        }

        return Auth::id();
    }

    /** @param array<string, mixed> $data */
    private function resolveDueDate(array $data, Supplier $supplier): ?string
    {
        if ($data['payment_method'] !== 'kredit') {
            return null;
        }

        if (filled($data['due_date'] ?? null)) {
            return CarbonImmutable::parse((string) $data['due_date'])->toDateString();
        }

        if ($supplier->payment_term_days === null) {
            throw ValidationException::withMessages(['due_date' => 'Tanggal jatuh tempo wajib diisi untuk pembelian kredit.']);
        }

        return CarbonImmutable::parse((string) $data['invoice_date'])
            ->addDays((int) $supplier->payment_term_days)
            ->toDateString();
    }

    /** @param array<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    private function preserveExistingExpiryApprovals(
        GoodsReceipt $receipt,
        array $lines,
        CarbonImmutable $invoiceDate,
    ): array {
        if ($receipt->invoice_date->toDateString() !== $invoiceDate->toDateString()) {
            return $lines;
        }

        $approvedItems = $receipt->items()->whereNotNull('approved_by')->get();

        foreach ($lines as $index => $line) {
            if (filter_var($line['approve_expiry'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $approvedItem = $approvedItems->first(fn (GoodsReceiptItem $item): bool => (int) $item->item_id === (int) $line['item_id']
                && $item->batch_no === trim((string) $line['batch_no'])
                && $item->expiry_date->toDateString() === (string) $line['expiry_date']
            );

            if ($approvedItem) {
                $lines[$index]['_previous_approved_by'] = (int) $approvedItem->approved_by;
            }
        }

        return $lines;
    }

    /** @param array<string, mixed> $line */
    private function createReceiptItem(GoodsReceipt $receipt, array $line): GoodsReceiptItem
    {
        $receiptItem = new GoodsReceiptItem;
        $receiptItem->fill([
            'goods_receipt_id' => $receipt->id,
            'item_id' => $line['item_id'],
            'unit_id' => $line['unit_id'],
            'qty_received' => $line['qty_received'],
            'qty_base' => $line['qty_base'],
            'batch_no' => $line['batch_no'],
            'expiry_date' => $line['expiry_date'],
            'unit_price' => $line['unit_price'],
            'discount_amount' => $line['discount_amount'],
            'line_total' => $line['line_total'],
            'match_status' => $line['match_status'],
            'approved_by' => $line['approved_by'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        $receiptItem->save();

        return $receiptItem;
    }

    private function postCashPurchase(GoodsReceipt $receipt, string $amount, ?int $cashBankAccountId): void
    {
        if ($cashBankAccountId === null) {
            throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas/bank wajib dipilih untuk penerimaan tunai.']);
        }

        $account = CashBankAccount::query()->find($cashBankAccountId);
        $category = TransactionCategory::query()
            ->where('source_code', 'PURCHASE')
            ->where('direction', 'out')
            ->first();

        if (! $account?->is_active) {
            throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas/bank tidak ditemukan atau sudah nonaktif.']);
        }

        if (! $category?->is_active) {
            throw ValidationException::withMessages(['category_id' => 'Kategori transaksi pembelian aktif tidak ditemukan.']);
        }

        $this->financialPostingService->post([
            'cash_bank_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => $amount,
            'description' => 'Penerimaan barang '.$receipt->receipt_no.' - Faktur '.$receipt->invoice_no,
            'trx_date' => $receipt->invoice_date,
            'direction' => 'out',
            'ref_type' => GoodsReceipt::class,
            'ref_id' => (int) $receipt->id,
        ]);
    }

    private function positiveDecimal(string $value, int $precision, string $field): string
    {
        $maximum = match ($precision) {
            2 => self::MAX_MONEY,
            3 => '999999999999.999',
            default => throw new InvalidArgumentException('Presisi desimal tidak didukung.'),
        };

        if (! preg_match('/^\d+(?:\.\d{1,'.$precision.'})?$/', $value)
            || bccomp($value, '0', $precision) <= 0
            || bccomp($value, $maximum, $precision) > 0) {
            throw ValidationException::withMessages([$field => 'Nilai harus positif dengan presisi desimal yang valid.']);
        }

        return bcadd($value, '0', $precision);
    }

    private function money(string $value): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value) || bccomp($value, '0', 2) < 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Nilai uang tidak valid.']);
        }

        if (bccomp($value, self::MAX_MONEY, 2) > 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Nilai melebihi batas nominal yang dapat dicatat.']);
        }

        return bcadd($value, '0', 2);
    }

    private function roundDecimal(string $value, int $precision): string
    {
        $halfUnit = '0.'.str_repeat('0', $precision).'5';

        return bcadd($value, $halfUnit, $precision);
    }
}
