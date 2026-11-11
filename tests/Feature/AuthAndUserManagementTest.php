<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('authenticates an active user by username and resets failed login state', function () {
    $user = User::factory()->create([
        'username' => 'active.apothecary',
        'password' => 'Secure-password-123',
        'failed_attempts' => 3,
    ]);

    $response = $this->post(route('login.store'), [
        'username' => 'active.apothecary',
        'password' => 'Secure-password-123',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
    $response->assertSessionHas('last_activity_at');
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'failed_attempts' => 0,
        'locked_until' => null,
    ]);

    expect($user->fresh()->last_login_at)->not->toBeNull();
    expect(Hash::check('Secure-password-123', $user->fresh()->password))->toBeTrue();
});

it('records an incorrect password without exposing whether the username exists', function () {
    $user = User::factory()->create([
        'username' => 'wrong-password-user',
        'password' => 'Secure-password-123',
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'username' => 'wrong-password-user',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'username' => 'Nama pengguna atau kata sandi salah, atau akun tidak aktif/terkunci.',
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'failed_attempts' => 1,
        'locked_until' => null,
    ]);
});

it('locks an account after five consecutive failed login attempts', function () {
    $user = User::factory()->create([
        'username' => 'lockable-user',
        'password' => 'Secure-password-123',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'username' => 'lockable-user',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'));
    }

    $lockedUser = $user->fresh();

    expect($lockedUser->failed_attempts)->toBe(5);
    expect($lockedUser->locked_until)->not->toBeNull();
    expect($lockedUser->locked_until->isFuture())->toBeTrue();

    $this->post(route('login.store'), [
        'username' => 'lockable-user',
        'password' => 'Secure-password-123',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('allows a user to sign in after the lockout expires', function () {
    $user = User::factory()->create([
        'username' => 'expired-lockout-user',
        'password' => 'Secure-password-123',
        'failed_attempts' => 5,
        'locked_until' => now()->subSecond(),
    ]);

    $this->post(route('login.store'), [
        'username' => 'expired-lockout-user',
        'password' => 'Secure-password-123',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'failed_attempts' => 0,
        'locked_until' => null,
    ]);
});

it('rejects login for an inactive account', function () {
    $user = User::factory()->create([
        'username' => 'inactive-user',
        'password' => 'Secure-password-123',
        'is_active' => false,
    ]);

    $this->from(route('login'))->post(route('login.store'), [
        'username' => 'inactive-user',
        'password' => 'Secure-password-123',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
    ]);
});

it('ends an authenticated session after thirty minutes of inactivity', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
        ->get(route('home'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('rate limits login attempts by username and IP address', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post(route('login.store'), [
            'username' => 'missing-user',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'));
    }

    $this->post(route('login.store'), [
        'username' => 'missing-user',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('logs out and invalidates the authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->timestamp])
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('ends an authenticated session when its account is deactivated', function () {
    $user = User::factory()->create();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->timestamp])
        ->get(route('home'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('forbids users without user-management permission and hides the menu item', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee(route('users.index'));
});

it('creates and updates a user with one assigned role and a hashed password', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');

    $this->actingAs($manager)->post(route('users.store'), [
        'username' => 'cashier.new',
        'name' => 'Kasir Baru',
        'email' => null,
        'password' => 'Secure-password-456',
        'password_confirmation' => 'Secure-password-456',
        'role' => 'kasir',
    ])->assertRedirect(route('users.index'));

    $createdUser = User::query()->where('username', 'cashier.new')->firstOrFail();

    $this->assertModelExists($createdUser);
    expect(Hash::check('Secure-password-456', $createdUser->password))->toBeTrue();
    expect($createdUser->hasRole('kasir'))->toBeTrue();

    $this->put(route('users.update', $createdUser), [
        'username' => 'cashier.updated',
        'name' => 'Kasir Diperbarui',
        'email' => null,
        'password' => '',
        'password_confirmation' => '',
        'role' => 'gudang',
    ])->assertRedirect(route('users.index'));

    $createdUser->refresh();
    expect($createdUser->username)->toBe('cashier.updated');
    expect($createdUser->hasRole('gudang'))->toBeTrue();
    expect(Hash::check('Secure-password-456', $createdUser->password))->toBeTrue();
});

it('deactivates another user without deleting the account', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $target = User::factory()->create();

    $this->actingAs($manager)
        ->patch(route('users.status', $target), ['is_active' => false])
        ->assertRedirect(route('users.index'));

    $this->assertModelExists($target);
    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'is_active' => false,
    ]);
});

it('provisions the first apothecary through interactive prompts', function () {
    $this->artisan('app:create-initial-apothecary')
        ->expectsQuestion('Nama pengguna', 'first.apothecary')
        ->expectsQuestion('Nama apoteker', 'Apoteker Pertama')
        ->expectsQuestion('Kata sandi (minimal 12 karakter)', 'Secure-bootstrap-123')
        ->expectsQuestion('Konfirmasi kata sandi', 'Secure-bootstrap-123')
        ->assertSuccessful();

    $user = User::query()->where('username', 'first.apothecary')->firstOrFail();

    expect($user->hasRole('apoteker'))->toBeTrue();
    expect(Hash::check('Secure-bootstrap-123', $user->password))->toBeTrue();
});

it('seeds the five initial roles and their proposed permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->where('guard_name', 'web')->pluck('name')->all())
        ->toContain('apoteker', 'tenaga_farmasi', 'kasir', 'gudang', 'keuangan');

    expect(Permission::query()->where('guard_name', 'web')->pluck('name')->all())
        ->toContain('core.users.view', 'core.users.create', 'core.users.update', 'core.users.deactivate')
        ->toContain('md.item.manage', 'inventory.receipt.manage', 'pos.sale.create', 'rx.prescription.manage');
});
