<?php

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\GoodsReceiptService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Models\TaxRecord;
use App\Modules\Md\Models\TaxSetting;
use App\Modules\Md\Services\PricingService;
use App\Modules\Purchasing\Models\Payable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

uses(RefreshDatabase::class);

function goodsReceiptPayload(Supplier $supplier, Item $item, string $invoiceNo = 'INV-RECEIPT-001'): array
{
    $itemUnit = ItemUnit::query()
        ->where('item_id', $item->id)
        ->where('unit_id', $item->order_unit_id)
        ->firstOrFail();

    return [
        'supplier_id' => $supplier->id,
        'invoice_no' => $invoiceNo,
        'invoice_date' => today()->toDateString(),
        'payment_method' => 'kredit',
        'discount_amount' => '20.00',
        'lines' => [[
            'item_id' => $item->id,
            'item_unit_id' => $itemUnit->id,
            'qty_received' => '2.000',
            'batch_no' => 'LOT-RECEIPT-001',
            'expiry_date' => today()->addYear()->toDateString(),
            'unit_price' => '100.00',
            'discount_amount' => '10.00',
        ]],
    ];
}

function goodsReceiptWarehouseUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('gudang');

    return $user;
}

function seedGoodsReceiptConfiguration(): void
{
    app(RolesAndPermissionsSeeder::class)->run();
    app(TransactionCategorySeeder::class)->run();
    TaxSetting::factory()->create(['effective_from' => today()->toDateString(), 'rate_percent' => '11.00']);
}

it('saves a draft without changing stock and calculates converted quantity, discounts, tax, and supplier terms', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create(['payment_term_days' => 30]);
    $item = Item::factory()->create(['is_taxable' => true]);
    $itemUnit = ItemUnit::query()->where('item_id', $item->id)->where('unit_id', $item->order_unit_id)->firstOrFail();
    $itemUnit->update(['factor_to_base' => '10.000']);
    $payload = goodsReceiptPayload($supplier, $item);

    $this->actingAs($user)->get(route('inventory.receipts.create'))
        ->assertOk()
        ->assertSee('Tambah baris');

    $response = $this->actingAs($user)->post(route('inventory.receipts.store'), $payload);

    $receipt = GoodsReceipt::query()->firstOrFail();
    $response->assertRedirect(route('inventory.receipts.show', $receipt));
    $this->actingAs($user)->get(route('inventory.receipts.show', $receipt))
        ->assertOk()
        ->assertSee($receipt->receipt_no)
        ->assertSee('188.70');
    $this->assertDatabaseHas('goods_receipts', [
        'id' => $receipt->id,
        'total_before_tax' => '170.00',
        'tax_amount' => '18.70',
        'total_after_tax' => '188.70',
        'status' => 'draft',
    ]);
    expect($receipt->fresh()->due_date->toDateString())->toBe(today()->addDays(30)->toDateString());
    $this->assertDatabaseHas('goods_receipt_items', [
        'goods_receipt_id' => $receipt->id,
        'qty_received' => '2.000',
        'qty_base' => '20.000',
        'line_total' => '190.00',
    ]);
    $this->assertDatabaseCount('item_batches', 0);
    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('tax_records', 0);

    expect($item->fresh()->hna_base)->toBe('0.0000');
});

it('confirms a credit receipt atomically with stock, cost, pricing, payable, tax, and audit records', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create(['payment_term_days' => 30]);
    $item = Item::factory()->create(['is_taxable' => true]);
    $itemUnit = ItemUnit::query()->where('item_id', $item->id)->where('unit_id', $item->order_unit_id)->firstOrFail();
    $itemUnit->update(['factor_to_base' => '10.000']);
    app(PricingService::class)->saveMarginSetting([
        'scope_type' => 'global',
        'scope_id' => null,
        'sales_type' => 'obat_bebas',
        'margin_percent' => '20.00',
    ]);
    $payload = goodsReceiptPayload($supplier, $item);
    $receipt = app(GoodsReceiptService::class)->createDraft($payload);

    $this->actingAs($user)->post(route('inventory.receipts.confirm', $receipt))
        ->assertRedirect(route('inventory.receipts.show', $receipt));

    $batch = ItemBatch::query()->firstOrFail();
    $payable = Payable::query()->firstOrFail();
    $tax = TaxRecord::query()->where('source_type', GoodsReceipt::class)->firstOrFail();
    $receipt->refresh();

    expect($receipt->status)->toBe('confirmed');
    expect($batch->qty_on_hand)->toBe('20.000');
    expect($batch->unit_cost_base)->toBe('8.5000');
    expect($item->fresh()->hna_base)->toBe('8.5000');
    expect($payable->total_amount)->toBe('188.70');
    expect($payable->due_date->toDateString())->toBe(today()->addDays(30)->toDateString());
    expect($tax->rate_percent)->toBe('11.00');
    expect($tax->taxable_amount)->toBe('170.00');
    expect($tax->tax_amount)->toBe('18.70');
    expect(ItemPrice::query()->where('item_id', $item->id)->where('sales_type', 'obat_bebas')->where('is_current', true)->exists())->toBeTrue();
    $this->assertDatabaseHas('stock_movements', [
        'item_batch_id' => $batch->id,
        'qty_change' => '20.000',
        'type' => 'receipt',
        'ref_type' => GoodsReceipt::class,
        'ref_id' => $receipt->id,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => GoodsReceipt::class,
        'auditable_id' => (string) $receipt->id,
        'event' => 'updated',
    ]);

    $this->actingAs($user)->post(route('inventory.receipts.confirm', $receipt));
    $this->assertDatabaseCount('stock_movements', 1);
    $this->assertDatabaseCount('payables', 1);
});

