<?php

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleReturn;
use App\Modules\Pos\Services\ReceiptPrintService;
use App\Modules\Pos\Services\SaleLifecycleService;
use App\Modules\Pos\Services\SaleReturnService;
use App\Modules\Pos\Services\SaleService;
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

function saleFixture(string $price = '100.00', string $stock = '5.000'): array
{
    $item = Item::factory()->create();
    ItemPrice::factory()->for($item)->create(['sales_type' => 'obat_bebas', 'selling_price' => $price]);
    $batch = ItemBatch::factory()->for($item)->create(['expiry_date' => today()->addMonths(6)]);
    app(StockService::class)->increase($batch, $stock, 'receipt', 'Test stock receipt');
    $account = CashBankAccount::factory()->create(['opening_balance' => '1000.00', 'current_balance' => '1000.00']);
    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '1.000']],
        'payments' => [[
            'method' => 'tunai',
            'amount' => $price,
            'cash_received' => $price,
            'cash_bank_account_id' => $account->id,
        ]],
    ]);

    return [$sale, $batch, $account];
}

it('cancels a sale without deleting it and reverses stock and financial postings', function () {
    [$sale, $batch, $account] = saleFixture();

    app(SaleLifecycleService::class)->cancel($sale, 'Salah input kasir');

    $sale->refresh();
    expect($sale->status)->toBe('cancelled');
    expect($sale->cancel_reason)->toBe('Salah input kasir');
    expect($batch->fresh()->qty_on_hand)->toBe('5.000');
    expect($account->fresh()->current_balance)->toBe('1000.00');
    expect(FinancialTransaction::query()->where('ref_id', $sale->id)->where('status', 'reversed')->count())->toBe(1);
    expect(FinancialTransaction::query()->where('reverses_id', FinancialTransaction::query()->where('ref_type', Sale::class)->where('ref_id', $sale->id)->orderBy('id')->value('id'))->count())->toBe(1);
});

it('rejects a second cancellation', function () {
    [$sale] = saleFixture();
    app(SaleLifecycleService::class)->cancel($sale, 'Duplikat');

    expect(fn () => app(SaleLifecycleService::class)->cancel($sale->fresh(), 'Duplikat'))
        ->toThrow(ValidationException::class);
});

it('records a validated return, refunds cash, and optionally restocks the original batch', function () {
    [$sale, $batch, $account] = saleFixture();

    $return = app(SaleReturnService::class)->create($sale, [
        'items' => [['sale_item_id' => $sale->items->first()->id, 'qty_base' => '1.000']],
        'reason' => 'Barang dikembalikan pelanggan',
        'restock' => true,
        'cash_bank_account_id' => $account->id,
    ]);

    expect($return)->toBeInstanceOf(SaleReturn::class);
    expect($return->refund_amount)->toBe('100.00');
    expect($batch->fresh()->qty_on_hand)->toBe('5.000');
    expect($account->fresh()->current_balance)->toBe('1000.00');
    expect(FinancialTransaction::query()->where('ref_type', SaleReturn::class)->where('ref_id', $return->id)->value('direction'))->toBe('out');

    expect(fn () => app(SaleReturnService::class)->create($sale, [
        'items' => [['sale_item_id' => $sale->items->first()->id, 'qty_base' => '1.000']],
        'reason' => 'Retur kedua melebihi qty',
        'restock' => false,
        'cash_bank_account_id' => $account->id,
    ]))->toThrow(ValidationException::class);
});

it('adds health services to the sale total and posts health service revenue', function () {
    [$item] = saleFixture('100.00');
    $service = HealthService::factory()->create(['name' => 'Cek tekanan darah', 'default_fee' => '25.00']);
    $account = CashBankAccount::factory()->create(['opening_balance' => '1000.00', 'current_balance' => '1000.00']);

    $sale = app(SaleService::class)->checkout([
        'sale_type' => 'obat_bebas',
        'cart' => ['item-'.$item->id => ['item_id' => $item->id, 'quantity' => '1.000']],
        'services' => [['health_service_id' => $service->id, 'result_note' => 'Normal']],
        'payments' => [[
            'method' => 'tunai', 'amount' => '125.00', 'cash_received' => '125.00', 'cash_bank_account_id' => $account->id,
        ]],
    ]);

    expect($sale->grand_total)->toBe('125.00');
    expect($sale->services->first()->result_note)->toBe('Normal');
    expect(FinancialTransaction::query()->where('ref_id', $sale->id)->sum('amount'))->toBe(125);
});

it('renders an original and copy receipt', function () {
    [$sale] = saleFixture();
    $service = app(ReceiptPrintService::class);

    expect($service->render($sale, false)->render())->toContain('STRUK PEMBAYARAN');
    expect($service->render($sale, true)->render())->toContain('SALINAN');
});
