<?php

namespace App\Modules\Purchasing\Models;

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Core\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payable_id', 'cash_bank_account_id', 'payment_date', 'amount', 'reference_no', 'notes', 'created_by', 'updated_by',
])]

class PayablePayment extends Model
{
    /** @use HasFactory<PayablePaymentFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2'];
    }

    /** @return BelongsTo<Payable, self> */
    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    /** @return BelongsTo<CashBankAccount, self> */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(CashBankAccount::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, self> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
