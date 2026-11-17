<?php

namespace Tests\Browser;

use App\Models\User;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionItem;
use App\Modules\Rx\Models\PrescriptionScreening;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('approves prescription screening from the prescription detail page', function (): void {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    PharmacyStaff::factory()->for($staffUser, 'user')->create(['name' => 'Petugas browser']);

    $item = Item::factory()->create(['name' => 'Obat resep browser']);
    $prescription = Prescription::factory()->create([
        'rx_no' => 'RX-BROWSER-01',
        'patient_id' => Patient::factory(),
        'doctor_id' => Doctor::factory(),
    ]);
    PrescriptionItem::factory()->for($prescription)->for($item)->create([
        'unit_id' => $item->base_unit_id,
        'qty_prescribed' => '4.000',
        'dosage_instruction' => 'Satu tablet tiga kali sehari',
    ]);

    $this->actingAs($staffUser);

    visit('/rx/prescriptions/'.$prescription->id)
        ->assertSee('RX-BROWSER-01')
        ->assertSee('Screening resep')
        ->assertSee('Obat resep browser')
        ->click('Setujui screening')
        ->assertSee('verified')
        ->assertSee('Hasil penyiapan / racikan')
        ->assertSee('Screening: disetujui')
        ->assertNoJavaScriptErrors();

    expect($prescription->fresh()->status)->toBe('verified')
        ->and(PrescriptionScreening::query()->where('prescription_id', $prescription->id)->count())->toBe(1);
});
