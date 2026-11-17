<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('opens a sales report and applies the selected date range', function (): void {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    $this->actingAs($pharmacist);

    visit('/reports')
        ->assertSee('Laporan operasional dan keuangan')
        ->click('Penjualan per jenis')
        ->assertSee('Tanggal mulai')
        ->assertSee('Tanggal akhir')
        ->fill('input[type="date"][name="from"]', today()->subDays(7)->toDateString())
        ->fill('input[type="date"][name="to"]', today()->toDateString())
        ->click('Tampilkan')
        ->assertSee('Periode '.today()->subDays(7)->format('d-m-Y').' sampai '.today()->format('d-m-Y'))
        ->assertSee('Tidak ada data laporan pada rentang tanggal ini.')
        ->assertNoJavaScriptErrors();
});
