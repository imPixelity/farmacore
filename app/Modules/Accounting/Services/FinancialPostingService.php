<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\TransactionCategory;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialPostingService
{
    /** @param array{name: string, type: string, account_no?: string|null, opening_balance: string|int|float} $data */
    public function createAccount(array $data): CashBankAccount
    {
        $openingBalance = $this->normalizeAmount((string) $data['opening_balance'], allowZero: true);

        return DB::transaction(function () use ($data, $openingBalance): CashBankAccount {
            $account = new CashBankAccount;
            $account->fill([
                'name' => trim($data['name']),
                'type' => $data['type'],
                'account_no' => $data['account_no'] ?? null,
                'opening_balance' => $openingBalance,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $account->forceFill(['current_balance' => $openingBalance])->save();

            return $account;
        });
    }

    /** @param array{name: string, direction: string} $data */
    public function createCategory(array $data): TransactionCategory
    {
        return DB::transaction(function () use ($data): TransactionCategory {
            $category = new TransactionCategory;
            $category->fill([
                'name' => trim($data['name']),
                'direction' => $data['direction'],
                'source_code' => null,
                'is_system' => false,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $category->save();

            return $category;
        });
    }

    /** @param array{cash_bank_account_id: int|string, category_id: int|string, amount: string|int|float, description: string, trx_date: string|DateTimeInterface, direction: string, ref_type?: string|null, ref_id?: int|string|null} $data */
    public function post(array $data): FinancialTransaction
    {
        if (! in_array($data['direction'], ['in', 'out'], true)) {
            throw ValidationException::withMessages(['direction' => 'Arah transaksi tidak valid.']);
        }

        $amount = $this->normalizeAmount((string) $data['amount']);

        return DB::transaction(function () use ($data, $amount): FinancialTransaction {
            $account = CashBankAccount::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['cash_bank_account_id']);
            $category = TransactionCategory::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['category_id']);

            if (! $account->is_active) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas/bank sudah nonaktif.']);
            }

            if (! $category->is_active || $category->direction !== $data['direction']) {
                throw ValidationException::withMessages(['category_id' => 'Kategori aktif harus sesuai dengan arah transaksi.']);
            }

            $newBalance = $this->applyBalanceChange(
                (string) $account->current_balance,
                $amount,
                $data['direction'],
            );
            $this->assertNegativeBalanceAllowed($newBalance);

            $account->forceFill([
                'current_balance' => $newBalance,
                'updated_by' => Auth::id(),
            ])->save();

            $transaction = new FinancialTransaction;
            $transaction->fill([
                'trx_no' => 'FT-'.Str::ulid(),
                'trx_date' => $this->dateString($data['trx_date']),
                'cash_bank_account_id' => $account->id,
                'category_id' => $category->id,
                'direction' => $data['direction'],
                'amount' => $amount,
                'description' => trim($data['description']),
                'ref_type' => $data['ref_type'] ?? null,
                'ref_id' => $data['ref_id'] ?? null,
                'status' => 'posted',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $transaction->save();

            return $transaction;
        }, attempts: 5);
    }

    /** @param array{cash_bank_account_id: int|string, category_id: int|string, amount: string|int|float, description: string, trx_date: string|DateTimeInterface} $data */
    public function postManualExpense(array $data): FinancialTransaction
    {
        return $this->post([...$data, 'direction' => 'out']);
    }

    public function reverse(FinancialTransaction $financialTransaction, string $description): FinancialTransaction
    {
        return DB::transaction(function () use ($financialTransaction, $description): FinancialTransaction {
            $original = FinancialTransaction::query()
                ->lockForUpdate()
                ->findOrFail($financialTransaction->id);

            if ($original->status !== 'posted' || $original->reverses_id !== null) {
                throw ValidationException::withMessages(['transaction' => 'Transaksi ini sudah dibalik atau tidak dapat dibalik.']);
            }

            if (FinancialTransaction::query()->where('reverses_id', $original->id)->exists()) {
                throw ValidationException::withMessages(['transaction' => 'Transaksi ini sudah memiliki transaksi lawan.']);
            }

            $account = CashBankAccount::query()
                ->lockForUpdate()
                ->findOrFail($original->cash_bank_account_id);
            $direction = $original->direction === 'in' ? 'out' : 'in';
            $newBalance = $this->applyBalanceChange(
                (string) $account->current_balance,
                (string) $original->amount,
                $direction,
            );
            $this->assertNegativeBalanceAllowed($newBalance);

            $reversal = new FinancialTransaction;
            $reversal->fill([
                'trx_no' => 'FT-'.Str::ulid(),
                'trx_date' => today()->toDateString(),
                'cash_bank_account_id' => $account->id,
                'category_id' => $original->category_id,
                'direction' => $direction,
                'amount' => $original->amount,
                'description' => 'Reversal '.$original->trx_no.': '.trim($description),
                'ref_type' => FinancialTransaction::class,
                'ref_id' => $original->id,
                'status' => 'posted',
                'reverses_id' => $original->id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $reversal->save();

            $account->forceFill([
                'current_balance' => $newBalance,
                'updated_by' => Auth::id(),
            ])->save();

            $original->forceFill([
                'status' => 'reversed',
                'updated_by' => Auth::id(),
            ])->save();

            return $reversal;
        }, attempts: 5);
    }

    private function normalizeAmount(string $amount, bool $allowZero = false): string
    {
        if (! preg_match('/^\\d+(?:\\.\\d{1,2})?$/', $amount)
            || bccomp($amount, '0', 2) < 0
            || bccomp($amount, '9999999999999.99', 2) > 0
            || (! $allowZero && bccomp($amount, '0', 2) <= 0)) {
            throw ValidationException::withMessages(['amount' => 'Jumlah harus bernilai positif dengan maksimal dua angka desimal.']);
        }

        return bcadd($amount, '0', 2);
    }

    private function applyBalanceChange(string $currentBalance, string $amount, string $direction): string
    {
        $balance = $direction === 'in'
            ? bcadd($currentBalance, $amount, 2)
            : bcsub($currentBalance, $amount, 2);

        if (bccomp($balance, '9999999999999.99', 2) > 0 || bccomp($balance, '-9999999999999.99', 2) < 0) {
            throw ValidationException::withMessages(['amount' => 'Saldo setelah transaksi berada di luar batas yang dapat dicatat.']);
        }

        return $balance;
    }

    private function assertNegativeBalanceAllowed(string $balance): void
    {
        if (bccomp($balance, '0', 2) < 0 && ! Auth::user()?->can('accounting.cash.allow_negative')) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo akun tidak mencukupi untuk pengeluaran ini.',
            ]);
        }
    }

    private function dateString(string|DateTimeInterface $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;
    }
}
