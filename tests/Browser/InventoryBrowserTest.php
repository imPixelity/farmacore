<?php

namespace Tests\Browser;

use App\Models\User;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows the current batch quantity and expiry status on the inventory detail page', function (): void {
    $warehouseUser = User::factory()->create();
    $warehouseUser->assignRole('gudang');
    $unit = Unit::factory()->create(['name' => 'tablet']);
    $item = Item::factory()->create([
        'name' => 'Stok browser 1000',
        'base_unit_id' => $unit->id,
        'order_unit_id' => $unit->id,
    ]);
    $batch = ItemBatch::factory()->for($item)->create([
        'batch_no' => 'BATCH-BROWSER-01',
        'expiry_date' => today()->addYear()->toDateString(),
    ]);
    app(StockService::class)->increase($batch, '1000.000', 'receipt', 'Stok awal browser test');

    $this->actingAs($warehouseUser);

    visit('/inventory/stock/'.$item->id)
        ->assertSee('Rincian batch stok')
        ->assertSee($item->name)
        ->assertSee('Total stok semua batch')
        ->assertSee('1.000 tablet')
        ->assertSee('BATCH-BROWSER-01')
        ->assertSee('Belum kedaluwarsa')
        ->assertNoJavaScriptErrors();
});
