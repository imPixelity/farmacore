<?php

use App\Jobs\Inventory\RecalculateStockPolicy;
use App\Models\User;
use App\Modules\Core\Services\SettingsService;
use App\Modules\Inventory\Exceptions\ExpiredBatchNotSellableException;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockPolicy;
use App\Modules\Inventory\Services\StockPolicyService;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('calculates minimum, buffer, and reorder point from recent usage and supplier lead time', function () {
    $this->travelTo(today()->setTime(10, 0));
    $item = Item::factory()->create(['movement_class' => 'fast']);
    StockPolicy::query()->create([
        'movement_class' => 'fast',
        'min_stock_days' => 10,
        'safety_factor' => '2.00',
    ]);
    $supplier = Supplier::factory()->create(['lead_time_days' => '5.0']);
    $receipt = GoodsReceipt::factory()->create(['supplier_id' => $supplier->id]);
    $receipt->forceFill(['status' => 'confirmed'])->saveQuietly();
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $item->id,
    ]);
    $batch = ItemBatch::factory()->create([
        'item_id' => $item->id,
        'expiry_date' => today()->addYear()->toDateString(),
    ]);
    $stock = app(StockService::class);
    $stock->increase($batch, '100.000', 'receipt', 'Penerimaan awal');
    $stock->decrease($batch, '30.000', 'sale', 'Pemakaian penjualan');

    $calculatedItem = app(StockPolicyService::class)->recalculate($item->fresh());

    expect($calculatedItem->average_monthly_usage)->toBe('10.000000');
    expect($calculatedItem->min_stock)->toBe('3.333');
    expect($calculatedItem->buffer_stock)->toBe('3.333');
    expect($calculatedItem->reorder_point)->toBe('3.333');
});

it('uses a manual minimum when the item has no usage history', function () {
    $item = Item::factory()->create();

    $updatedItem = app(StockPolicyService::class)->updateItemPolicy($item, 'slow', '12.500');

    expect($updatedItem->movement_class)->toBe('slow');
    expect($updatedItem->min_stock_override)->toBe('12.500');
    expect($updatedItem->min_stock)->toBe('12.500');
    expect($updatedItem->reorder_point)->toBe('12.500');
    expect($updatedItem->average_monthly_usage)->toBeNull();
    expect($updatedItem->buffer_stock)->toBeNull();
});

it('lets an apothecary override a minimum stock value for one item', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $item = Item::factory()->create();

    $this->actingAs($user)->put(route('inventory.stock.policy.update', $item), [
        'movement_class' => 'medium',
        'min_stock_override' => '8.250',
    ])->assertRedirect(route('inventory.stock.show', $item));

    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'movement_class' => 'medium',
        'min_stock_override' => '8.250',
        'min_stock' => '8.250',
        'reorder_point' => '8.250',
    ]);
});

it('renders the policy editor and per-item stock metrics for an apothecary', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $item = Item::factory()->create();
    app(StockPolicyService::class)->updateItemPolicy($item, 'fast', '4.000');

    $this->actingAs($user)->get(route('inventory.stock-policies.index'))
        ->assertOk()
        ->assertSee('Parameter pergerakan barang')
        ->assertSee('fast moving');
    $this->actingAs($user)->get(route('inventory.stock.show', $item))
        ->assertOk()
        ->assertSee('Kebijakan dan rekomendasi stok')
        ->assertSee('4.000');
});

it('stores movement-class policy configuration and queues recalculation', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    Queue::fake();

    $this->actingAs($user)->put(route('inventory.stock-policies.update'), [
        'policies' => [
            'fast' => ['min_stock_days' => 7, 'safety_factor' => '1.50'],
            'medium' => ['min_stock_days' => 14, 'safety_factor' => '1.25'],
            'slow' => ['min_stock_days' => 30, 'safety_factor' => '1.00'],
        ],
    ])->assertRedirect(route('inventory.stock-policies.index'));

    $this->assertDatabaseHas('stock_policies', [
        'movement_class' => 'medium',
        'min_stock_days' => 14,
        'safety_factor' => '1.25',
    ]);
    Queue::assertPushed(RecalculateStockPolicy::class);
});

