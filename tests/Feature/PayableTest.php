<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PayablePayment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);
});

it('pays a supplier payable partially and posts cash out with audit records', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create(['current_balance' => '1000.00']);
    $payable = Payable::factory()->create(['total_amount' => '750.00', 'paid_amount' => '0.00']);

    $this->actingAs($financeUser)
        ->post(route('purchasing.payables.pay', $payable), [
            'cash_bank_account_id' => $account->id,
            'payment_date' => '2026-11-12',
            'amount' => '250.00',
            'reference_no' => 'TRF-001',
        ])
        ->assertRedirect(route('purchasing.payables.show', $payable));

    $payable->refresh();
    $payment = PayablePayment::query()->firstOrFail();
    $transaction = FinancialTransaction::query()->firstOrFail();

    expect($payable->paid_amount)->toBe('250.00');
    expect($payable->status)->toBe('sebagian');
    expect($payment->amount)->toBe('250.00');
    expect($transaction->direction)->toBe('out');
    expect($transaction->ref_id)->toBe($payment->id);
    expect($account->fresh()->current_balance)->toBe('750.00');
    expect(AuditLog::query()->where('auditable_type', PayablePayment::class)->where('auditable_id', $payment->id)->where('event', 'created')->exists())->toBeTrue();
});

it('rejects payment above the remaining supplier payable without changing balances', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create(['current_balance' => '1000.00']);
    $payable = Payable::factory()->create(['total_amount' => '750.00', 'paid_amount' => '250.00', 'status' => 'sebagian']);

    $this->actingAs($financeUser)
        ->post(route('purchasing.payables.pay', $payable), [
            'cash_bank_account_id' => $account->id,
            'payment_date' => '2026-11-12',
            'amount' => '500.01',
        ])
        ->assertSessionHasErrors('amount');

    expect($payable->fresh()->paid_amount)->toBe('250.00');
    expect(PayablePayment::query()->count())->toBe(0);
    expect(FinancialTransaction::query()->count())->toBe(0);
    expect($account->fresh()->current_balance)->toBe('1000.00');
});

it('marks a supplier payable as paid when the final balance is settled', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create(['current_balance' => '1000.00']);
    $payable = Payable::factory()->create(['total_amount' => '750.00', 'paid_amount' => '250.00', 'status' => 'sebagian']);

    $this->actingAs($financeUser)
        ->post(route('purchasing.payables.pay', $payable), [
            'cash_bank_account_id' => $account->id,
            'payment_date' => '2026-11-12',
            'amount' => '500.00',
        ])
        ->assertRedirect();

    expect($payable->fresh()->status)->toBe('lunas');
    expect(PayablePayment::query()->count())->toBe(1);
    expect($account->fresh()->current_balance)->toBe('500.00');
});
