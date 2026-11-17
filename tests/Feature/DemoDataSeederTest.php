<?php

use App\Livewire\Pos\PosScreen;
use App\Models\User;
use App\Modules\Md\Models\Item;
use App\Modules\Rx\Models\Prescription;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a repeatable demo environment with working role accounts and business transactions', function () {
    config(['demo.password' => 'DemoPassword-For-Tests']);

    $this->seed(DemoDataSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('items', 3);
    $this->assertDatabaseCount('goods_receipts', 1);
    $this->assertDatabaseCount('item_batches', 3);
    $this->assertDatabaseCount('stock_movements', 5);
    $this->assertDatabaseCount('sales', 2);
    $this->assertDatabaseCount('receivables', 1);
    $this->assertDatabaseCount('prescriptions', 1);

    $apoteker = User::query()->where('username', 'demo.apoteker')->firstOrFail();
    expect($apoteker->hasRole('apoteker'))->toBeTrue();

    $this->post(route('login.store'), [
        'username' => 'demo.apoteker',
        'password' => 'DemoPassword-For-Tests',
    ])->assertRedirect(route('home'));

    $this->seed(DemoDataSeeder::class);

    $this->assertDatabaseCount('prescription_screenings', 1);
    $this->assertDatabaseHas('prescriptions', [
        'notes' => 'DATA DEMO FARMA CORE - resep demo terverifikasi',
        'status' => 'verified',
    ]);
    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('items', 3);
    $this->assertDatabaseCount('goods_receipts', 1);
    $this->assertDatabaseCount('item_batches', 3);
    $this->assertDatabaseCount('stock_movements', 5);
    $this->assertDatabaseCount('sales', 2);
    $this->assertDatabaseCount('receivables', 1);
    $this->assertDatabaseCount('prescriptions', 1);
});

it('allows adding demo amoxicillin to the POS from its verified prescription', function () {
    config(['demo.password' => 'DemoPassword-For-Tests']);

    $this->seed(DemoDataSeeder::class);

    $cashier = User::query()->where('username', 'demo.kasir')->firstOrFail();
    $amoxicillin = Item::query()->where('code', 'DEMO-OBT-002')->firstOrFail();
    $batch = $amoxicillin->batches()->firstOrFail();
    $prescription = Prescription::query()->where('notes', 'DATA DEMO FARMA CORE - resep demo terverifikasi')->firstOrFail();

    $this->assertDatabaseHas('item_prices', [
        'item_id' => $amoxicillin->id,
        'sales_type' => 'resep',
        'is_current' => true,
    ]);
    $this->assertDatabaseMissing('item_prices', [
        'item_id' => $amoxicillin->id,
        'sales_type' => 'obat_bebas',
        'is_current' => true,
    ]);
    expect($amoxicillin->dosage_form)->toBe($amoxicillin->baseUnit->name);
    expect(bccomp((string) $batch->movements()->sum('qty_change'), (string) $batch->qty_on_hand, 3))->toBe(0);

    Livewire::actingAs($cashier)
        ->test(PosScreen::class)
        ->call('addPrescription', $prescription->id)
        ->assertSet('salesType', 'resep')
        ->assertSet('cart.rx-item-'.$prescription->items->first()->id.'.quantity', '10.000')
        ->assertSee('Amoxicillin 500 mg')
        ->assertSee('Stok tersedia: 60 '.$amoxicillin->baseUnit->name)
        ->assertDontSee('Harga Amoxicillin 500 mg untuk jenis penjualan ini belum tersedia.');
});

it('requires a demo password before writing demo records', function () {
    config(['demo.password' => null]);

    expect(fn () => app(DemoDataSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Set FARMACORE_DEMO_PASSWORD');

    $this->assertDatabaseCount('users', 0);
});
