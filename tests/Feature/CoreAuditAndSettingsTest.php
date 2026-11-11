<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Core\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('records user creation updates and deletion without secrets', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $subject = User::factory()->create([
        'username' => 'audited.user',
        'name' => 'Nama Awal',
        'password' => 'Secret-password-123',
    ]);
    $subject->update([
        'name' => 'Nama Baru',
        'password' => 'Another-secret-123',
    ]);
    $subject->delete();

    $logs = AuditLog::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $subject->id)
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(3);
    expect($logs[0]->event)->toBe('created');
    expect($logs[0]->user_id)->toBe($actor->id);
    expect($logs[0]->created_at)->not->toBeNull();
    expect($logs[0]->new_values)->toHaveKey('username', 'audited.user');
    expect($logs[0]->new_values)->not->toHaveKey('password');
    expect($logs[1]->event)->toBe('updated');
    expect($logs[1]->old_values)->toHaveKey('name', 'Nama Awal');
    expect($logs[1]->new_values)->toHaveKey('name', 'Nama Baru');
    expect($logs[1]->old_values)->not->toHaveKey('password');
    expect($logs[1]->new_values)->not->toHaveKey('password');
    expect($logs[2]->event)->toBe('deleted');
    expect($logs[2]->old_values)->toHaveKey('name', 'Nama Baru');
    expect($logs[2]->new_values)->toBeNull();
});

it('keeps audit logs append-only and exposes only the read-only viewer route', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $auditLog = AuditLog::factory()->create();

    expect(fn () => $auditLog->update(['event' => 'tampered']))->toThrow(LogicException::class);
    expect(fn () => $auditLog->delete())->toThrow(LogicException::class);

    $this->actingAs($apoteker)
        ->get(route('audit-logs.index'))
        ->assertOk();
    $this->actingAs($apoteker)->put('/audit-logs')->assertMethodNotAllowed();
    $this->actingAs($apoteker)->delete('/audit-logs')->assertMethodNotAllowed();
});

it('authorizes and filters the audit viewer', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)->get(route('audit-logs.index'))->assertForbidden();

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    AuditLog::factory()->create([
        'user_id' => $apoteker->id,
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => $apoteker->id,
        'new_values' => ['name' => '<script>alert(1)</script>'],
    ]);

    $this->actingAs($apoteker)
        ->get(route('audit-logs.index', ['event' => 'updated', 'model' => User::class]))
        ->assertOk()
        ->assertSee('updated')
        ->assertSee('&lt;script&gt;', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('audits role assignment changes made through user management', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $subject = User::factory()->create();
    $subject->assignRole('kasir');

    $this->actingAs($manager)->put(route('users.update', $subject), [
        'username' => $subject->username,
        'name' => $subject->name,
        'email' => $subject->email,
        'password' => '',
        'password_confirmation' => '',
        'role' => 'gudang',
    ])->assertRedirect(route('users.index'));

    $roleAudit = AuditLog::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $subject->id)
        ->where('event', 'updated')
        ->whereJsonContains('new_values->roles', 'gudang')
        ->firstOrFail();

    expect($roleAudit->old_values)->toHaveKey('roles', ['kasir']);
    expect($roleAudit->new_values)->toHaveKey('roles', ['gudang']);
});

it('reads defaults and invalidates cached application settings after writes', function () {
    $settings = app(SettingsService::class);

    expect($settings->get('login_max_attempts', 5))->toBe(5);
    expect($settings->get('session_timeout_minutes', 30))->toBe(30);

    $settings->setMany([
        'login_max_attempts' => 8,
        'session_timeout_minutes' => 45,
    ]);

    expect($settings->get('login_max_attempts', 5))->toBe(8);
    expect($settings->get('session_timeout_minutes', 30))->toBe(45);
    expect(AppSetting::query()->find('login_max_attempts')?->value)->toBe(8);
    expect(AppSetting::query()->find('session_timeout_minutes')?->value)->toBe(45);
});

it('authorizes setting changes and persists the validated values', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)->get(route('settings.index'))->assertForbidden();
    $this->actingAs($cashier)->put(route('settings.update'), [
        'login_max_attempts' => 7,
        'login_lockout_minutes' => 25,
        'session_timeout_minutes' => 40,
    ])->assertForbidden();

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    $this->actingAs($apoteker)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Konfigurasi FarmaCore');

    $this->actingAs($apoteker)->put(route('settings.update'), [
        'expiry_alert_days' => null,
        'payable_alert_days' => 14,
        'login_max_attempts' => 7,
        'login_lockout_minutes' => 25,
        'session_timeout_minutes' => 40,
        'price_rounding_step' => 100,
        'usage_average_months' => 3,
        'pharmacy_receipt_identity' => '<script>alert(2)</script>',
    ])->assertRedirect(route('settings.index'));

    expect(AppSetting::query()->find('login_max_attempts')?->value)->toBe(7);
    expect(AppSetting::query()->find('pharmacy_receipt_identity')?->value)->toBe('<script>alert(2)</script>');
    expect(AuditLog::query()->where('auditable_type', AppSetting::class)->where('event', 'created')->exists())->toBeTrue();

    $this->actingAs($apoteker)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>alert(2)</script>', escape: false);
});

it('applies configured login lockout and session timeout settings', function () {
    app(SettingsService::class)->setMany([
        'login_max_attempts' => 2,
        'login_lockout_minutes' => 7,
        'session_timeout_minutes' => 1,
    ]);

    $user = User::factory()->create([
        'username' => 'configurable.lockout',
        'password' => Hash::make('Correct-password-123'),
    ]);

    foreach (range(1, 2) as $attempt) {
        $this->post(route('login.store'), [
            'username' => 'configurable.lockout',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'));
    }

    expect($user->fresh()->failed_attempts)->toBe(2);
    expect($user->fresh()->locked_until->isAfter(now()->addMinutes(6)))->toBeTrue();

    $activeUser = User::factory()->create();
    $this->actingAs($activeUser)
        ->withSession(['last_activity_at' => now()->subSeconds(61)->timestamp])
        ->get(route('home'))
        ->assertRedirect(route('login'));
});
