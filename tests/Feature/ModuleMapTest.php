<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('requires authentication to open the module map', function () {
    $this->get(route('modules.index'))
        ->assertRedirect(route('login'));
});

it('shows requirement references and modules available to an apothecary', function () {
    $apothecary = User::factory()->create();
    $apothecary->assignRole('apoteker');

    $this->actingAs($apothecary)
        ->get(route('modules.index'))
        ->assertOk()
        ->assertSee('Alur bisnis utama')
        ->assertSee('REQ-MD-01–17')
        ->assertSee('REQ-INV-01–21')
        ->assertSee('REQ-PUR-01–13')
        ->assertSee('REQ-POS-01–17')
        ->assertSee('REQ-RX-01–10')
        ->assertSee('REQ-ACC-01–10')
        ->assertSee('REQ-RPT-01–14')
        ->assertSee(route('inventory.stock.index'), escape: false)
        ->assertSee(route('purchasing.purchase-orders.index'), escape: false)
        ->assertSee(route('pos.index'), escape: false);
});

it('hides module cards and links the cashier is not allowed to access', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('modules.index'))
        ->assertOk()
        ->assertSee('REQ-MD-01–17')
        ->assertSee('REQ-POS-01–17')
        ->assertSee('REQ-RPT-01–14')
        ->assertSee(route('pos.index'), escape: false)
        ->assertDontSee('REQ-INV-01–21')
        ->assertDontSee('REQ-PUR-01–13')
        ->assertDontSee('REQ-RX-01–10')
        ->assertDontSee('REQ-ACC-01–10')
        ->assertDontSee(route('accounting.index'), escape: false);
});
