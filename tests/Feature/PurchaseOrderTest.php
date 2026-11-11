<?php

use App\Models\User;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function purchasingUser(): User
{
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('gudang');

    return $user;
}

it('shows items at or below reorder point with a rounded purchase recommendation', function () {
    $user = purchasingUser();
    $item = Item::factory()->create([
        'name' => 'Item ROP',
        'reorder_point' => '10.000',
        'buffer_stock' => '12.000',
        'hna_base' => '2500.0000',
    ]);

    $this->actingAs($user)->get(route('purchasing.purchase-orders.index'))
        ->assertOk()
        ->assertSee('Item ROP')
        ->assertSee('12.000');
});

it('creates a draft purchase order and calculates its total', function () {
    $user = purchasingUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['hna_base' => '1250.0000']);

    $response = $this->actingAs($user)->post(route('purchasing.purchase-orders.store'), [
        'supplier_id' => $supplier->id,
        'order_date' => today()->toDateString(),
        'lines' => [[
            'item_id' => $item->id,
            'unit_id' => $item->order_unit_id,
            'qty_ordered' => '4.000',
            'unit_price' => '1250.0000',
        ]],
    ]);

    $order = PurchaseOrder::query()->first();
    $response->assertRedirect(route('purchasing.purchase-orders.show', $order));
    expect($order->total_estimated)->toBe('5000.00');
    $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $order->id, 'qty_base' => '4.000']);
});

it('sends a draft and prevents editing after sending', function () {
    $user = purchasingUser();
    $order = PurchaseOrder::factory()->create();
    $item = Item::factory()->create();
    $order->items()->create([
        'item_id' => $item->id,
        'unit_id' => $item->order_unit_id,
        'qty_ordered' => '2.000',
        'qty_base' => '2.000',
        'unit_price' => '1000.0000',
        'line_total' => '2000.00',
    ]);

    $this->actingAs($user)->post(route('purchasing.purchase-orders.send', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe('sent');
    expect($order->fresh()->sent_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['auditable_type' => PurchaseOrder::class, 'auditable_id' => $order->id, 'event' => 'updated']);

    $this->actingAs($user)->put(route('purchasing.purchase-orders.update', $order), [
        'supplier_id' => $order->supplier_id,
        'order_date' => today()->toDateString(),
        'lines' => [[
            'item_id' => $item->id,
            'unit_id' => $item->order_unit_id,
            'qty_ordered' => '2.000',
        ]],
    ])->assertSessionHasErrors('purchase_order');
});

it('rejects duplicate items and users without purchasing permission', function () {
    $user = purchasingUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create();
    $data = [
        'supplier_id' => $supplier->id,
        'order_date' => today()->toDateString(),
        'lines' => [
            ['item_id' => $item->id, 'unit_id' => $item->order_unit_id, 'qty_ordered' => '1.000'],
            ['item_id' => $item->id, 'unit_id' => $item->order_unit_id, 'qty_ordered' => '1.000'],
        ],
    ];

    $this->actingAs($user)->post(route('purchasing.purchase-orders.store'), $data)->assertSessionHasErrors('lines');

    $withoutPermission = User::factory()->create();
    $this->actingAs($withoutPermission)->get(route('purchasing.purchase-orders.index'))->assertForbidden();
});
