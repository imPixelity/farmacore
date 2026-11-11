<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransactionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(TransactionCategorySeeder::class);
});

it('creates an account with opening and current balances through the finance screen', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');

    $this->actingAs($financeUser)
        ->post(route('accounting.accounts.store'), [
            'name' => 'Kas Utama',
            'type' => 'kas',
            'opening_balance' => '250000.00',
        ])
        ->assertRedirect(route('accounting.index'));

    $account = CashBankAccount::query()->where('name', 'Kas Utama')->firstOrFail();

    expect($account->opening_balance)->toBe('250000.00');
    expect($account->current_balance)->toBe('250000.00');
    expect(AuditLog::query()->where('auditable_type', CashBankAccount::class)->where('auditable_id', $account->id)->where('event', 'created')->exists())->toBeTrue();
});

it('posts incoming amounts through the financial posting service', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();
    $category = TransactionCategory::query()->where('source_code', 'SALE_RX')->firstOrFail();

    $transaction = app(FinancialPostingService::class)->post([
        'cash_bank_account_id' => $account->id,
        'category_id' => $category->id,
        'direction' => 'in',
        'trx_date' => '2026-11-11',
        'amount' => '125.50',
        'description' => 'Pemasukan penjualan resep',
    ]);

    expect($transaction->direction)->toBe('in');
    expect($account->fresh()->current_balance)->toBe('125.50');
});

it('posts a categorized manual expense and updates the locked account balance', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create([
        'name' => 'Kas Operasional',
        'opening_balance' => '250000.00',
        'current_balance' => '250000.00',
    ]);
    $category = TransactionCategory::query()->where('source_code', 'ELECTRICITY')->firstOrFail();

    $this->actingAs($financeUser)
        ->post(route('accounting.expenses.store'), [
            'cash_bank_account_id' => $account->id,
            'category_id' => $category->id,
            'trx_date' => '2026-11-11',
            'amount' => '75000.00',
            'description' => 'Tagihan listrik bulan berjalan',
        ])
        ->assertRedirect(route('accounting.index'));

    $transaction = FinancialTransaction::query()->where('description', 'Tagihan listrik bulan berjalan')->firstOrFail();

    expect($transaction->direction)->toBe('out');
    expect($transaction->amount)->toBe('75000.00');
    expect($account->fresh()->current_balance)->toBe('175000.00');
    expect(AuditLog::query()->where('auditable_type', FinancialTransaction::class)->where('auditable_id', $transaction->id)->where('event', 'created')->exists())->toBeTrue();
});

it('requires an account, category, amount, date and description for a manual expense', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();

    $this->actingAs($financeUser)
        ->post(route('accounting.expenses.store'), [
            'cash_bank_account_id' => $account->id,
            'category_id' => '',
            'trx_date' => '2026-11-11',
            'amount' => '10.00',
            'description' => '',
        ])
        ->assertSessionHasErrors(['category_id', 'description']);

    expect(FinancialTransaction::query()->exists())->toBeFalse();
    expect($account->fresh()->current_balance)->toBe('0.00');
});

it('rejects an expense that makes the balance negative unless the actor has the special permission', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create([
        'opening_balance' => '100.00',
        'current_balance' => '100.00',
    ]);
    $category = TransactionCategory::query()->where('source_code', 'ELECTRICITY')->firstOrFail();
    $payload = [
        'cash_bank_account_id' => $account->id,
        'category_id' => $category->id,
        'trx_date' => '2026-11-11',
        'amount' => '150.00',
        'description' => 'Tagihan listrik',
    ];

    $this->actingAs($financeUser)
        ->post(route('accounting.expenses.store'), $payload)
        ->assertSessionHasErrors('amount');

    expect($account->fresh()->current_balance)->toBe('100.00');
    expect(FinancialTransaction::query()->exists())->toBeFalse();

    $financeUser->givePermissionTo('accounting.cash.allow_negative');
    $this->post(route('accounting.expenses.store'), $payload)->assertRedirect(route('accounting.index'));

    expect($account->fresh()->current_balance)->toBe('-50.00');
    expect(FinancialTransaction::query()->count())->toBe(1);
});

it('records corrections as one opposite transaction and forbids editing or deleting values', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create([
        'opening_balance' => '100.00',
        'current_balance' => '100.00',
    ]);
    $category = TransactionCategory::query()->where('source_code', 'ELECTRICITY')->firstOrFail();
    $posting = app(FinancialPostingService::class);
    $original = $posting->postManualExpense([
        'cash_bank_account_id' => $account->id,
        'category_id' => $category->id,
        'trx_date' => '2026-11-11',
        'amount' => '30.00',
        'description' => 'Pembelian alat kebersihan',
    ]);

    $this->actingAs($financeUser)
        ->patch(route('accounting.transactions.reverse', $original), [
            'description' => 'Transaksi tercatat dua kali',
        ])
        ->assertRedirect(route('accounting.index'));

    $original->refresh();
    $reversal = FinancialTransaction::query()->where('reverses_id', $original->id)->firstOrFail();

    expect($original->status)->toBe('reversed');
    expect($reversal->direction)->toBe('in');
    expect($reversal->amount)->toBe('30.00');
    expect($reversal->description)->toContain('Transaksi tercatat dua kali');
    expect($account->fresh()->current_balance)->toBe('100.00');
    expect(fn () => $original->update(['amount' => '99.00']))->toThrow(LogicException::class);
    expect(fn () => $original->delete())->toThrow(LogicException::class);

    $this->patch(route('accounting.transactions.reverse', $original), [
        'description' => 'Koreksi kedua',
    ])->assertSessionHasErrors('transaction');

    expect(FinancialTransaction::query()->count())->toBe(2);
});

it('keeps system categories protected from user-controlled source codes and rejects mismatched expense categories', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();

    $this->actingAs($financeUser)
        ->post(route('accounting.categories.store'), [
            'name' => 'Biaya bahan kemasan',
            'direction' => 'out',
            'source_code' => 'SALE_RX',
            'is_system' => '1',
        ])
        ->assertRedirect(route('accounting.index'));

    $customCategory = TransactionCategory::query()->where('name', 'Biaya bahan kemasan')->firstOrFail();
    expect($customCategory->is_system)->toBeFalse();
    expect($customCategory->source_code)->toBeNull();
    expect(AuditLog::query()->where('auditable_type', TransactionCategory::class)->where('auditable_id', $customCategory->id)->where('event', 'created')->exists())->toBeTrue();

    $incomeCategory = TransactionCategory::query()->where('source_code', 'SALE_RX')->firstOrFail();
    $this->post(route('accounting.expenses.store'), [
        'cash_bank_account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'trx_date' => '2026-11-11',
        'amount' => '10.00',
        'description' => 'Kategori arah tidak sesuai',
    ])->assertSessionHasErrors('category_id');

    expect(FinancialTransaction::query()->exists())->toBeFalse();
});

it('requires an authenticated user to access account balances', function () {
    $this->get(route('accounting.index'))->assertRedirect(route('login'));
});

it('restricts account management to finance users and exposes no transaction edit or delete route', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    $transaction = FinancialTransaction::factory()->create();

    $this->actingAs($cashier)
        ->get(route('accounting.index'))
        ->assertForbidden();
    $this->actingAs($cashier)
        ->post(route('accounting.expenses.store'), [])
        ->assertForbidden();

    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $this->actingAs($financeUser)
        ->put('/accounting/transactions/'.$transaction->id)
        ->assertNotFound();
    $this->actingAs($financeUser)
        ->delete('/accounting/transactions/'.$transaction->id)
        ->assertNotFound();
});
