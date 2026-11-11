<?php

use App\Models\User;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Models\Unit;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionCompounding;
use App\Modules\Rx\Models\PrescriptionDispense;
use App\Modules\Rx\Models\PrescriptionScreening;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('records a prescription and completes screening, preparation, and dispense stages', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    $staff = PharmacyStaff::factory()->for($staffUser, 'user')->create(['name' => 'Siti Farmasi']);
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $item = Item::factory()->create();
    $unit = Unit::factory()->create();

    $this->actingAs($staffUser)
        ->post(route('rx.prescriptions.store'), [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'rx_date' => '2026-11-12',
            'rx_group' => 'dokter_luar',
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'qty_prescribed' => '10.000',
                'dosage_instruction' => '2 kali sehari',
            ]],
        ])
        ->assertRedirect();

    $prescription = Prescription::query()->firstOrFail();
    expect($prescription->status)->toBe('received');

    $this->post(route('rx.prescriptions.transition', $prescription), [
        'target' => 'verified',
        'checklist' => ['patient_match' => true, 'dose_safe' => true, 'interaction_checked' => true],
    ])->assertRedirect();

    $this->post(route('rx.prescriptions.transition', $prescription), [
        'target' => 'prepared',
        'prepared_items' => [$prescription->items()->first()->id => '10.000'],
    ])->assertRedirect();

    $this->post(route('rx.prescriptions.transition', $prescription), [
        'target' => 'dispensed',
        'received_by_name' => 'Budi Pasien',
    ])->assertRedirect();

    expect($prescription->fresh()->status)->toBe('dispensed');
    expect(PrescriptionScreening::query()->where('prescription_id', $prescription->id)->count())->toBe(1);
    expect($prescription->items()->first()->prepared_qty)->toBe('10.000');
    expect(PrescriptionDispense::query()->where('prescription_id', $prescription->id)->first()->received_by_name)->toBe('Budi Pasien');
    expect($staff->fresh()->name)->toBe('Siti Farmasi');
});

it('rejects skipped stages and refuses dispense before verification', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    PharmacyStaff::factory()->for($staffUser, 'user')->create();
    $prescription = Prescription::factory()->create();

    $this->actingAs($staffUser)
        ->post(route('rx.prescriptions.transition', $prescription), [
            'target' => 'dispensed',
            'received_by_name' => 'Penerima',
        ])
        ->assertSessionHasErrors('status');

    expect($prescription->fresh()->status)->toBe('received');
    expect(PrescriptionDispense::query()->count())->toBe(0);
});

it('records a compounding result and its responsible pharmacy staff', function () {
    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    $staff = PharmacyStaff::factory()->for($staffUser, 'user')->create();
    $prescription = Prescription::factory()->create();
    $item = $prescription->items()->create([
        'item_id' => Item::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'qty_prescribed' => '2.000',
        'dosage_instruction' => 'Sesuai etiket',
        'is_compound' => true,
        'compound_group' => 'R1',
    ]);

    $this->actingAs($staffUser)->post(route('rx.prescriptions.transition', $prescription), [
        'target' => 'verified',
        'checklist' => ['patient_match' => true, 'dose_safe' => true, 'interaction_checked' => true],
    ]);
    $this->post(route('rx.prescriptions.transition', $prescription), [
        'target' => 'prepared',
        'prepared_items' => [$item->id => '2.000'],
        'compoundings' => [['compound_name' => 'Racikan R1', 'yield_qty' => '2.000', 'yield_unit' => 'bungkus']],
    ])->assertRedirect();

    $compounding = PrescriptionCompounding::query()->firstOrFail();
    expect($prescription->fresh()->status)->toBe('prepared');
    expect($compounding->pharmacy_staff_id)->toBe($staff->id);
});
