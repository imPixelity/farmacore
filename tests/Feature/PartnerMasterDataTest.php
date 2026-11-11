<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Models\Supplier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates updates and deactivates supplier records with audit history', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.suppliers.store'), [
        'name' => 'PBF Sehat Sentosa',
        'address' => 'Jalan Apotek 10',
        'phone' => '0211234567',
        'email' => 'sales@example.test',
        'contact_person' => 'Budi Santoso',
        'payment_terms' => 'Kredit 30 hari',
        'payment_term_days' => '30',
        'lead_time_days' => '2.5',
        'is_active' => '1',
    ])->assertRedirect();

    $supplier = Supplier::query()->where('name', 'PBF Sehat Sentosa')->firstOrFail();
    expect($supplier->created_by)->toBe($manager->id);
    expect($supplier->payment_term_days)->toBe(30);
    expect($supplier->lead_time_days)->toBe('2.5');

    $this->put(route('md.suppliers.update', $supplier), [
        'name' => 'PBF Sehat Sentosa Baru',
        'address' => 'Jalan Apotek 11',
        'phone' => '0217654321',
        'email' => 'order@example.test',
        'contact_person' => 'Siti Santoso',
        'payment_terms' => 'Tunai',
        'payment_term_days' => '0',
        'lead_time_days' => '0.5',
        'is_active' => '1',
    ])->assertRedirect();

    $this->patch(route('md.suppliers.status', $supplier))->assertRedirect();

    expect($supplier->fresh()->name)->toBe('PBF Sehat Sentosa Baru');
    expect($supplier->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()->where('auditable_type', Supplier::class)->where('auditable_id', $supplier->id)->where('event', 'created')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', Supplier::class)->where('auditable_id', $supplier->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('validates supplier contact and payment terms', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.suppliers.store'), [
        'name' => '',
        'email' => 'invalid-email',
        'payment_term_days' => '-1',
        'lead_time_days' => '-0.1',
        'is_active' => '1',
    ])->assertSessionHasErrors(['name', 'email', 'payment_term_days', 'lead_time_days']);

    expect(Supplier::query()->exists())->toBeFalse();
});

it('masks patient identity and contact details for roles without sensitive-data permission', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager)->post(route('md.patients.store'), [
        'code' => 'PAT-000001',
        'name' => 'Rina Pasien',
        'identity_no' => '1234567890123456',
        'phone' => '081234567890',
        'address' => 'Alamat privat nomor 99',
        'birth_date' => '1990-04-23',
        'is_active' => '1',
    ])->assertRedirect();

    $patient = Patient::query()->where('code', 'PAT-000001')->firstOrFail();
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('md.patients.index'))
        ->assertOk()
        ->assertSee('Rina Pasien')
        ->assertSee($patient->maskedIdentityNo())
        ->assertSee($patient->maskedPhone())
        ->assertSee('Disamarkan')
        ->assertDontSee('1234567890123456')
        ->assertDontSee('081234567890')
        ->assertDontSee('Alamat privat nomor 99')
        ->assertDontSee('1990-04-23');

    $this->get(route('md.patients.index', ['search' => '1234567890123456']))
        ->assertOk()
        ->assertDontSee('PAT-000001');

    expect($patient->toArray())->not->toHaveKeys(['identity_no', 'phone', 'address', 'birth_date']);
});

it('allows sensitive patient search to apothecaries and pharmacy staff and redacts PII from audit logs', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager)->post(route('md.patients.store'), [
        'code' => 'PAT-000002',
        'name' => 'Dewi Rahma',
        'identity_no' => '9876543210987654',
        'phone' => '081111222333',
        'address' => 'Jalan Rahasia 7',
        'birth_date' => '1988-08-15',
        'is_active' => '1',
    ])->assertRedirect();

    $patient = Patient::query()->where('code', 'PAT-000002')->firstOrFail();

    $this->get(route('md.patients.index', ['search' => '9876543210987654']))
        ->assertOk()
        ->assertSee('Dewi Rahma')
        ->assertSee('9876543210987654')
        ->assertSee('Jalan Rahasia 7');

    $pharmacyStaff = User::factory()->create();
    $pharmacyStaff->assignRole('tenaga_farmasi');
    $this->actingAs($pharmacyStaff)
        ->get(route('md.patients.index', ['search' => '9876543210987654']))
        ->assertOk()
        ->assertSee('9876543210987654')
        ->assertSee('081111222333')
        ->assertSee('Jalan Rahasia 7');

    $this->actingAs($manager)->put(route('md.patients.update', $patient), [
        'code' => 'PAT-000002',
        'name' => 'Dewi Rahma',
        'identity_no' => '9876543210123456',
        'phone' => '081999888777',
        'address' => 'Jalan Rahasia 8',
        'birth_date' => '1988-08-15',
        'is_active' => '1',
    ])->assertRedirect();

    $patientAudit = AuditLog::query()->where('auditable_type', Patient::class)->where('auditable_id', $patient->id)->get();
    $auditContents = $patientAudit->toJson();

    expect($auditContents)->not->toContain('9876543210987654');
    expect($auditContents)->not->toContain('081111222333');
    expect($auditContents)->not->toContain('Jalan Rahasia 7');
    expect($patientAudit->contains(fn (AuditLog $entry): bool => data_get($entry->new_values, 'redacted_fields') === ['identity_no', 'phone', 'address']))->toBeTrue();
});

