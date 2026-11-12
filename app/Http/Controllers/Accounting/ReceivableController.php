<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\PayReceivableRequest;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Services\ReceivableService;
use App\Modules\Md\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('accounting.receivable.manage');

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $query = $this->filteredQuery($status, $search);
        $summary = (clone $query)
            ->selectRaw('COALESCE(SUM(total_amount), 0) AS total_amount, COALESCE(SUM(paid_amount), 0) AS paid_amount')
            ->first();

        return view('accounting.receivables.index', [
            'receivables' => $query
                ->with([
                    'patient:id,code,name',
                    'sale:id,invoice_no,sale_date,grand_total',
                    'payments.cashBankAccount:id,name,type',
                ])
                ->orderByRaw("CASE status WHEN 'belum_dibayar' THEN 0 WHEN 'sebagian' THEN 1 ELSE 2 END")
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'patients' => Patient::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'accounts' => CashBankAccount::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'current_balance']),
            'filters' => ['status' => $status, 'search' => $search],
            'summary' => [
                'total_amount' => (string) ($summary?->total_amount ?? '0.00'),
                'paid_amount' => (string) ($summary?->paid_amount ?? '0.00'),
                'remaining_amount' => bcsub((string) ($summary?->total_amount ?? '0.00'), (string) ($summary?->paid_amount ?? '0.00'), 2),
            ],
        ]);
    }

    public function pay(PayReceivableRequest $request, Receivable $receivable, ReceivableService $service): RedirectResponse
    {
        $service->pay($receivable, $request->validated());

        $filters = array_filter([
            'status' => $request->string('redirect_status')->toString(),
            'search' => $request->string('redirect_search')->toString(),
        ], fn (string $value): bool => $value !== '');

        return redirect()->route('accounting.receivables.index', $filters)
            ->with('success', 'Pembayaran piutang berhasil dicatat dan diposting ke kas/bank.');
    }

    /** @return Builder<Receivable> */
    private function filteredQuery(string $status, string $search): Builder
    {
        return Receivable::query()
            ->when(in_array($status, ['belum_dibayar', 'sebagian', 'lunas'], true), fn (Builder $query): Builder => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('patient', function (Builder $patientQuery) use ($search): void {
                    $patientQuery->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%");
                });
            });
    }
}
