<?php

use App\Models\User;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;

uses(RefreshDatabase::class);

it('posts each batch balance change with one signed immutable ledger movement', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->for($item)->create(['batch_no' => 'LOT-POST-01']);
    $stock = app(StockService::class);

    $increase = $stock->increase($batch, '10.250', 'receipt', 'Penerimaan barang', 'GoodsReceipt', 17);
    $decrease = $stock->decrease($batch, '2.125', 'sale', 'Penjualan barang', 'Sale', 29);

    expect($batch->fresh()->qty_on_hand)->toBe('8.125');
    expect($increase->qty_change)->toBe('10.250');
    expect($increase->qty_after)->toBe('10.250');
    expect($decrease->qty_change)->toBe('-2.125');
    expect($decrease->qty_after)->toBe('8.125');
    expect((string) StockMovement::query()->where('item_batch_id', $batch->id)->sum('qty_change'))->toBe('8.125');
    $this->assertDatabaseCount('stock_movements', 2);
});

it('rolls back an overdrawn batch and keeps its previous ledger intact', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->for($item)->create();
    $stock = app(StockService::class);
    $stock->increase($batch, '1.500', 'receipt', 'Penerimaan barang');

    expect(fn () => $stock->decrease($batch, '2.000', 'sale', 'Penjualan barang'))
        ->toThrow(InsufficientStockException::class);

    expect($batch->fresh()->qty_on_hand)->toBe('1.500');
    $this->assertDatabaseCount('stock_movements', 1);
});

it('rejects invalid quantities and reasons without creating stock movements', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->for($item)->create();
    $stock = app(StockService::class);

    expect(fn () => $stock->increase($batch, '0', 'receipt', 'Penerimaan barang'))
        ->toThrow(InvalidArgumentException::class, 'Jumlah stok harus positif dengan maksimal tiga angka desimal.');
    expect(fn () => $stock->increase($batch, '1.1234', 'receipt', 'Penerimaan barang'))
        ->toThrow(InvalidArgumentException::class, 'Jumlah stok harus positif dengan maksimal tiga angka desimal.');
    expect(fn () => $stock->increase($batch, '1.000', 'receipt', '  '))
        ->toThrow(InvalidArgumentException::class, 'Alasan mutasi stok wajib diisi.');

    expect($batch->fresh()->qty_on_hand)->toBe('0.000');
    $this->assertDatabaseCount('stock_movements', 0);
});

it('only changes batch balances and creates append-only movements through the stock service', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->for($item)->create();
    $movement = app(StockService::class)->increase($batch, '3.000', 'receipt', 'Penerimaan barang');

    expect(fn () => $batch->fresh()->forceFill(['qty_on_hand' => '9.000'])->save())
        ->toThrow(LogicException::class, 'Saldo batch hanya boleh diubah melalui StockService.');
    expect(fn () => (new StockMovement)->forceFill([
        'item_batch_id' => $batch->id,
        'qty_change' => '1.000',
        'qty_after' => '4.000',
        'type' => 'adjustment',
        'reason' => 'Mutasi langsung',
    ])->save())
        ->toThrow(LogicException::class, 'Mutasi stok hanya boleh dibuat melalui StockService.');
    expect(fn () => $movement->update(['reason' => 'Diubah']))
        ->toThrow(LogicException::class, 'Mutasi stok bersifat append-only.');
    expect(fn () => $movement->delete())
        ->toThrow(LogicException::class, 'Mutasi stok tidak dapat dihapus.');

    expect($batch->fresh()->qty_on_hand)->toBe('3.000');
    $this->assertDatabaseCount('stock_movements', 1);
});

it('enforces the non-negative batch balance constraint at the database level', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->for($item)->create();

    expect(fn () => DB::table('item_batches')->where('id', $batch->id)->update(['qty_on_hand' => '-0.001']))
        ->toThrow(QueryException::class);

    expect($batch->fresh()->qty_on_hand)->toBe('0.000');
});

