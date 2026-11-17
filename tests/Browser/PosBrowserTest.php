<?php

namespace Tests\Browser;

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\Unit;
use App\Modules\Pos\Models\Sale;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Pest\Browser\Api\AwaitableWebpage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);
});

function signInAsBrowserCashier(): AwaitableWebpage
{
    return visit('/login')
        ->fill('#username', 'cashier-browser')
        ->fill('#password', 'Secure-password-123')
        ->click('Masuk')
        ->assertPathIs('/');
}

it('lets a cashier add an item and read grouped stock and quantity in the POS', function (): void {
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Amoxicillin 500 mg',
        'dosage_form' => 'tablet',
        'strength_volume' => '500 mg',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => '1200.00',
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, '1000.000', 'receipt', 'Stok awal browser test');

    $cashier = User::factory()->create([
        'username' => 'cashier-browser',
        'password' => 'Secure-password-123',
    ]);
    $cashier->assignRole('kasir');

    $page = signInAsBrowserCashier();
    $page->navigate('/pos')
        ->assertSee('Keranjang penjualan')
        ->type('input[placeholder="Nama, kode, atau barcode"]', 'Amoxicillin')
        ->assertSee('1.000 tablet')
        ->click('Tambah')
        ->assertSee('Stok tersedia: 1.000 tablet')
        ->fill('input[aria-label="Jumlah Amoxicillin 500 mg dalam tablet"]', '1000')
        ->keys('input[aria-label="Jumlah Amoxicillin 500 mg dalam tablet"]', 'Tab')
        ->assertSee('1.000 × Rp 1.200,00')
        ->assertSee('Rp 1.200.000,00')
        ->assertNoJavaScriptErrors();
});

it('shows a missing-price validation message without rendering an exception page', function (): void {
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Produk tanpa harga',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, '10.000', 'receipt', 'Stok awal browser test');

    $cashier = User::factory()->create([
        'username' => 'cashier-browser',
        'password' => 'Secure-password-123',
    ]);
    $cashier->assignRole('kasir');

    $page = signInAsBrowserCashier();
    $page->navigate('/pos')
        ->type('input[placeholder="Nama, kode, atau barcode"]', 'Produk tanpa harga')
        ->click('Tambah')
        ->assertSee('Harga Produk tanpa harga untuk jenis penjualan ini belum tersedia.')
        ->assertDontSee('ViewException')
        ->assertSee('Keranjang penjualan')
        ->assertNoJavaScriptErrors();
});

it('adds the matching item when a known barcode is scanned from the POS', function (): void {
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Obat barcode dikenal',
        'barcode' => 'BROWSER-ITEM-001',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => '2500.00',
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, '5.000', 'receipt', 'Stok awal browser test');

    $cashier = User::factory()->create([
        'username' => 'cashier-browser',
        'password' => 'Secure-password-123',
    ]);
    $cashier->assignRole('kasir');

    $page = signInAsBrowserCashier();
    $page->navigate('/pos')
        ->fill('input[placeholder="Scan lalu Enter"]', 'BROWSER-ITEM-001')
        ->keys('input[placeholder="Scan lalu Enter"]', 'Enter')
        ->assertSee('Obat barcode dikenal')
        ->assertSee('1 × Rp 2.500,00')
        ->assertValue('input[placeholder="Scan lalu Enter"]', '')
        ->assertNoJavaScriptErrors();
});

it('keeps the cart available when a scanned barcode is unknown', function (): void {
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Obat untuk scan',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => '2500.00',
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, '5.000', 'receipt', 'Stok awal browser test');

    $cashier = User::factory()->create([
        'username' => 'cashier-browser',
        'password' => 'Secure-password-123',
    ]);
    $cashier->assignRole('kasir');

    $page = signInAsBrowserCashier();
    $page->navigate('/pos')
        ->type('input[placeholder="Nama, kode, atau barcode"]', 'Obat untuk scan')
        ->click('Tambah')
        ->fill('input[placeholder="Scan lalu Enter"]', 'UNKNOWN-123')
        ->keys('input[placeholder="Scan lalu Enter"]', 'Enter')
        ->assertSee('Barcode tidak dikenal. Transaksi tetap dapat dilanjutkan.')
        ->assertSee('1 × Rp 2.500,00')
        ->assertSee('Obat untuk scan')
        ->assertNoJavaScriptErrors();
});

it('completes a cash checkout from the browser and updates sale, stock, and cash balance', function (): void {
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Vitamin C browser',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => '1000.00',
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, '5.000', 'receipt', 'Stok awal browser test');
    $account = CashBankAccount::factory()->create(['name' => 'Kas Browser']);

    $cashier = User::factory()->create([
        'username' => 'cashier-browser',
        'password' => 'Secure-password-123',
    ]);
    $cashier->assignRole('kasir');

    $page = signInAsBrowserCashier();
    $page->navigate('/pos')
        ->type('input[placeholder="Nama, kode, atau barcode"]', 'Vitamin C browser')
        ->click('Tambah')
        ->select('select[wire\\:model="cashBankAccountId"]', (string) $account->id)
        ->fill('input[placeholder="Total otomatis"]', '1000')
        ->fill('input[placeholder="Sama dengan nominal"]', '1000')
        ->click('Selesaikan penjualan')
        ->assertSee('berhasil diselesaikan.')
        ->assertSee('Lihat / cetak struk')
        ->assertNoJavaScriptErrors();

    $sale = Sale::query()->where('grand_total', '1000.00')->firstOrFail();

    expect($batch->fresh()->qty_on_hand)->toBe('4.000')
        ->and($account->fresh()->current_balance)->toBe('1000.00');
    $this->assertDatabaseHas('sales', ['id' => $sale->id, 'grand_total' => '1000.00']);
});
