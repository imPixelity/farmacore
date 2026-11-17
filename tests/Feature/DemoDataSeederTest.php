<?php

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a repeatable demo environment with working role accounts and business transactions', function () {
    config(['demo.password' => 'DemoPassword-For-Tests']);

    $this->seed(DemoDataSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('items', 3);
    $this->assertDatabaseCount('goods_receipts', 1);
    $this->assertDatabaseCount('item_batches', 3);
    $this->assertDatabaseCount('stock_movements', 5);
    $this->assertDatabaseCount('sales', 2);
    $this->assertDatabaseCount('receivables', 1);
    $this->assertDatabaseCount('prescriptions', 1);

    $apoteker = User::query()->where('username', 'demo.apoteker')->firstOrFail();
    expect($apoteker->hasRole('apoteker'))->toBeTrue();

    $this->post(route('login.store'), [
        'username' => 'demo.apoteker',
        'password' => 'DemoPassword-For-Tests',
    ])->assertRedirect(route('home'));

    $this->seed(DemoDataSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('items', 3);
    $this->assertDatabaseCount('goods_receipts', 1);
    $this->assertDatabaseCount('item_batches', 3);
    $this->assertDatabaseCount('stock_movements', 5);
    $this->assertDatabaseCount('sales', 2);
    $this->assertDatabaseCount('receivables', 1);
    $this->assertDatabaseCount('prescriptions', 1);
});

it('requires a demo password before writing demo records', function () {
    config(['demo.password' => null]);

    expect(fn () => app(DemoDataSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Set FARMACORE_DEMO_PASSWORD');

    $this->assertDatabaseCount('users', 0);
});
