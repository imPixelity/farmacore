<?php

use App\Models\User;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Services\ReceiptMatchingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function matchingUser(): User
{
    app(RolesAndPermissionsSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('gudang');

    return $user;
}

function matchingFixture(string $receivedQuantity = '10.000', string $poQuantity = '10.000'): array
{
    $supplier = Supplier::factory()->create(['lead_time_days' => '5.0']);
    $item = Item::factory()->create(['name' => 'Matching Item', 'hna_base' => '100.0000']);
    $itemUnit = ItemUnit::query()->where('item_id', $item->id)->where('unit_id', $item->order_unit_id)->firstOrFail();
    $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'status' => 'sent']);
    $orderItem = $order->items()->create([
        'item_id' => $item->id,
        'unit_id' => $item->order_unit_id,
        'qty_ordered' => $poQuantity,
        'qty_base' => $poQuantity,
        'qty_received' => '0.000',
        'unit_price' => '100.0000',
        'line_total' => bcmul($poQuantity, '100', 2),
    ]);
    $receipt = GoodsReceipt::factory()->create([
        'supplier_id' => $supplier->id,
        'purchase_order_id' => $order->id,
        'status' => 'draft',
        'matching_status' => 'tidak_ada_po',
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $item->id,
        'unit_id' => $itemUnit->unit_id,
        'qty_received' => $receivedQuantity,
        'qty_base' => $receivedQuantity,
        'unit_price' => '100.00',
        'line_total' => bcmul($receivedQuantity, '100', 2),
        'match_status' => 'tidak_dipesan',
    ]);

    return [$supplier, $order, $orderItem, $receipt];
}

it('matches an exact receipt, updates the PO quantity, and marks the PO received', function () {
    $user = matchingUser();
    [, $order, $orderItem, $receipt] = matchingFixture();

    $this->actingAs($user);
    $matched = app(ReceiptMatchingService::class)->matchAndApply($receipt);

    expect($matched->matching_status)->toBe('sesuai');
    expect($orderItem->fresh()->qty_received)->toBe('10.000');
    expect($order->fresh()->status)->toBe('received');
    $this->assertDatabaseHas('goods_receipt_items', ['goods_receipt_id' => $receipt->id, 'match_status' => 'sesuai', 'is_return_candidate' => false]);
});

it('requires explicit confirmation for an over-receipt and marks it as a return candidate', function () {
    $user = matchingUser();
    [, $order, $orderItem, $receipt] = matchingFixture('12.000', '10.000');
    $service = app(ReceiptMatchingService::class);

    expect(fn () => $service->matchAndApply($receipt))->toThrow(ValidationException::class);
    expect($orderItem->fresh()->qty_received)->toBe('0.000');

    $service->matchAndApply($receipt, true);

    expect($order->fresh()->status)->toBe('received');
    $this->assertDatabaseHas('goods_receipt_items', ['goods_receipt_id' => $receipt->id, 'match_status' => 'lebih', 'is_return_candidate' => true]);
});

it('calculates supplier lead time and delivery and matching accuracy from confirmed receipts', function () {
    $user = matchingUser();
    [$supplier, $order, $orderItem, $receipt] = matchingFixture();
    $order->forceFill(['sent_at' => now()->subDays(5), 'expected_date' => today()->addDay()])->saveQuietly();
    $receipt->forceFill(['invoice_date' => today()])->saveQuietly();
    $receipt->forceFill(['status' => 'confirmed', 'matching_status' => 'sesuai'])->saveQuietly();
    $orderItem->forceFill(['qty_received' => '10.000'])->saveQuietly();

    $metrics = app(ReceiptMatchingService::class)->supplierMetrics($supplier);

    expect($metrics['average_lead_time_days'])->toBe('5.0');
    expect($metrics['delivery_accuracy_percent'])->toBe('100.00');
    expect($metrics['matching_accuracy_percent'])->toBe('100.00');
});
