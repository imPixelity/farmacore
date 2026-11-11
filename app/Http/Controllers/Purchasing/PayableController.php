<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\PayPayableRequest;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Services\PayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayableController extends Controller
{
    public function index(Request $request, PayableService $service): View
    {
        Gate::authorize('purchasing.payable.view');
        $data = $service->listing($request->string('status')->toString() ?: null, $request->string('due')->toString() ?: null);

        return view('purchasing.payables.index', $data + ['filters' => $request->only(['status', 'due'])]);
    }

    public function show(Payable $payable, PayableService $service): View
    {
        Gate::authorize('purchasing.payable.view');
        $payable->load(['supplier', 'goodsReceipt', 'payments.cashBankAccount', 'payments.creator']);

        return view('purchasing.payables.show', ['payable' => $payable, 'accounts' => $service->activeAccounts()]);
    }

    public function pay(PayPayableRequest $request, Payable $payable, PayableService $service): RedirectResponse
    {
        $service->pay($payable, $request->validated());

        return redirect()->route('purchasing.payables.show', $payable)->with('success', 'Pembayaran utang berhasil dicatat dan kas keluar diposting.');
    }
}
