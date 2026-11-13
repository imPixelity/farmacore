<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ReportFilterRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\Patient;
use App\Modules\Reporting\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $availableReports = $reports->availableTo($user);
        abort_if($availableReports === [], 403);

        return view('reporting.index', ['reports' => $availableReports]);
    }

    public function show(ReportFilterRequest $request, string $report, ReportService $reports): View
    {
        $definition = $reports->definitions()[$report] ?? abort(404);
        Gate::authorize($definition['permission']);
        if ($report === 'aktivitas-farmasi') {
            Gate::authorize('rx.patient_history.view');
        }

        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $validated = $request->validated();
        $defaultFrom = CarbonImmutable::now()->startOfMonth()->toDateString();
        $defaultTo = CarbonImmutable::now()->endOfMonth()->toDateString();
        $from = CarbonImmutable::parse($validated['from'] ?? $defaultFrom)->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? $defaultTo)->endOfDay();
        $result = $reports->run($report, $from, $to, $user);

        if ($report === 'aktivitas-farmasi') {
            $this->auditPatientHistoryReport($request, $result['rows']);
        }

        return view('reporting.show', [
            ...$result,
            'availableReports' => $reports->availableTo($user),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    private function auditPatientHistoryReport(Request $request, LengthAwarePaginator|array $rows): void
    {
        $pageRows = $rows instanceof LengthAwarePaginator ? $rows->items() : $rows;
        $patientIds = collect($pageRows)->pluck('patient_id')->filter()->unique();

        foreach ($patientIds as $patientId) {
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'event' => 'viewed',
                'auditable_type' => Patient::class,
                'auditable_id' => (string) $patientId,
                'new_values' => ['action' => 'patient_history_report_viewed'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
