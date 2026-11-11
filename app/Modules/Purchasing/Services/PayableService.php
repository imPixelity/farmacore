<?php

namespace App\Modules\Purchasing\Services;

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use App\Modules\Core\Services\NotificationService;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PayablePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableService
{
    public function __construct(
        private FinancialPostingService $posting,
        private NotificationService $notifications,
    ) {}

    /** @return array{payables: Collection<int, Payable>, supplierTotals: Collection<int, object>, totalOutstanding: string} */
    public function listing(?string $status = null, ?string $dueFilter = null): array
    {
        $query = Payable::query()
            ->with(['supplier:id,name', 'goodsReceipt:id,receipt_no'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($dueFilter === 'overdue', fn (Builder $query) => $query->whereDate('due_date', '<', today()))
            ->when($dueFilter === 'soon', fn (Builder $query) => $query->whereBetween('due_date', [today(), today()->addDays(7)]))
            ->where('status', '!=', 'lunas')
            ->orderBy('due_date')
            ->orderBy('id');

        $payables = $query->get();
        $supplierTotals = $payables->groupBy('supplier_id')->map(function (Collection $items): object {
            /** @var Payable $payable */
            $payable = $items->first();

            return (object) [
                'supplier' => $payable->supplier,
                'total' => $items->sum(fn (Payable $payable): float => (float) $payable->total_amount),
                'paid' => $items->sum(fn (Payable $payable): float => (float) $payable->paid_amount),
                'remaining' => $items->sum(fn (Payable $payable): float => (float) $payable->remainingAmount()),
            ];
        })->values();

        return [
            'payables' => $payables,
            'supplierTotals' => $supplierTotals,
            'totalOutstanding' => number_format($payables->sum(fn (Payable $payable): float => (float) $payable->remainingAmount()), 2, '.', ''),
        ];
    }

    /** @return Collection<int, CashBankAccount> */
    public function activeAccounts(): Collection
    {
        return CashBankAccount::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @param array{cash_bank_account_id: int|string, payment_date: string, amount: string|int|float, reference_no?: string|null, notes?: string|null} $data */
    public function pay(Payable $payable, array $data): PayablePayment
    {
        $amount = $this->normalizeAmount((string) $data['amount']);

        return DB::transaction(function () use ($payable, $data, $amount): PayablePayment {
            /** @var Payable $lockedPayable */
            $lockedPayable = Payable::query()->lockForUpdate()->findOrFail($payable->id);
            $remaining = $lockedPayable->remainingAmount();

            if (bccomp($amount, $remaining, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran melebihi sisa utang supplier.']);
            }

            $account = CashBankAccount::query()->findOrFail((int) $data['cash_bank_account_id']);
            if (! $account->is_active) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Akun kas/bank tidak ditemukan atau sudah nonaktif.']);
            }

            $category = TransactionCategory::query()
                ->where('source_code', 'PAYABLE_PAYMENT')
                ->where('direction', 'out')
                ->where('is_active', true)
                ->first();

            if ($category === null) {
                throw ValidationException::withMessages(['amount' => 'Kategori pembayaran utang aktif belum tersedia.']);
            }

            $payment = new PayablePayment;
            $payment->fill([
                'payable_id' => $lockedPayable->id,
                'cash_bank_account_id' => $account->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'reference_no' => filled($data['reference_no'] ?? null) ? trim((string) $data['reference_no']) : null,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $payment->save();

            $paidAmount = bcadd((string) $lockedPayable->paid_amount, $amount, 2);
            $lockedPayable->forceFill([
                'paid_amount' => $paidAmount,
                'status' => bccomp($paidAmount, (string) $lockedPayable->total_amount, 2) === 0 ? 'lunas' : 'sebagian',
                'updated_by' => Auth::id(),
            ])->save();

            $this->posting->post([
                'cash_bank_account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => $amount,
                'description' => 'Pembayaran utang supplier '.$lockedPayable->invoice_no,
                'trx_date' => $data['payment_date'],
                'direction' => 'out',
                'ref_type' => PayablePayment::class,
                'ref_id' => $payment->id,
            ]);

            return $payment->fresh(['payable', 'cashBankAccount']);
        }, attempts: 5);
    }

    public function notifyDueSoon(): void
    {
        $recipients = User::query()->where('is_active', true)->permission('purchasing.payable.view')->orderBy('id')->get();

        Payable::query()
            ->with('supplier:id,name')
            ->where('status', '!=', 'lunas')
            ->whereBetween('due_date', [today(), today()->addDays(7)])
            ->orderBy('id')
            ->chunkById(100, function (Collection $payables) use ($recipients): void {
                foreach ($payables as $payable) {
                    foreach ($recipients as $recipient) {
                        $this->notifications->send($recipient, 'PayableDueSoon', 'payable-'.$payable->id, [
                            'payable_id' => $payable->id,
                            'supplier_name' => $payable->supplier->name,
                            'invoice_no' => $payable->invoice_no,
                            'due_date' => $payable->due_date->toDateString(),
                            'remaining_amount' => $payable->remainingAmount(),
                            'message' => 'Utang supplier '.$payable->invoice_no.' jatuh tempo pada '.$payable->due_date->format('d-m-Y').'.',
                            'url' => route('purchasing.payables.index'),
                        ]);
                    }
                }
            });
    }

    private function normalizeAmount(string $amount): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)
            || bccomp($amount, '0', 2) <= 0
            || bccomp($amount, '9999999999999.99', 2) > 0) {
            throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus positif dengan maksimal dua angka desimal.']);
        }

        return bcadd($amount, '0', 2);
    }
}
