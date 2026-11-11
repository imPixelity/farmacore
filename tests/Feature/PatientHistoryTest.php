<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
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

it('shows a patient timeline and audits opening the sensitive history', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('apoteker');
    $patient = Patient::factory()->create(['name' => 'Pasien Riwayat']);
    $prescription = Prescription::factory()->for($patient)->create(['rx_no' => 'RX-HISTORY-01']);
    $staff = PharmacyStaff::factory()->create(['name' => 'Petugas Riwayat']);
    PrescriptionScreening::factory()->create(['prescription_id' => $prescription->id, 'pharmacy_staff_id' => $staff->id]);
    PrescriptionCompounding::factory()->create(['prescription_id' => $prescription->id, 'pharmacy_staff_id' => $staff->id]);
    PrescriptionDispense::factory()->create(['prescription_id' => $prescription->id, 'pharmacy_staff_id' => $staff->id]);

    $this->actingAs($viewer)
        ->get(route('rx.patient-history.show', $patient))
        ->assertOk()
        ->assertSee('RX-HISTORY-01')
        ->assertSee('Screening')
        ->assertSee('Racikan')
        ->assertSee('Obat diserahkan');

    expect(AuditLog::query()->where('user_id', $viewer->id)->where('event', 'viewed')->where('auditable_type', Patient::class)->where('auditable_id', $patient->id)->exists())->toBeTrue();
});

it('filters pharmacy activity by staff and date range', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('apoteker');
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $firstStaff = PharmacyStaff::factory()->for($firstUser, 'user')->create(['name' => 'Staff Satu']);
    $secondStaff = PharmacyStaff::factory()->for($secondUser, 'user')->create(['name' => 'Staff Dua']);
    $firstPrescription = Prescription::factory()->for($patient)->for($doctor)->create();
    $secondPrescription = Prescription::factory()->for($patient)->for($doctor)->create();
    PrescriptionScreening::factory()->create(['prescription_id' => $firstPrescription->id, 'pharmacy_staff_id' => $firstStaff->id, 'performed_at' => '2026-11-10 10:00:00']);
    PrescriptionScreening::factory()->create(['prescription_id' => $secondPrescription->id, 'pharmacy_staff_id' => $secondStaff->id, 'performed_at' => '2026-11-11 10:00:00']);

    $this->actingAs($viewer)
        ->get(route('rx.staff-activity.index', ['staff_id' => $firstStaff->id, 'from' => '2026-11-10', 'to' => '2026-11-10']))
        ->assertOk()
        ->assertSee('Staff Satu')
        ->assertSee($firstPrescription->rx_no)
        ->assertDontSee($secondPrescription->rx_no);
});

it('denies patient history access without the dedicated permission', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $patient = Patient::factory()->create();

    $this->actingAs($cashier)
        ->get(route('rx.patient-history.show', $patient))
        ->assertForbidden();
});