it('prevents duplicate batch numbers and expiry dates for the same item', function () {
    $item = Item::factory()->create();
    $batchAttributes = [
        'batch_no' => 'LOT-UNIQUE-01',
        'expiry_date' => today()->addYear(),
    ];
    ItemBatch::factory()->for($item)->create($batchAttributes);

    expect(fn () => ItemBatch::factory()->for($item)->create($batchAttributes))
        ->toThrow(QueryException::class);
});

it('returns only active unexpired stock as available', function () {
    $this->travelTo(now()->setTime(12, 0));

    $activeItem = Item::factory()->create();
    $inactiveItem = Item::factory()->create(['is_active' => false]);
    $currentBatch = ItemBatch::factory()->for($activeItem)->create(['expiry_date' => today()->addDay()]);
    $expiredBatch = ItemBatch::factory()->for($activeItem)->create(['expiry_date' => today()->subDay()]);
    $inactiveBatch = ItemBatch::factory()->for($inactiveItem)->create(['expiry_date' => today()->addDay()]);
    $stock = app(StockService::class);

    $stock->increase($currentBatch, '4.250', 'receipt', 'Penerimaan barang');
    $stock->increase($expiredBatch, '8.000', 'receipt', 'Penerimaan barang');
    $stock->increase($inactiveBatch, '7.000', 'receipt', 'Penerimaan barang');

    expect($stock->available($activeItem))->toBe('4.250');
    expect($stock->available($inactiveItem))->toBe('0.000');
});

it('requires authentication and the stock-view permission for stock screens', function () {
    $this->get(route('inventory.stock.index'))->assertRedirectToRoute('login');

    $this->seed(RolesAndPermissionsSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('inventory.stock.index'))
        ->assertForbidden();

    $warehouseStaff = User::factory()->create();
    $warehouseStaff->assignRole('gudang');

    $this->actingAs($warehouseStaff)
        ->get(route('inventory.stock.index'))
        ->assertOk();
});

it('shows stock totals in configured units and links to paginated batch details', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $warehouseStaff = User::factory()->create();
    $warehouseStaff->assignRole('gudang');
    $item = Item::factory()->create(['name' => 'Paracetamol 500 mg', 'code' => 'ITM-STOCK-01']);
    $item->baseUnit()->update(['name' => 'tablet']);
    $item->orderUnit()->update(['name' => 'strip']);
    $item->itemUnits()->where('unit_id', $item->order_unit_id)->update(['factor_to_base' => '10.000']);
    $batch = ItemBatch::factory()->for($item)->create([
        'batch_no' => 'LOT-STOCK-01',
        'expiry_date' => today()->addMonths(6),
    ]);
    app(StockService::class)->increase($batch, '8.000', 'receipt', 'Penerimaan barang');

    $this->actingAs($warehouseStaff)
        ->get(route('inventory.stock.index'))
        ->assertOk()
        ->assertSee('8.000 tablet')
        ->assertSee('0.800 strip')
        ->assertSee('1 batch · Rincian');

    $this->get(route('inventory.stock.show', $item))
        ->assertOk()
        ->assertSee('LOT-STOCK-01')
        ->assertSee('8.000')
        ->assertSee('Belum kedaluwarsa');
});

it('escapes item and batch labels on the stock detail page', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $warehouseStaff = User::factory()->create();
    $warehouseStaff->assignRole('gudang');
    $item = Item::factory()->create(['name' => '<script>alert(1)</script>']);
    $batch = ItemBatch::factory()->for($item)->create([
        'batch_no' => '<script>batch</script>',
        'expiry_date' => today()->addMonth(),
    ]);
    app(StockService::class)->increase($batch, '1.000', 'receipt', 'Penerimaan barang');

    $this->actingAs($warehouseStaff)
        ->get(route('inventory.stock.show', $item))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
        ->assertSee('&lt;script&gt;batch&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>batch</script>', escape: false);
});
