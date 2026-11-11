<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\ReverseFinancialTransactionRequest;
use App\Http\Requests\Accounting\StoreCashBankAccountRequest;
use App\Http\Requests\Accounting\StoreManualExpenseRequest;
use App\Http\Requests\Accounting\StoreTransactionCategoryRequest;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Accounting\Services\FinancialPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashBankController extends Controller
{
    public function index(): View
    {
        Gate::authorize('accounting.cash.view');

        return view('accounting.index', [
            'accounts' => CashBankAccount::query()->orderBy('name')->get(),
            'categories' => TransactionCategory::query()->orderBy('direction')->orderBy('is_system')->orderBy('name')->get(),
            'expenseCategories' => TransactionCategory::query()
                ->where('direction', 'out')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'activeAccounts' => CashBankAccount::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'current_balance']),
            'transactions' => FinancialTransaction::query()
                ->with([
                    'account:id,name,type',
                    'category:id,name,direction',
                    'reversal:id,reverses_id,trx_no',
                ])
                ->orderByDesc('trx_date')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function storeAccount(StoreCashBankAccountRequest $request, FinancialPostingService $posting): RedirectResponse
    {
        $posting->createAccount($request->validated());

        return redirect()->route('accounting.index')->with('success', 'Akun kas/bank berhasil dibuat.');
    }

    public function storeCategory(StoreTransactionCategoryRequest $request, FinancialPostingService $posting): RedirectResponse
    {
        $posting->createCategory($request->validated());

        return redirect()->route('accounting.index')->with('success', 'Kategori transaksi berhasil dibuat.');
    }

    public function storeExpense(StoreManualExpenseRequest $request, FinancialPostingService $posting): RedirectResponse
    {
        $posting->postManualExpense($request->validated());

        return redirect()->route('accounting.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function reverse(
        ReverseFinancialTransactionRequest $request,
        FinancialTransaction $financialTransaction,
        FinancialPostingService $posting,
    ): RedirectResponse {
        $posting->reverse($financialTransaction, $request->validated('description'));

        return redirect()->route('accounting.index')->with('success', 'Transaksi lawan berhasil dibuat.');
    }
}
