<?php

use App\Models\User;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Md\Models\Item;
use App\Modules\Pos\Models\Sale;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Rx\Models\Prescription;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows only the sales and item summaries available to a cashier', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    Sale::factory()->for($cashier, 'cashier')->create([
        'invoice_no' => 'INV-DASHBOARD-001',
        'grand_total' => '42500.00',
    ]);
    Item::factory()->create();
    Payable::factory()->create();
    Prescription::factory()->create();

    $this->actingAs($cashier)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Penjualan hari ini')
        ->assertSee('42.500,00')
        ->assertSee('INV-DASHBOARD-001')
        ->assertSee('Barang aktif')
        ->assertDontSee('Utang supplier')
        ->assertDontSee('Piutang pelanggan')
        ->assertDontSee('Antrean resep')
        ->assertDontSee('Batch tersedia');
});

it('shows the prescription queue only to users with prescription access', function () {
    $pharmacyStaff = User::factory()->create();
    $pharmacyStaff->assignRole('tenaga_farmasi');
    Prescription::factory()->create();
    Payable::factory()->create();

    $this->actingAs($pharmacyStaff)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Antrean resep')
        ->assertSee('Resep yang masih dalam proses pelayanan')
        ->assertDontSee('Utang supplier')
        ->assertDontSee('Piutang pelanggan')
        ->assertDontSee('Batch tersedia');
});

it('shows payable and receivable summaries only to finance users', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    Payable::factory()->create();
    Receivable::factory()->create();
    Prescription::factory()->create();

    $this->actingAs($financeUser)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Utang supplier')
        ->assertSee('Piutang pelanggan')
        ->assertDontSee('Antrean resep')
        ->assertDontSee('Batch tersedia');
});
