<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Services\ReceivableService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);
});

function receivableActor(): User
{
    $user = User::factory()->create();
    $user->assignRole('keuangan');

    return $user;
}

it('protects the receivable screen and allows authorized finance users to view it', function () {
    $this->get(route('accounting.receivables.index'))->assertRedirect(route('login'));

    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $this->actingAs($cashier)->get(route('accounting.receivables.index'))->assertForbidden();

    $this->actingAs(receivableActor())->get(route('accounting.receivables.index'))->assertOk()->assertSee('Piutang pelanggan');
});

it('records partial and final payments, updates status, and posts cash income', function () {
    $this->actingAs(receivableActor());
    $receivable = Receivable::factory()->create([
        'total_amount' => '500.00',
        'paid_amount' => '0.00',
        'status' => 'belum_dibayar',
    ]);
    $account = CashBankAccount::factory()->create();
    $service = app(ReceivableService::class);

    $partial = $service->pay($receivable, [
        'amount' => '200.00',
        'cash_bank_account_id' => $account->id,
        'payment_date' => '2026-11-12',
    ]);

    expect($partial->status)->toBe('sebagian');
    expect($partial->paid_amount)->toBe('200.00');
    expect($account->fresh()->current_balance)->toBe('200.00');
    expect(FinancialTransaction::query()->count())->toBe(1);

    $complete = $service->pay($partial, [
        'amount' => '300.00',
        'cash_bank_account_id' => $account->id,
        'payment_date' => '2026-11-12',
    ]);

    expect($complete->status)->toBe('lunas');
    expect($complete->paid_amount)->toBe('500.00');
    expect($complete->payments)->toHaveCount(2);
    expect($account->fresh()->current_balance)->toBe('500.00');
    expect(FinancialTransaction::query()->count())->toBe(2);
    expect(AuditLog::query()->where('auditable_type', Receivable::class)->where('auditable_id', $receivable->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('rejects payment above the remaining balance without changing the receivable or account', function () {
    $this->actingAs(receivableActor());
    $receivable = Receivable::factory()->create([
        'total_amount' => '500.00',
        'paid_amount' => '150.00',
        'status' => 'sebagian',
    ]);
    $account = CashBankAccount::factory()->create(['current_balance' => '100.00']);

    expect(fn () => app(ReceivableService::class)->pay($receivable, [
        'amount' => '351.00',
        'cash_bank_account_id' => $account->id,
        'payment_date' => '2026-11-12',
    ]))->toThrow(ValidationException::class);

    expect($receivable->fresh()->paid_amount)->toBe('150.00');
    expect($receivable->payments()->count())->toBe(0);
    expect($account->fresh()->current_balance)->toBe('100.00');
    expect(FinancialTransaction::query()->count())->toBe(0);
});

it('validates and posts a payment through the HTTP endpoint', function () {
    $financeUser = receivableActor();
    $receivable = Receivable::factory()->create(['total_amount' => '250.00']);
    $account = CashBankAccount::factory()->create();

    $this->actingAs($financeUser)
        ->post(route('accounting.receivables.pay', $receivable), [
            'amount' => '250.00',
            'cash_bank_account_id' => $account->id,
            'payment_date' => '2026-11-12',
        ])
        ->assertRedirect(route('accounting.receivables.index'));

    expect($receivable->fresh()->status)->toBe('lunas');
    expect($account->fresh()->current_balance)->toBe('250.00');
});
