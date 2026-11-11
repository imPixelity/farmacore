<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

#[Fillable(['trx_no', 'trx_date', 'cash_bank_account_id', 'category_id', 'direction', 'amount', 'description', 'ref_type', 'ref_id', 'status', 'reverses_id', 'created_by', 'updated_by'])]
#[UseFactory(FinancialTransactionFactory::class)]
class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'trx_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (FinancialTransaction $transaction): void {
            $changedFields = array_values(array_diff(array_keys($transaction->getDirty()), ['updated_by', 'updated_at']));

            if ($changedFields !== ['status']
                || $transaction->getOriginal('status') !== 'posted'
                || $transaction->status !== 'reversed'
                || ! self::query()->where('reverses_id', $transaction->getKey())->exists()) {
                throw new LogicException('Transaksi keuangan bersifat append-only; koreksi harus melalui FinancialPostingService::reverse().');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Transaksi keuangan tidak dapat dihapus.');
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class, 'cash_bank_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_id');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