it('posts cash purchases through the financial posting service', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['is_taxable' => true]);
    $payload = goodsReceiptPayload($supplier, $item);
    $payload['payment_method'] = 'tunai';
    $payload['discount_amount'] = '0.00';
    $payload['lines'][0]['discount_amount'] = '0.00';
    $receipt = app(GoodsReceiptService::class)->createDraft($payload);
    $account = CashBankAccount::factory()->create([
        'opening_balance' => '1000.00',
        'current_balance' => '1000.00',
    ]);

    $this->actingAs($user)->post(route('inventory.receipts.confirm', $receipt), [
        'cash_bank_account_id' => $account->id,
    ])->assertRedirect(route('inventory.receipts.show', $receipt));

    expect($account->fresh()->current_balance)->toBe('778.00');
    $this->assertDatabaseHas('financial_transactions', [
        'cash_bank_account_id' => $account->id,
        'direction' => 'out',
        'amount' => '222.00',
        'ref_type' => GoodsReceipt::class,
        'ref_id' => $receipt->id,
    ]);
    $this->assertDatabaseCount('payables', 0);
});

it('rolls back all receipt effects when a cash purchase cannot be posted', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['is_taxable' => true]);
    $payload = goodsReceiptPayload($supplier, $item);
    $payload['payment_method'] = 'tunai';
    $payload['discount_amount'] = '0.00';
    $payload['lines'][0]['discount_amount'] = '0.00';
    $receipt = app(GoodsReceiptService::class)->createDraft($payload);
    $account = CashBankAccount::factory()->create();

    $this->actingAs($user)->from(route('inventory.receipts.show', $receipt))
        ->post(route('inventory.receipts.confirm', $receipt), ['cash_bank_account_id' => $account->id])
        ->assertRedirect(route('inventory.receipts.show', $receipt))
        ->assertSessionHasErrors('amount');

    expect($receipt->fresh()->status)->toBe('draft');
    expect($item->fresh()->hna_base)->toBe('0.0000');
    expect($account->fresh()->current_balance)->toBe('0.00');
    $this->assertDatabaseCount('item_batches', 0);
    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('payables', 0);
    $this->assertDatabaseCount('financial_transactions', 0);
    $this->assertDatabaseCount('tax_records', 0);
});

it('requires explicit pharmacist approval for expired stock or stock expiring before its invoice date', function () {
    seedGoodsReceiptConfiguration();
    $warehouseUser = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['is_taxable' => true]);
    $payload = goodsReceiptPayload($supplier, $item, 'INV-EXPIRED-001');
    $payload['lines'][0]['expiry_date'] = today()->subDay()->toDateString();

    $this->actingAs($warehouseUser)->post(route('inventory.receipts.store'), $payload)
        ->assertSessionHasErrors(['lines.0.expiry_date']);
    $this->assertDatabaseCount('goods_receipts', 0);

    $payload['invoice_no'] = 'INV-EXPIRY-BEFORE-INVOICE';
    $payload['invoice_date'] = today()->addDays(10)->toDateString();
    $payload['lines'][0]['expiry_date'] = today()->addDays(5)->toDateString();
    $this->actingAs($warehouseUser)->post(route('inventory.receipts.store'), $payload)
        ->assertSessionHasErrors(['lines.0.expiry_date']);
    $this->assertDatabaseCount('goods_receipts', 0);

    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    $payload['invoice_no'] = 'INV-EXPIRED-002';
    $payload['invoice_date'] = today()->toDateString();
    $payload['lines'][0]['expiry_date'] = today()->subDay()->toDateString();
    $payload['lines'][0]['approve_expiry'] = '1';

    $this->actingAs($pharmacist)->post(route('inventory.receipts.store'), $payload)
        ->assertRedirect();

    $receipt = GoodsReceipt::query()->firstOrFail();
    $receiptItem = $receipt->items()->firstOrFail();
    expect($receiptItem->approved_by)->toBe($pharmacist->id);

    $editPayload = $payload;
    unset($editPayload['lines'][0]['approve_expiry']);
    $editPayload['lines'][0]['qty_received'] = '3.000';
    $this->actingAs($warehouseUser)->put(route('inventory.receipts.update', $receipt), $editPayload)
        ->assertRedirect(route('inventory.receipts.show', $receipt));

    $receiptItem = $receipt->items()->firstOrFail();
    expect($receiptItem->approved_by)->toBe($pharmacist->id);
    expect($receiptItem->qty_received)->toBe('3.000');
});

