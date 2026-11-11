<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

#[Signature('app:create-initial-apothecary')]
#[Description('Provision the first apoteker account interactively')]
class CreateInitialApothecary extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('Akun apoteker awal hanya dapat dibuat saat belum ada pengguna.');

            return self::FAILURE;
        }

        $username = $this->ask('Nama pengguna');
        $name = $this->ask('Nama apoteker');
        $password = $this->secret('Kata sandi (minimal 12 karakter)');
        $passwordConfirmation = $this->secret('Konfirmasi kata sandi');

        $validated = Validator::make([
            'username' => $username,
            'name' => $name,
        ], [
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')],
            'name' => ['required', 'string', 'max:100'],
        ])->validate();

        if (! is_string($password) || strlen($password) < 12 || ! hash_equals($password, (string) $passwordConfirmation)) {
            $this->error('Kata sandi tidak valid atau konfirmasinya tidak cocok.');

            return self::FAILURE;
        }

        if ($this->call('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--no-interaction' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $role = Role::query()->where('name', 'apoteker')->where('guard_name', 'web')->firstOrFail();

        DB::transaction(function () use ($validated, $password, $role): void {
            $user = User::query()->create([
                'username' => $validated['username'],
                'name' => $validated['name'],
                'password' => $password,
            ]);

            $user->assignRole($role);
        });

        $this->info('Akun apoteker awal berhasil dibuat.');

        return self::SUCCESS;
    }
}
