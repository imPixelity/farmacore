<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Models\ReceivablePayment;
use App\Modules\Accounting\Models\TransactionCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableService
{
    public function __construct(private FinancialPostingService $financialPosting) {}

    /** @param array{amount: string|int|float, cash_bank_account_id: int|string, payment_date: string} $data */
    public function pay(Receivable $receivable, array $data): Receivable
    {
        $amount = $this->positiveMoney((string) $data['amount']);

        return DB::transaction(function () use ($receivable, $data, $amount): Receivable {
            $lockedReceivable = Receivable::query()
                ->with('patient:id,name,code')
                ->lockForUpdate()
                ->findOrFail($receivable->id);

            if ($lockedReceivable->status === 'lunas') {
                throw ValidationException::withMessages(['receivable' => 'Piutang ini sudah lunas.']);
            }

            $remaining = bcsub((string) $lockedReceivable->total_amount, (string) $lockedReceivable->paid_amount, 2);
            if (bccomp($amount, $remaining, 2) > 0) {
                throw ValidationException::withMessages([
                    'amount' => "Pembayaran melebihi sisa piutang Rp {$remaining}.",
                ]);
            }

            $account = CashBankAccount::query()
                ->lockForUpdate()
                ->find((int) $data['cash_bank_account_id']);
            if (! $account?->is_active) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas/bank aktif wajib dipilih.']);
            }

            $category = TransactionCategory::query()
                ->where('source_code', 'RECEIVABLE_PAYMENT')
                ->where('direction', 'in')
                ->where('is_active', true)
                ->first();
            if (! $category) {
                throw ValidationException::withMessages(['category' => 'Kategori pembayaran piutang belum tersedia.']);
            }

            $newPaidAmount = bcadd((string) $lockedReceivable->paid_amount, $amount, 2);
            $status = bccomp($newPaidAmount, (string) $lockedReceivable->total_amount, 2) === 0
                ? 'lunas'
                : 'sebagian';
            $payment = new ReceivablePayment;
            $payment->fill([
                'receivable_id' => $lockedReceivable->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'cash_bank_account_id' => $account->id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $payment->save();

            $lockedReceivable->forceFill([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
                'updated_by' => Auth::id(),
            ])->save();

            $this->financialPosting->post([
                'cash_bank_account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => $amount,
                'description' => "Pembayaran piutang {$lockedReceivable->patient->name}",
                'trx_date' => $data['payment_date'],
                'direction' => 'in',
                'ref_type' => ReceivablePayment::class,
                'ref_id' => $payment->id,
            ]);

            return $lockedReceivable->fresh(['patient', 'sale', 'payments.cashBankAccount']);
        }, attempts: 5);
    }

    private function positiveMoney(string $amount): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)
            || bccomp($amount, '0', 2) <= 0
            || bccomp($amount, '9999999999999.99', 2) > 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus positif dengan maksimal dua angka desimal.']);
        }

        return bcadd($amount, '0', 2);
    }
}
