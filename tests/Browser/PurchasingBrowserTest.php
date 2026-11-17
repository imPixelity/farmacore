<?php

namespace Tests\Browser;

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PayablePayment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);
});

it('records a supplier payment from the payable detail page', function (): void {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create([
        'name' => 'Kas pembayaran browser',
        'current_balance' => '1000.00',
    ]);
    $payable = Payable::factory()->create([
        'invoice_no' => 'INV-BROWSER-PAY-01',
        'total_amount' => '750.00',
        'paid_amount' => '0.00',
    ]);

    $this->actingAs($financeUser);

    visit('/purchasing/payables/'.$payable->id)
        ->assertSee('INV-BROWSER-PAY-01')
        ->assertSee('Catat pembayaran')
        ->select('select[name="cash_bank_account_id"]', (string) $account->id)
        ->fill('input[name="payment_date"]', today()->toDateString())
        ->fill('input[name="amount"]', '250.00')
        ->fill('input[name="reference_no"]', 'BROWSER-PAY-001')
        ->click('Simpan pembayaran')
        ->assertSee('Sebagian')
        ->assertSee('BROWSER-PAY-001')
        ->assertSee('Rp 250,00')
        ->assertNoJavaScriptErrors();

    expect($payable->fresh()->paid_amount)->toBe('250.00')
        ->and($payable->fresh()->status)->toBe('sebagian')
        ->and($account->fresh()->current_balance)->toBe('750.00')
        ->and(PayablePayment::query()->count())->toBe(1);
});
