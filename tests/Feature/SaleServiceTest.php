<?php

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\TaxRecord;
use App\Modules\Md\Models\TaxSetting;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleItemBatch;
use App\Modules\Pos\Services\SaleService;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);

    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);
});

function checkoutItem(string $price = '100.00', string $stock = '10.000', bool $taxable = false): array
{
    $item = Item::factory()->create(['is_taxable' => $taxable]);
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => $price,
    ]);
    $batch = ItemBatch::factory()->for($item)->create(['expiry_date' => today()->addMonths(6)]);
    app(StockService::class)->increase($batch, $stock, 'receipt', 'Stok test');

    return [$item->fresh(), $batch->fresh()];
}

function cashPayment(CashBankAccount $account, string $amount, ?string $cashReceived = null): array
{
    return [
        'method' => 'tunai',
        'amount' => $amount,
        'cash_received' => $cashReceived ?? $amount,
        'cash_bank_account_id' => $account->id,
    ];
}

it('completes a cash sale with FEFO allocation and change', function () {
    [$item, $firstBatch] = checkoutItem('100.00', '5.000');
    $secondBatch = ItemBatch::factory()->for($item)->create(['expiry_date' => today()->addMonths(12)]);
    app(StockService::class)->increase($secondBatch, '5.000', 'receipt', 'Stok test kedua');
    $account = CashBankAccount::factory()->create();

    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '6.000']],
        'payments' => [cashPayment($account, '600.00', '650.00')],
    ]);

    expect($sale->grand_total)->toBe('600.00');
    expect($sale->payment_status)->toBe('lunas');
    expect($sale->payments->first()->change_amount)->toBe('50.00');
    expect(SaleItemBatch::query()->where('sale_item_id', $sale->items->first()->id)->orderBy('item_batch_id')->pluck('qty_base')->all())->toBe(['5.000', '1.000']);
    expect($firstBatch->fresh()->qty_on_hand)->toBe('0.000');
    expect($secondBatch->fresh()->qty_on_hand)->toBe('4.000');
    expect($account->fresh()->current_balance)->toBe('600.00');
    expect(FinancialTransaction::query()->where('ref_id', $sale->id)->count())->toBe(1);
});

it('rolls back the sale and stock when FEFO cannot satisfy the requested quantity', function () {
    [$item, $validBatch] = checkoutItem('100.00', '1.000');
    $expiredBatch = ItemBatch::factory()->for($item)->create(['expiry_date' => today()->subDay()]);
    app(StockService::class)->increase($expiredBatch, '5.000', 'receipt', 'Stok kedaluwarsa test');
    $account = CashBankAccount::factory()->create();

    expect(fn () => app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '2.000']],
        'payments' => [cashPayment($account, '200.00')],
    ]))->toThrow(ValidationException::class);

    expect(Sale::query()->count())->toBe(0);
    expect($validBatch->fresh()->qty_on_hand)->toBe('1.000');
    expect($expiredBatch->fresh()->qty_on_hand)->toBe('5.000');
    expect(FinancialTransaction::query()->count())->toBe(0);
});

it('splits doctor fee revenue once across combined payment methods', function () {
    [$item] = checkoutItem('1000.00', '2.000');
    ItemPrice::factory()->for($item)->create(['sales_type' => 'resep', 'selling_price' => '1000.00']);
    $prescription = Prescription::factory()->create();
    Prescription::duringWorkflow(function () use ($prescription): void {
        $prescription->forceFill(['status' => 'verified'])->save();
    });
    $prescriptionItem = PrescriptionItem::factory()->for($prescription)->for($item)->create(['unit_id' => $item->base_unit_id]);
    $cashAccount = CashBankAccount::factory()->create(['type' => 'kas']);
    $bankAccount = CashBankAccount::factory()->create(['type' => 'bank']);

    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'resep',
        'patient_id' => $prescription->patient_id,
        'prescription_id' => $prescription->id,
        'doctor_fee' => '60.00',
        'tuslah_fee' => '0.00',
        'cart' => ['rx-item-'.$prescriptionItem->id => ['item_id' => $item->id, 'quantity' => '1.000', 'prescription_item_id' => $prescriptionItem->id]],
        'payments' => [
            cashPayment($cashAccount, '530.00'),
            [
                'method' => 'non_tunai',
                'channel' => 'QRIS',
                'amount' => '530.00',
                'cash_bank_account_id' => $bankAccount->id,
            ],
        ],
    ]);

    $doctorCategory = TransactionCategory::query()->where('source_code', 'DOCTOR_FEE')->firstOrFail();
    $saleCategory = TransactionCategory::query()->where('source_code', 'SALE_RX')->firstOrFail();

    expect($sale->payment_status)->toBe('lunas');
    expect(FinancialTransaction::query()->where('category_id', $doctorCategory->id)->sum('amount'))->toBe(60);
    expect(FinancialTransaction::query()->where('category_id', $saleCategory->id)->sum('amount'))->toBe(1000);
});

it('creates a receivable for a credit sale without posting cash income', function () {
    [$item] = checkoutItem('250.00', '5.000');
    $patient = Patient::factory()->create();

    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'patient_id' => $patient->id,
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '2.000']],
        'payments' => [[
            'method' => 'kredit',
            'amount' => '500.00',
        ]],
    ]);

    expect($sale->payment_status)->toBe('belum_dibayar');
    expect($sale->receivable->total_amount)->toBe('500.00');
    expect($sale->receivable->patient_id)->toBe($patient->id);
    expect(Receivable::query()->where('sale_id', $sale->id)->count())->toBe(1);
    expect(FinancialTransaction::query()->count())->toBe(0);
});

it('snapshots included output tax on taxable sale lines', function () {
    TaxSetting::factory()->create(['effective_from' => today(), 'rate_percent' => '11.00']);
    [$item] = checkoutItem('1000.00', '2.000', taxable: true);
    $account = CashBankAccount::factory()->create();

    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '1.000']],
        'payments' => [cashPayment($account, '1000.00')],
    ]);

    expect($sale->tax_amount)->toBe('99.10');
    expect(TaxRecord::query()->where('source_id', $sale->id)->where('direction', 'output')->value('tax_amount'))->toBe('99.10');
    expect($sale->items->first()->tax_amount)->toBe('99.10');
});