it('warns about duplicate supplier invoice numbers but allows explicit continuation', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['is_taxable' => true]);
    $payload = goodsReceiptPayload($supplier, $item, 'INV-DUPLICATE-001');
    $cancelledReceipt = GoodsReceipt::factory()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'INV-CANCELLED-001',
    ]);
    $cancelledReceipt->update(['status' => 'cancelled']);
    expect(app(GoodsReceiptService::class)->hasDuplicateInvoice($supplier->id, 'INV-CANCELLED-001'))->toBeFalse();

    GoodsReceipt::factory()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'INV-DUPLICATE-001',
    ]);

    $this->actingAs($user)->from(route('inventory.receipts.create'))
        ->post(route('inventory.receipts.store'), $payload)
        ->assertRedirect(route('inventory.receipts.create'))
        ->assertSessionHas('duplicate_invoice_warning');
    $this->assertDatabaseCount('goods_receipts', 2);

    $payload['confirm_duplicate'] = '1';
    $this->actingAs($user)->post(route('inventory.receipts.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseCount('goods_receipts', 3);
    expect(app(GoodsReceiptService::class)->hasDuplicateInvoice($supplier->id, 'INV-DUPLICATE-001'))->toBeTrue();
});

it('allocates invoice discounts across taxable and non-taxable lines before calculating input tax', function () {
    seedGoodsReceiptConfiguration();
    $user = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $taxableItem = Item::factory()->create(['is_taxable' => true]);
    $nonTaxableItem = Item::factory()->create(['is_taxable' => false]);
    $payload = goodsReceiptPayload($supplier, $taxableItem, 'INV-MIXED-TAX-001');
    $secondItemUnit = ItemUnit::query()
        ->where('item_id', $nonTaxableItem->id)
        ->where('unit_id', $nonTaxableItem->order_unit_id)
        ->firstOrFail();
    $payload['lines'][] = [
        'item_id' => $nonTaxableItem->id,
        'item_unit_id' => $secondItemUnit->id,
        'qty_received' => '2.000',
        'batch_no' => 'LOT-NON-TAX-001',
        'expiry_date' => today()->addYear()->toDateString(),
        'unit_price' => '100.00',
        'discount_amount' => '10.00',
    ];

    $this->actingAs($user)->post(route('inventory.receipts.store'), $payload)
        ->assertRedirect();

    $receipt = GoodsReceipt::query()->firstOrFail();
    expect($receipt->total_before_tax)->toBe('360.00');
    expect($receipt->tax_amount)->toBe('19.80');
    expect($receipt->total_after_tax)->toBe('379.80');
    expect($receipt->items()->where('item_id', $taxableItem->id)->firstOrFail()->line_total)->toBe('190.00');
    expect($receipt->items()->where('item_id', $nonTaxableItem->id)->firstOrFail()->line_total)->toBe('190.00');
});

it('protects receipt screens and confirmed receipts from unauthorized edits', function () {
    $this->get(route('inventory.receipts.index'))->assertRedirectToRoute('login');

    $this->seed(RolesAndPermissionsSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier)->get(route('inventory.receipts.index'))->assertForbidden();

    seedGoodsReceiptConfiguration();
    $warehouseUser = goodsReceiptWarehouseUser();
    $supplier = Supplier::factory()->create();
    $item = Item::factory()->create(['is_taxable' => false]);
    $payload = goodsReceiptPayload($supplier, $item, 'INV-IMMUTABLE-001');
    $payload['payment_method'] = 'kredit';
    $payload['discount_amount'] = '0.00';
    $payload['lines'][0]['discount_amount'] = '0.00';
    $receipt = app(GoodsReceiptService::class)->createDraft($payload);
    $receipt = app(GoodsReceiptService::class)->confirm($receipt);

    $this->actingAs($warehouseUser)->put(route('inventory.receipts.update', $receipt), $payload)
        ->assertSessionHasErrors('receipt');

    expect($receipt->fresh()->invoice_no)->toBe('INV-IMMUTABLE-001');
    expect(fn () => $receipt->forceFill(['invoice_no' => 'MUTATED'])->save())
        ->toThrow(LogicException::class, 'Penerimaan yang sudah dikonfirmasi tidak dapat diubah.');
    expect(fn () => $receipt->items()->firstOrFail()->forceFill(['batch_no' => 'MUTATED'])->save())
        ->toThrow(LogicException::class, 'Baris penerimaan yang sudah dikonfirmasi tidak dapat diubah.');
    $this->assertDatabaseCount('stock_movements', 1);
});
