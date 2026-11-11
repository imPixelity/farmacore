<?php

use App\Livewire\Pos\PosScreen;
use App\Models\User;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\DiscountRule;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function preparePosItem(string $price = '1200.00', string $stock = '10.000'): Item
{
    $item = Item::factory()->create();
    ItemPrice::factory()->for($item)->create([
        'sales_type' => 'obat_bebas',
        'selling_price' => $price,
    ]);
    $batch = ItemBatch::factory()->for($item)->create();
    app(StockService::class)->increase($batch, $stock, 'receipt', 'Stok awal test');

    return $item->fresh();
}

it('shows the protected POS screen only to authenticated users with view permission', function () {
    $this->get(route('pos.index'))->assertRedirect(route('login'));

    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)->get(route('pos.index'))->assertOk()->assertSee('Keranjang penjualan');
});

it('adds items, rejects insufficient stock, and recalculates the cart total', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);
    $item = preparePosItem('1200.00', '2.000');

    Livewire::test(PosScreen::class)
        ->call('addItem', $item->id)
        ->assertSet('cart.item-'.$item->id.'.quantity', '1.000')
        ->assertSee('Rp 1.200,00')
        ->call('updateQuantity', 'item-'.$item->id, '3.000')
        ->assertHasErrors('cart')
        ->assertSet('cart.item-'.$item->id.'.quantity', '1.000');
});

it('applies the active discount rule to the displayed cart total', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);
    $item = preparePosItem('1000.00');
    DiscountRule::factory()->create([
        'name' => 'Diskon POS',
        'rule_type' => 'qty',
        'sales_type' => 'obat_bebas',
        'item_id' => $item->id,
        'min_qty' => '2.000',
        'discount_type' => 'percent',
        'discount_value' => '10.00',
        'is_active' => true,
        'effective_from' => today()->subDay(),
    ]);

    Livewire::test(PosScreen::class)
        ->call('addItem', $item->id)
        ->call('updateQuantity', 'item-'.$item->id, '2.000')
        ->assertSee('Diskon Rp 200,00')
        ->assertSee('Rp 1.800,00');
});

it('loads only verified prescription items into the cart', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);
    $item = preparePosItem('1500.00');
    $prescription = Prescription::factory()->create();
    Prescription::duringWorkflow(function () use ($prescription): void {
        $prescription->forceFill(['status' => 'verified'])->save();
    });
    $prescriptionItem = PrescriptionItem::factory()->for($prescription)->for($item)->create([
        'unit_id' => $item->base_unit_id,
        'qty_prescribed' => '2.000',
    ]);

    Livewire::test(PosScreen::class)
        ->call('addPrescription', $prescription->id)
        ->assertSet('cart.rx-item-'.$prescriptionItem->id.'.quantity', '2.000')
        ->assertSee($prescription->rx_no)
        ->assertSee('Rp 3.000,00');
});

it('reports an unknown barcode without interrupting the cart', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);

    Livewire::test(PosScreen::class)
        ->set('barcode', 'UNKNOWN-123')
        ->call('scanBarcode')
        ->assertHasErrors(['barcode' => 'Barcode tidak dikenal. Transaksi tetap dapat dilanjutkan.'])
        ->assertSet('cart', []);
});