it('notifies inventory users when available stock reaches the reorder point', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('apoteker');
    $item = Item::factory()->create([
        'movement_class' => 'fast',
        'min_stock_override' => '5.000',
    ]);
    $batch = ItemBatch::factory()->create([
        'item_id' => $item->id,
        'expiry_date' => today()->addYear()->toDateString(),
    ]);
    $stock = app(StockService::class);
    $stock->increase($batch, '10.000', 'receipt', 'Penerimaan awal');
    app(StockPolicyService::class)->recalculateAndNotify($item->fresh());
    $this->assertDatabaseCount('notifications', 0);

    $stock->decrease($batch, '6.000', 'sale', 'Penjualan');
    app(StockPolicyService::class)->recalculateAndNotify($item->fresh());

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'type' => 'ReorderPointReached',
    ]);
});

it('sends near-expiry notifications only within the configured threshold', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('gudang');
    app(SettingsService::class)->setMany(['expiry_alert_days' => 10]);
    $nearItem = Item::factory()->create();
    $nearBatch = ItemBatch::factory()->create([
        'item_id' => $nearItem->id,
        'batch_no' => 'NEAR-EXPIRY',
        'expiry_date' => today()->addDays(10)->toDateString(),
    ]);
    $farItem = Item::factory()->create();
    $farBatch = ItemBatch::factory()->create([
        'item_id' => $farItem->id,
        'batch_no' => 'FAR-EXPIRY',
        'expiry_date' => today()->addDays(11)->toDateString(),
    ]);
    $stock = app(StockService::class);
    $stock->increase($nearBatch, '4.000', 'receipt', 'Penerimaan');
    $stock->increase($farBatch, '3.000', 'receipt', 'Penerimaan');

    app(StockPolicyService::class)->notifyNearExpiryBatches();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $user->id,
        'type' => 'BatchNearExpiry',
        'dedupe_key' => hash('sha256', 'BatchNearExpiry|batch-'.$nearBatch->id.'|'.today()->toDateString()),
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $user->id,
        'type' => 'BatchNearExpiry',
        'dedupe_key' => hash('sha256', 'BatchNearExpiry|batch-'.$farBatch->id.'|'.today()->toDateString()),
    ]);
});

it('filters the expiry monitor by an inclusive date range and shows batch details', function () {
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('gudang');
    $matchingItem = Item::factory()->create(['name' => 'Expiry Filter Match']);
    $matchingBatch = ItemBatch::factory()->create([
        'item_id' => $matchingItem->id,
        'batch_no' => 'FILTER-MATCH',
        'expiry_date' => today()->addDays(4)->toDateString(),
    ]);
    $outsideItem = Item::factory()->create(['name' => 'Expiry Filter Outside']);
    $outsideBatch = ItemBatch::factory()->create([
        'item_id' => $outsideItem->id,
        'batch_no' => 'FILTER-OUTSIDE',
        'expiry_date' => today()->addDays(12)->toDateString(),
    ]);
    $stock = app(StockService::class);
    $stock->increase($matchingBatch, '2.000', 'receipt', 'Penerimaan');
    $stock->increase($outsideBatch, '1.000', 'receipt', 'Penerimaan');

    $this->actingAs($user)->get(route('inventory.expiry.index', [
        'expiry_from' => today()->addDays(3)->toDateString(),
        'expiry_to' => today()->addDays(5)->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Expiry Filter Match')
        ->assertSee('FILTER-MATCH')
        ->assertDontSee('Expiry Filter Outside');
});

it('rejects policy management for users without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('inventory.stock-policies.index'))->assertForbidden();
});

it('does not allow an expired batch to be reduced as a sale', function () {
    $item = Item::factory()->create();
    $batch = ItemBatch::factory()->create([
        'item_id' => $item->id,
        'expiry_date' => today()->subDay()->toDateString(),
    ]);
    $stock = app(StockService::class);
    $stock->increase($batch, '2.000', 'receipt', 'Penerimaan');

    expect(fn () => $stock->decrease($batch, '1.000', 'sale', 'Penjualan'))
        ->toThrow(ExpiredBatchNotSellableException::class);

    expect($batch->fresh()->qty_on_hand)->toBe('2.000');
    $this->assertDatabaseCount('stock_movements', 1);
});
