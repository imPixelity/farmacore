<?php

use App\Models\User;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\PurchaseReturn;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Purchasing\Models\Payable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function confirmedReceiptWithStock(string $paymentMethod = 'kredit'): array
{
    $receipt = GoodsReceipt::factory()->create([
        'payment_method' => $paymentMethod,
        'total_after_tax' => '5000.00',
        'due_date' => $paymentMethod === 'kredit' ? today()->addDays(30) : null,
    ]);
    $receiptItem = GoodsReceiptItem::factory()->for($receipt, 'receipt')->create([
        'qty_received' => '5.000',
        'qty_base' => '5.000',
        'unit_price' => '1000.00',
        'line_total' => '5000.00',
    ]);
    $batch = ItemBatch::factory()->for($receiptItem->item)->create([
        'goods_receipt_item_id' => $receiptItem->id,
        'unit_cost_base' => '1000.0000',
    ]);
    app(StockService::class)->increase($batch, '5.000', 'receipt', 'Penerimaan barang');
    GoodsReceipt::duringConfirmation(function () use ($receipt): void {
        $receipt->forceFill(['status' => 'confirmed', 'confirmed_at' => now()])->save();
    });

    return [$receipt->fresh(), $receiptItem->fresh(), $batch->fresh()];
}

it('confirms a purchase return, reduces stock, and adjusts the supplier payable', function () {
    [$receipt, $receiptItem, $batch] = confirmedReceiptWithStock();
    $payable = Payable::factory()->create([
        'supplier_id' => $receipt->supplier_id,
        'goods_receipt_id' => $receipt->id,
        'total_amount' => '5000.00',
        'paid_amount' => '1000.00',
        'status' => 'sebagian',
    ]);
    $warehouse = User::factory()->create();
    $warehouse->assignRole('gudang');

    $this->actingAs($warehouse)
        ->post(route('inventory.purchase-returns.store'), [
            'goods_receipt_id' => $receipt->id,
            'return_date' => '2026-11-12',
            'lines' => [
                $receiptItem->id => [
                    'goods_receipt_item_id' => $receiptItem->id,
                    'item_batch_id' => $batch->id,
                    'qty_base' => '2.000',
                    'reason' => 'rusak',
                ],
            ],
        ])
        ->assertRedirect();

    $purchaseReturn = PurchaseReturn::query()->firstOrFail();
    $approver = User::factory()->create();
    $approver->assignRole('apoteker');
    $this->actingAs($approver)
        ->post(route('inventory.purchase-returns.confirm', $purchaseReturn))
        ->assertRedirect();

    expect($purchaseReturn->fresh()->status)->toBe('confirmed');
    expect($purchaseReturn->fresh()->payable_adjustment_amount)->toBe('2000.00');
    expect($purchaseReturn->fresh()->supplier_credit_amount)->toBe('0.00');
    expect($batch->fresh()->qty_on_hand)->toBe('3.000');
    expect($payable->fresh()->total_amount)->toBe('3000.00');
    expect($payable->fresh()->status)->toBe('sebagian');
    expect(StockMovement::query()->where('type', 'purchase_return')->count())->toBe(1);
});

it('rejects cumulative returns beyond received quantity or current batch stock', function () {
    [$receipt, $receiptItem, $batch] = confirmedReceiptWithStock('tunai');
    $warehouse = User::factory()->create();
    $warehouse->assignRole('gudang');
    $payload = [
        'goods_receipt_id' => $receipt->id,
        'return_date' => '2026-11-12',
        'lines' => [$receiptItem->id => [
            'goods_receipt_item_id' => $receiptItem->id,
            'item_batch_id' => $batch->id,
            'qty_base' => '3.000',
            'reason' => 'salah_kirim',
        ]],
    ];

    $this->actingAs($warehouse)->post(route('inventory.purchase-returns.store'), $payload)->assertRedirect();
    $firstReturn = PurchaseReturn::query()->firstOrFail();
    $approver = User::factory()->create();
    $approver->assignRole('apoteker');
    $this->actingAs($approver)->post(route('inventory.purchase-returns.confirm', $firstReturn));

    $payload['lines'][$receiptItem->id]['qty_base'] = '3.000';
    $this->actingAs($warehouse)
        ->post(route('inventory.purchase-returns.store'), $payload)
        ->assertSessionHasErrors('qty_base');

    expect(PurchaseReturn::query()->where('status', 'draft')->count())->toBe(0);
    expect($batch->fresh()->qty_on_hand)->toBe('2.000');
});