it('validates patient codes and dates and audits sensitive-field changes without storing their values', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.patients.store'), [
        'code' => 'PAT-000003',
        'name' => 'Pasien Uji',
        'identity_no' => str_repeat('1', 31),
        'birth_date' => now()->addDay()->toDateString(),
        'is_active' => '1',
    ])->assertSessionHasErrors(['identity_no', 'birth_date']);

    $this->post(route('md.patients.store'), [
        'code' => 'PAT-000004',
        'name' => 'Pasien Uji',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Patient::query()->where('code', 'PAT-000004')->exists())->toBeTrue();
});

it('creates and updates doctors with the required origin and audit records', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.doctors.store'), [
        'name' => 'Dr. Adi Pratama',
        'identity_no' => 'SIP-12345678',
        'phone' => '081234567890',
        'origin' => 'luar',
        'is_active' => '1',
    ])->assertRedirect();

    $doctor = Doctor::query()->where('name', 'Dr. Adi Pratama')->firstOrFail();
    $this->put(route('md.doctors.update', $doctor), [
        'name' => 'Dr. Adi Pratama',
        'identity_no' => 'SIP-87654321',
        'phone' => '081234567891',
        'origin' => 'apotek',
        'is_active' => '1',
    ])->assertRedirect();
    $this->patch(route('md.doctors.status', $doctor))->assertRedirect();

    expect($doctor->fresh()->origin)->toBe('apotek');
    expect($doctor->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()->where('auditable_type', Doctor::class)->where('auditable_id', $doctor->id)->where('event', 'updated')->exists())->toBeTrue();

    $this->post(route('md.doctors.store'), [
        'name' => 'Dokter Tanpa Asal',
        'origin' => 'internal',
        'is_active' => '1',
    ])->assertSessionHasErrors('origin');
});

it('links a pharmacy staff profile to one active user with the pharmacy-staff role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $staffUser = User::factory()->create(['name' => 'Nadia Farmasi']);
    $staffUser->assignRole('tenaga_farmasi');
    $this->actingAs($manager);

    $this->post(route('md.pharmacy-staff.store'), [
        'user_id' => $staffUser->id,
        'name' => 'Nadia Farmasi',
        'position' => 'Apoteker pendamping',
        'is_active' => '1',
    ])->assertRedirect();

    $staff = PharmacyStaff::query()->where('user_id', $staffUser->id)->firstOrFail();
    expect($staff->user->is($staffUser))->toBeTrue();
    expect($staffUser->fresh()->pharmacyStaff->is($staff))->toBeTrue();

    $this->put(route('md.pharmacy-staff.update', $staff), [
        'user_id' => $staffUser->id,
        'name' => 'Nadia Farmasi',
        'position' => 'Penanggung jawab pelayanan',
        'is_active' => '1',
    ])->assertRedirect();
    $this->patch(route('md.pharmacy-staff.status', $staff))->assertRedirect();

    expect($staff->fresh()->position)->toBe('Penanggung jawab pelayanan');
    expect($staff->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()->where('auditable_type', PharmacyStaff::class)->where('auditable_id', $staff->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('rejects users without the pharmacy-staff role and duplicate staff assignments', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    $this->actingAs($manager);

    $payload = [
        'user_id' => $cashier->id,
        'name' => 'Akun Kasir',
        'position' => 'Tidak sesuai',
        'is_active' => '1',
    ];
    $this->post(route('md.pharmacy-staff.store'), $payload)->assertSessionHasErrors('user_id');

    $payload['user_id'] = $staffUser->id;
    $this->post(route('md.pharmacy-staff.store'), $payload)->assertRedirect();
    $this->post(route('md.pharmacy-staff.store'), $payload)->assertSessionHasErrors('user_id');

    expect(PharmacyStaff::query()->where('user_id', $staffUser->id)->count())->toBe(1);
});

it('creates updates and deactivates health services with validated fees', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.health-services.store'), [
        'name' => 'Cek kolesterol',
        'default_fee' => '25000.50',
        'is_active' => '1',
    ])->assertRedirect();

    $service = HealthService::query()->where('name', 'Cek kolesterol')->firstOrFail();
    $this->put(route('md.health-services.update', $service), [
        'name' => 'Cek kolesterol lengkap',
        'default_fee' => '35000.00',
        'is_active' => '1',
    ])->assertRedirect();
    $this->patch(route('md.health-services.status', $service))->assertRedirect();

    expect($service->fresh()->default_fee)->toBe('35000.00');
    expect($service->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()->where('auditable_type', HealthService::class)->where('auditable_id', $service->id)->where('event', 'updated')->exists())->toBeTrue();

    $this->post(route('md.health-services.store'), [
        'name' => 'Biaya tidak valid',
        'default_fee' => '-0.01',
        'is_active' => '1',
    ])->assertSessionHasErrors('default_fee');
});

it('authorizes read and management access and never exposes master-data deletion routes', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier);

    $this->get(route('md.suppliers.index'))->assertOk();
    $this->get(route('md.patients.index'))->assertOk();
    $this->get(route('md.doctors.index'))->assertOk();
    $this->get(route('md.pharmacy-staff.index'))->assertOk();
    $this->get(route('md.health-services.index'))->assertOk();

    $this->get(route('md.suppliers.create'))->assertForbidden();
    $this->get(route('md.patients.create'))->assertForbidden();
    $this->post('/master/suppliers', [])->assertForbidden();
    $this->delete('/master/suppliers/1')->assertMethodNotAllowed();
    $this->delete('/master/patients/1')->assertMethodNotAllowed();
    $this->delete('/master/doctors/1')->assertMethodNotAllowed();
    $this->delete('/master/pharmacy-staff/1')->assertMethodNotAllowed();
    $this->delete('/master/health-services/1')->assertMethodNotAllowed();
});
