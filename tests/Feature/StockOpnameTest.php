<?php

use App\Models\User;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockWriteOff;
use App\Modules\Inventory\Services\StockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('stores a stock snapshot, calculates differences, and applies approved adjustments', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('gudang');
    $item = ItemBatch::factory()->create(['unit_cost_base' => '1250.0000']);
    app(StockService::class)->increase($item, '10.000', 'receipt', 'Penerimaan barang');

    $this->actingAs($warehouse)
        ->post(route('inventory.opnames.store'), ['opname_date' => '2026-11-12'])
        ->assertRedirect();

    $opname = StockOpname::query()->firstOrFail();
    $line = $opname->items()->firstOrFail();
    expect($line->system_qty)->toBe('10.000');

    $this->put(route('inventory.opnames.update', $opname), [
        'items' => [$line->id => ['physical_qty' => '8.000', 'note' => 'Dua unit rusak saat hitung']],
    ])->assertRedirect();

    expect($line->fresh()->diff_qty)->toBe('-2.000');
    $this->post(route('inventory.opnames.submit', $opname))->assertRedirect();

    $approver = User::factory()->create();
    $approver->assignRole('apoteker');
    $this->actingAs($approver)
        ->post(route('inventory.opnames.approve', $opname))
        ->assertRedirect();

    expect($opname->fresh()->status)->toBe('approved');
    expect($item->fresh()->qty_on_hand)->toBe('8.000');
    expect(StockMovement::query()->where('type', 'opname_adjust')->count())->toBe(1);
});

it('rejects a submitted stock opname without changing the batch balance', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('gudang');
    $item = ItemBatch::factory()->create();
    app(StockService::class)->increase($item, '5.000', 'receipt', 'Penerimaan barang');

    $this->actingAs($warehouse)->post(route('inventory.opnames.store'), ['opname_date' => '2026-11-12']);
    $opname = StockOpname::query()->firstOrFail();
    $line = $opname->items()->firstOrFail();
    $this->put(route('inventory.opnames.update', $opname), ['items' => [$line->id => ['physical_qty' => '3.000']]]);
    $this->post(route('inventory.opnames.submit', $opname));

    $approver = User::factory()->create();
    $approver->assignRole('apoteker');
    $this->actingAs($approver)
        ->post(route('inventory.opnames.reject', $opname), ['notes' => 'Perlu hitung ulang'])
        ->assertRedirect();

    expect($opname->fresh()->status)->toBe('rejected');
    expect($item->fresh()->qty_on_hand)->toBe('5.000');
    expect(StockMovement::query()->where('type', 'opname_adjust')->count())->toBe(0);
});

it('creates and approves a write-off using batch cost as the loss amount', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('gudang');
    $item = ItemBatch::factory()->create(['unit_cost_base' => '2500.0000']);
    app(StockService::class)->increase($item, '4.000', 'receipt', 'Penerimaan barang');

    $this->actingAs($warehouse)
        ->post(route('inventory.write-offs.store'), [
            'item_batch_id' => $item->id,
            'write_off_date' => '2026-11-12',
            'qty_base' => '2.000',
            'reason' => 'rusak',
        ])
        ->assertRedirect(route('inventory.opnames.index'));

    $writeOff = StockWriteOff::query()->firstOrFail();
    expect($writeOff->loss_amount)->toBe('5000.00');

    $approver = User::factory()->create();
    $approver->assignRole('apoteker');
    $this->actingAs($approver)
        ->post(route('inventory.write-offs.approve', $writeOff))
        ->assertRedirect();

    expect($writeOff->fresh()->status)->toBe('approved');
    expect($item->fresh()->qty_on_hand)->toBe('2.000');
    expect(StockMovement::query()->where('type', 'rusak')->count())->toBe(1);
});
