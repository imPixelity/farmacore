<?php

namespace App\Modules\Pos\Services;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Services\DiscountService;
use App\Modules\Md\Services\PricingService;
use App\Modules\Md\Services\TaxService;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleItem;
use App\Modules\Pos\Models\SaleItemBatch;
use App\Modules\Pos\Models\SaleService as SaleHealthService;
use App\Modules\Rx\Models\Prescription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /** @var list<string> */
    private const SALES_TYPES = ['resep', 'obat_bebas', 'produk_lain'];

    /** @var list<string> */
    private const PAYMENT_METHODS = ['tunai', 'non_tunai', 'kredit'];

    public function __construct(
        private PricingService $pricing,
        private DiscountService $discounts,
        private TaxService $taxes,
        private StockService $stock,
        private FinancialPostingService $financialPosting,
    ) {}

    /**
     * @param  array{sale_type: string, cart: array<string, array<string, mixed>>, patient_id?: int|string|null, prescription_id?: int|string|null, doctor_fee?: string|int|float, tuslah_fee?: string|int|float, payments: array<int, array<string, mixed>>}  $data
     */
    public function checkout(array $data): Sale
    {
        $this->assertSaleType((string) $data['sale_type']);
        $this->positiveMoney((string) ($data['doctor_fee'] ?? '0.00'), 'doctor_fee', allowZero: true);
        $this->positiveMoney((string) ($data['tuslah_fee'] ?? '0.00'), 'tuslah_fee', allowZero: true);

        return DB::transaction(function () use ($data): Sale {
            $saleType = (string) $data['sale_type'];
            $prescription = $this->resolvePrescription($saleType, isset($data['prescription_id']) ? (int) $data['prescription_id'] : null);
            $patient = $this->resolvePatient(
                isset($data['patient_id']) ? (int) $data['patient_id'] : null,
                $prescription,
                $data['payments'],
                $saleType,
            );
            $prepared = $this->prepareLines($data['cart'], $saleType, $prescription);
            $serviceRows = $this->prepareServices($data['services'] ?? []);
            $healthServiceFee = '0.00';
            foreach ($serviceRows as $serviceRow) {
                $healthServiceFee = bcadd($healthServiceFee, $serviceRow['fee'], 2);
            }
            $doctorFee = $this->positiveMoney((string) ($data['doctor_fee'] ?? '0.00'), 'doctor_fee', allowZero: true);
            $tuslahFee = $this->positiveMoney((string) ($data['tuslah_fee'] ?? '0.00'), 'tuslah_fee', allowZero: true);

            if ($saleType !== 'resep' && (bccomp($doctorFee, '0.00', 2) > 0 || bccomp($tuslahFee, '0.00', 2) > 0)) {
                throw ValidationException::withMessages(['doctor_fee' => 'Jasa dokter dan tuslah hanya dapat ditambahkan pada penjualan resep.']);
            }

            $subtotal = '0.00';
            $discountTotal = '0.00';
            $taxAmount = '0.00';
            $taxableAmount = '0.00';
            $taxRate = '0.00';

            foreach ($prepared as $line) {
                $subtotal = bcadd($subtotal, $line['subtotal'], 2);
                $discountTotal = bcadd($discountTotal, $line['discount_amount'], 2);
                $taxAmount = bcadd($taxAmount, $line['tax_amount'], 2);
                $taxableAmount = bcadd($taxableAmount, $line['taxable_amount'], 2);
                $taxRate = $line['tax_rate'];
            }

            $grandTotal = bcadd(bcadd(bcadd($subtotal, $doctorFee, 2), $tuslahFee, 2), $healthServiceFee, 2);
            $payments = $this->preparePayments($data['payments'], $grandTotal, $patient !== null);
            $invoiceNo = 'INV-'.Str::ulid();
            $sale = new Sale;
            $sale->fill([
                'invoice_no' => $invoiceNo,
                'sale_type' => $saleType,
                'sale_date' => now(),
                'cashier_id' => Auth::id(),
                'patient_id' => $patient?->id,
                'prescription_id' => $prescription?->id,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'doctor_fee' => $doctorFee,
                'tuslah_fee' => $tuslahFee,
                'service_fee_total' => bcadd(bcadd($doctorFee, $tuslahFee, 2), $healthServiceFee, 2),
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'payment_status' => $payments['payment_status'],
                'status' => 'completed',
            ]);
            $sale->save();

            foreach ($serviceRows as $serviceRow) {
                SaleHealthService::query()->create([
                    'sale_id' => $sale->id,
                    'health_service_id' => $serviceRow['health_service_id'],
                    'service_name' => $serviceRow['service_name'],
                    'fee' => $serviceRow['fee'],
                    'result_note' => $serviceRow['result_note'],
                ]);
            }

            foreach ($prepared as $line) {
                $saleItem = new SaleItem;
                $saleItem->fill([
                    'sale_id' => $sale->id,
                    'item_id' => $line['item']->id,
                    'unit_id' => $line['item']->base_unit_id,
                    'qty_input' => $line['quantity'],
                    'qty_base' => $line['quantity'],
                    'unit_price_base' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['total'],
                ]);
                $saleItem->save();
                $this->allocateAndDecrease($saleItem, $line['item'], $line['quantity'], $sale->invoice_no);
            }

            $remainingDoctorFee = $doctorFee;
            $remainingHealthServiceFee = $healthServiceFee;

            foreach ($payments['lines'] as $payment) {
                $salePayment = $sale->payments()->create([
                    'method' => $payment['method'],
                    'channel' => $payment['channel'],
                    'amount' => $payment['amount'],
                    'cash_received' => $payment['cash_received'],
                    'change_amount' => $payment['change_amount'],
                    'cash_bank_account_id' => $payment['cash_bank_account_id'],
                    'paid_at' => now(),
                ]);

                if ($payment['method'] !== 'kredit') {
                    [$remainingDoctorFee, $remainingHealthServiceFee] = $this->postPaymentRevenue(
                        $sale,
                        $salePayment,
                        $payment['amount'],
                        $remainingDoctorFee,
                        $remainingHealthServiceFee,
                    );
                }
            }

            if ($payments['credit_amount'] !== '0.00') {
                Receivable::query()->create([
                    'sale_id' => $sale->id,
                    'patient_id' => $patient->id,
                    'total_amount' => $payments['credit_amount'],
                    'paid_amount' => '0.00',
                    'status' => 'belum_dibayar',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            if (bccomp($taxableAmount, '0.00', 2) > 0) {
                $this->taxes->recordCalculatedOutputTax($sale, $taxRate, $taxableAmount, $taxAmount, now());
            }

            return $sale->fresh(['items.batches.itemBatch', 'payments', 'services', 'patient', 'prescription', 'receivable', 'taxRecords']);
        }, attempts: 5);
    }

    /** @param array<string, array<string, mixed>> $cart
     * @return list<array{item: Item, quantity: string, unit_price: string, subtotal: string, discount_amount: string, total: string, tax_amount: string, taxable_amount: string, tax_rate: string}>
     */
    private function prepareLines(array $cart, string $saleType, ?Prescription $prescription): array
    {
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => 'Keranjang penjualan tidak boleh kosong.']);
        }

        $rawLines = [];
        $itemsById = [];

        foreach ($cart as $index => $line) {
            if (! is_array($line) || ! isset($line['item_id'], $line['quantity']) || ! is_numeric($line['item_id'])) {
                throw ValidationException::withMessages(["cart.{$index}" => 'Baris keranjang tidak valid.']);
            }

            $item = Item::query()->with('baseUnit')->lockForUpdate()->find((int) $line['item_id']);
            if (! $item?->is_active) {
                throw ValidationException::withMessages(["cart.{$index}" => 'Barang aktif wajib dipilih.']);
            }

            $quantity = $this->positiveDecimal((string) $line['quantity'], "cart.{$index}.quantity");
            $prescriptionItemId = isset($line['prescription_item_id']) ? (int) $line['prescription_item_id'] : null;
            $this->assertPrescriptionLine($item, $quantity, $prescriptionItemId, $prescription);
            $price = $this->pricing->currentPrice($item, $saleType);

            if (! $price) {
                throw ValidationException::withMessages(["cart.{$index}" => "Harga {$item->name} belum tersedia untuk jenis penjualan ini."]);
            }

            $itemsById[$item->id] = $item;
            $rawLines[] = [
                'item_id' => $item->id,
                'category_id' => $item->category_id,
                'quantity' => $quantity,
                'unit_price' => (string) $price->selling_price,
            ];
        }

        $discount = $this->discounts->apply($rawLines, $saleType);
        $prepared = [];

        foreach ($discount['lines'] as $line) {
            $item = $itemsById[$line['item_id']];
            $tax = $this->lineTax($item, $line['total']);
            $prepared[] = [
                'item' => $item,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
                'discount_amount' => $line['discount_amount'],
                'total' => $line['total'],
                'tax_amount' => $tax['tax_amount'],
                'taxable_amount' => $tax['taxable_amount'],
                'tax_rate' => $tax['rate_percent'],
            ];
        }

        return $prepared;
    }

    /** @return array{rate_percent: string, taxable_amount: string, tax_amount: string} */
    private function lineTax(Item $item, string $grossAmount): array
    {
        if (! $item->is_taxable) {
            return ['rate_percent' => '0.00', 'taxable_amount' => '0.00', 'tax_amount' => '0.00'];
        }

        $rate = $this->taxes->rateForDate(now());
        if ($rate === null) {
            throw ValidationException::withMessages(['tax_setting' => 'Atur tarif PPN sebelum menjual barang kena pajak.']);
        }

        return $this->taxes->calculateIncludedTax($grossAmount, $rate);
    }

    /**
     * @param  array<int, array<string, mixed>>  $services
     * @return array<int, array{health_service_id: int, service_name: string, fee: string, result_note: string|null}>
     */
    private function prepareServices(array $services): array
    {
        $prepared = [];

        foreach ($services as $index => $service) {
            $serviceId = (int) ($service['health_service_id'] ?? 0);
            $healthService = HealthService::query()->active()->find($serviceId);
            if (! $healthService) {
                throw ValidationException::withMessages(["services.{$index}.health_service_id" => 'Layanan pemeriksaan tidak ditemukan atau tidak aktif.']);
            }
            $fee = $this->positiveMoney((string) ($service['fee'] ?? $healthService->default_fee), "services.{$index}.fee", allowZero: true);
            $resultNote = filled($service['result_note'] ?? null) ? trim((string) $service['result_note']) : null;
            if ($resultNote !== null && mb_strlen($resultNote) > 5000) {
                throw ValidationException::withMessages(["services.{$index}.result_note" => 'Catatan hasil pemeriksaan terlalu panjang.']);
            }
            $prepared[] = [
                'health_service_id' => $healthService->id,
                'service_name' => $healthService->name,
                'fee' => $fee,
                'result_note' => $resultNote,
            ];
        }

        return $prepared;
    }

    private function allocateAndDecrease(SaleItem $saleItem, Item $item, string $quantity, string $invoiceNo): void
    {
        $remaining = $quantity;
        $batches = ItemBatch::query()
            ->where('item_id', $item->id)
            ->whereDate('expiry_date', '>=', today()->toDateString())
            ->where('qty_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            $allocated = bccomp((string) $batch->qty_on_hand, $remaining, 3) < 0
                ? (string) $batch->qty_on_hand
                : $remaining;

            if (bccomp($allocated, '0', 3) <= 0) {
                continue;
            }

            SaleItemBatch::query()->create([
                'sale_item_id' => $saleItem->id,
                'item_batch_id' => $batch->id,
                'qty_base' => $allocated,
            ]);
            $this->stock->decrease($batch, $allocated, 'sale', "Penjualan {$invoiceNo}", Sale::class, $saleItem->sale_id);
            $remaining = bcsub($remaining, $allocated, 3);

            if (bccomp($remaining, '0', 3) === 0) {
                break;
            }
        }

        if (bccomp($remaining, '0', 3) > 0) {
            throw ValidationException::withMessages([
                'stock' => "Stok {$item->name} tidak mencukupi atau seluruh batch yang tersedia sudah kedaluwarsa.",
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     * @return array{lines: list<array{method: string, channel: string|null, amount: string, cash_received: string, change_amount: string, cash_bank_account_id: int|null}>, credit_amount: string, payment_status: string}
     */
    private function preparePayments(array $payments, string $grandTotal, bool $hasPatient): array
    {
        if ($payments === []) {
            throw ValidationException::withMessages(['payments' => 'Minimal satu metode pembayaran harus dipilih.']);
        }

        $lines = [];
        $paidAmount = '0.00';
        $creditAmount = '0.00';

        foreach ($payments as $index => $payment) {
            $method = (string) ($payment['method'] ?? '');
            if (! in_array($method, self::PAYMENT_METHODS, true)) {
                throw ValidationException::withMessages(["payments.{$index}.method" => 'Metode pembayaran tidak valid.']);
            }

            $amount = $this->positiveMoney((string) ($payment['amount'] ?? '0.00'), "payments.{$index}.amount");
            $accountId = isset($payment['cash_bank_account_id']) && $payment['cash_bank_account_id'] !== ''
                ? (int) $payment['cash_bank_account_id']
                : null;
            $channel = filled($payment['channel'] ?? null) ? trim((string) $payment['channel']) : null;
            $cashReceived = $method === 'tunai'
                ? $this->positiveMoney((string) ($payment['cash_received'] ?? $amount), "payments.{$index}.cash_received", allowZero: true)
                : '0.00';
            $changeAmount = '0.00';

            if ($method === 'tunai') {
                $this->assertAccount($accountId, 'kas', "payments.{$index}.cash_bank_account_id");
                if (bccomp($cashReceived, $amount, 2) < 0) {
                    throw ValidationException::withMessages(["payments.{$index}.cash_received" => 'Uang diterima tidak boleh kurang dari nominal tunai.']);
                }
                $changeAmount = bcsub($cashReceived, $amount, 2);
            } elseif ($method === 'non_tunai') {
                $this->assertAccount($accountId, 'bank', "payments.{$index}.cash_bank_account_id");
                if ($channel === null || mb_strlen($channel) > 30) {
                    throw ValidationException::withMessages(["payments.{$index}.channel" => 'Channel non-tunai wajib diisi.']);
                }
            } else {
                if (! $hasPatient) {
                    throw ValidationException::withMessages(['patient_id' => 'Pasien wajib dipilih untuk penjualan kredit.']);
                }
                if ($accountId !== null || $channel !== null) {
                    throw ValidationException::withMessages(["payments.{$index}" => 'Pembayaran kredit tidak membutuhkan akun atau channel.']);
                }
            }

            if ($method === 'kredit') {
                $creditAmount = bcadd($creditAmount, $amount, 2);
            } else {
                $paidAmount = bcadd($paidAmount, $amount, 2);
            }

            $lines[] = [
                'method' => $method,
                'channel' => $channel,
                'amount' => $amount,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'cash_bank_account_id' => $accountId,
            ];
        }

        if (bccomp(bcadd($paidAmount, $creditAmount, 2), $grandTotal, 2) !== 0) {
            throw ValidationException::withMessages(['payments' => 'Total metode pembayaran harus sama dengan total penjualan.']);
        }

        return [
            'lines' => $lines,
            'credit_amount' => $creditAmount,
            'payment_status' => bccomp($creditAmount, '0.00', 2) === 0 ? 'lunas' : (bccomp($paidAmount, '0.00', 2) > 0 ? 'sebagian' : 'belum_dibayar'),
        ];
    }

    /** @return array{0: string, 1: string} */
    private function postPaymentRevenue(Sale $sale, object $payment, string $amount, string $doctorFee, string $healthServiceFee): array
    {
        $doctorPortion = bccomp($doctorFee, $amount, 2) < 0 ? $doctorFee : $amount;
        $remainingAmount = bcsub($amount, $doctorPortion, 2);
        $healthPortion = bccomp($healthServiceFee, $remainingAmount, 2) < 0 ? $healthServiceFee : $remainingAmount;
        $salePortion = bcsub($remainingAmount, $healthPortion, 2);

        if (bccomp($salePortion, '0.00', 2) > 0) {
            $this->postRevenue($sale, $payment->cash_bank_account_id, $salePortion, $this->saleSourceCode($sale->sale_type));
        }
        if (bccomp($doctorPortion, '0.00', 2) > 0) {
            $this->postRevenue($sale, $payment->cash_bank_account_id, $doctorPortion, 'DOCTOR_FEE');
        }
        if (bccomp($healthPortion, '0.00', 2) > 0) {
            $this->postRevenue($sale, $payment->cash_bank_account_id, $healthPortion, 'HEALTH_SERVICE');
        }

        return [bcsub($doctorFee, $doctorPortion, 2), bcsub($healthServiceFee, $healthPortion, 2)];
    }

    private function postRevenue(Sale $sale, int $accountId, string $amount, string $sourceCode): void
    {
        $category = TransactionCategory::query()->where('source_code', $sourceCode)->where('direction', 'in')->first();
        if (! $category) {
            throw ValidationException::withMessages(['payment' => "Kategori pemasukan {$sourceCode} belum tersedia."]);
        }

        $this->financialPosting->post([
            'cash_bank_account_id' => $accountId,
            'category_id' => $category->id,
            'amount' => $amount,
            'description' => "Penjualan {$sale->invoice_no}",
            'trx_date' => now(),
            'direction' => 'in',
            'ref_type' => Sale::class,
            'ref_id' => $sale->id,
        ]);
    }

    private function resolvePrescription(string $saleType, ?int $prescriptionId): ?Prescription
    {
        if ($saleType !== 'resep' && $prescriptionId !== null) {
            throw ValidationException::withMessages(['prescription_id' => 'Resep hanya dapat dipilih pada penjualan resep.']);
        }

        if ($saleType === 'resep' && $prescriptionId === null) {
            throw ValidationException::withMessages(['prescription_id' => 'Resep terverifikasi wajib dipilih.']);
        }

        if ($prescriptionId === null) {
            return null;
        }

        $prescription = Prescription::query()->with(['patient', 'items'])->lockForUpdate()->find($prescriptionId);
        if (! $prescription || ! in_array($prescription->status, ['verified', 'prepared'], true)) {
            throw ValidationException::withMessages(['prescription_id' => 'Resep harus berstatus terverifikasi atau siap diserahkan.']);
        }

        return $prescription;
    }

    /** @param array<int, array<string, mixed>> $payments */
    private function resolvePatient(?int $patientId, ?Prescription $prescription, array $payments, string $saleType): ?Patient
    {
        $creditSelected = collect($payments)->contains(fn (array $payment): bool => ($payment['method'] ?? null) === 'kredit');
        $patientId ??= $prescription?->patient_id;

        if (($saleType === 'resep' || $creditSelected) && $patientId === null) {
            throw ValidationException::withMessages(['patient_id' => 'Pasien wajib dipilih untuk penjualan resep atau kredit.']);
        }

        if ($patientId === null) {
            return null;
        }

        $patient = Patient::query()->lockForUpdate()->find($patientId);
        if (! $patient?->is_active) {
            throw ValidationException::withMessages(['patient_id' => 'Pasien aktif wajib dipilih.']);
        }

        if ($prescription && $prescription->patient_id !== $patient->id) {
            throw ValidationException::withMessages(['patient_id' => 'Pasien harus sama dengan pasien pada resep.']);
        }

        return $patient;
    }

    private function assertPrescriptionLine(Item $item, string $quantity, ?int $prescriptionItemId, ?Prescription $prescription): void
    {
        if ($prescriptionItemId === null) {
            return;
        }

        if (! $prescription) {
            throw ValidationException::withMessages(['cart' => 'Baris resep memerlukan resep yang dipilih.']);
        }

        $prescriptionItem = $prescription->items->firstWhere('id', $prescriptionItemId);
        if (! $prescriptionItem || $prescriptionItem->item_id !== $item->id) {
            throw ValidationException::withMessages(['cart' => 'Item tidak sesuai dengan resep yang dipilih.']);
        }

        if (bccomp($quantity, (string) $prescriptionItem->qty_prescribed, 3) > 0) {
            throw ValidationException::withMessages(['cart' => 'Jumlah item resep melebihi jumlah yang diresepkan.']);
        }
    }

    private function assertAccount(?int $accountId, string $type, string $field): void
    {
        if ($accountId === null) {
            throw ValidationException::withMessages([$field => 'Akun kas/bank wajib dipilih.']);
        }

        $account = CashBankAccount::query()->find($accountId);
        if (! $account?->is_active || $account->type !== $type) {
            throw ValidationException::withMessages([$field => "Akun aktif bertipe {$type} wajib dipilih."]);
        }
    }

    private function saleSourceCode(string $saleType): string
    {
        return match ($saleType) {
            'resep' => 'SALE_RX',
            'obat_bebas' => 'SALE_OTC',
            'produk_lain' => 'SALE_NONDRUG',
        };
    }

    private function assertSaleType(string $saleType): void
    {
        if (! in_array($saleType, self::SALES_TYPES, true)) {
            throw ValidationException::withMessages(['sale_type' => 'Jenis penjualan tidak valid.']);
        }
    }

    private function positiveDecimal(string $value, string $field): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $value) || bccomp($value, '0', 3) <= 0) {
            throw ValidationException::withMessages([$field => 'Jumlah harus positif dengan maksimal tiga angka desimal.']);
        }

        return bcadd($value, '0', 3);
    }

    private function positiveMoney(string $value, string $field, bool $allowZero = false): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)
            || bccomp($value, '9999999999999.99', 2) > 0
            || bccomp($value, '0', 2) < 0
            || (! $allowZero && bccomp($value, '0', 2) <= 0)) {
            throw ValidationException::withMessages([$field => 'Nominal harus valid dengan maksimal dua angka desimal.']);
        }

        return bcadd($value, '0', 2);
    }
}
