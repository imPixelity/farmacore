<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\TaxObligationRequest;
use App\Modules\Accounting\Models\TaxObligation;
use App\Modules\Accounting\Services\TaxObligationService;
use App\Modules\Accounting\Services\TaxReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function index(Request $request, TaxReportService $reports): View
    {
        Gate::authorize('accounting.tax.manage');
        $period = $request->string('period')->toString() ?: now()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }

        return view('accounting.taxes.index', [
            'period' => $period,
            'summary' => $reports->summarize($period),
            'obligations' => TaxObligation::query()->where('period', $period)->orderBy('status')->orderBy('tax_type')->get(),
            'allObligations' => TaxObligation::query()->orderByDesc('period')->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function store(TaxObligationRequest $request, TaxObligationService $obligations): RedirectResponse
    {
        $obligations->create($request->validated());

        return redirect()->route('accounting.taxes.index', ['period' => $request->validated('period')])->with('success', 'Kewajiban pajak berhasil dicatat.');
    }

    public function update(TaxObligationRequest $request, TaxObligation $taxObligation, TaxObligationService $obligations): RedirectResponse
    {
        $obligations->update($taxObligation, $request->validated());

        return redirect()->route('accounting.taxes.index', ['period' => $request->validated('period')])->with('success', 'Kewajiban pajak berhasil diperbarui.');
    }

    public function markPaid(Request $request, TaxObligation $taxObligation, TaxObligationService $obligations): RedirectResponse
    {
        Gate::authorize('accounting.tax.manage');
        $validated = $request->validate(['paid_at' => ['required', 'date']]);
        $obligations->markPaid($taxObligation, Carbon::parse($validated['paid_at'])->toDateString());

        return back()->with('success', 'Kewajiban pajak ditandai sudah dibayar.');
    }
}
