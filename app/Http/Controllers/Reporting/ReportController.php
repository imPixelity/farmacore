<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ReportExportRequest;
use App\Http\Requests\Reporting\ReportFilterRequest;
use App\Jobs\Reporting\GenerateReportExport;
use App\Models\User;
use App\Modules\Reporting\Models\ReportExport;
use App\Modules\Reporting\Services\ReportAccessAuditService;
use App\Modules\Reporting\Services\ReportService;
use App\Modules\Reporting\Services\ReportValueFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

    public function show(
        ReportFilterRequest $request,
        string $report,
        ReportService $reports,
        ReportAccessAuditService $accessAudit,
        ReportValueFormatter $formatter,
    ): View {
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
            $rows = $result['rows'] instanceof LengthAwarePaginator ? $result['rows']->items() : (array) $result['rows'];
            $accessAudit->recordPatientHistoryAccess($user, $rows, $request->ip(), $request->userAgent());
        }

        return view('reporting.show', [
            ...$result,
            'availableReports' => $reports->availableTo($user),
            'formatter' => $formatter,
            'exports' => ReportExport::query()
                ->where('user_id', $user->id)
                ->where('report_key', $report)
                ->latest()
                ->limit(10)
                ->get(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function storeExport(
        ReportExportRequest $request,
        string $report,
        ReportService $reports,
    ): RedirectResponse {
        $definition = $reports->definitions()[$report] ?? abort(404);
        Gate::authorize($definition['permission']);

        if ($report === 'aktivitas-farmasi') {
            Gate::authorize('rx.patient_history.view');
        }

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validated();
        $reportExport = ReportExport::query()->create([
            'user_id' => $user->id,
            'report_key' => $report,
            'format' => $validated['format'],
            'from_date' => $validated['from'],
            'to_date' => $validated['to'],
            'status' => 'queued',
        ]);

        try {
            GenerateReportExport::dispatch($reportExport->id)->afterCommit();
        } catch (Throwable) {
            $reportExport->update([
                'status' => 'failed',
                'failure_message' => 'Ekspor gagal dimulai. Silakan coba lagi.',
            ]);

            return back()->with('error', 'Ekspor gagal dimulai. Silakan coba lagi.');
        }

        return back()->with('success', 'Permintaan ekspor masuk antrian. Muat ulang halaman untuk memeriksa status.');
    }

    public function downloadExport(
        Request $request,
        ReportExport $reportExport,
        ReportService $reports,
    ): StreamedResponse|RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless((int) $reportExport->user_id === (int) $user->id, 404);

        $definition = $reports->definitions()[$reportExport->report_key] ?? abort(404);
        Gate::authorize($definition['permission']);

        if ($reportExport->report_key === 'aktivitas-farmasi') {
            Gate::authorize('rx.patient_history.view');
        }

        if ($reportExport->status === 'failed') {
            return back()->with('error', $reportExport->failure_message ?? 'Ekspor gagal diproses. Silakan coba lagi.');
        }

        if ($reportExport->status !== 'completed' || $reportExport->file_path === null) {
            return back()->with('status', 'Ekspor masih diproses. Muat ulang halaman beberapa saat lagi.');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($reportExport->file_path)) {
            $reportExport->update([
                'status' => 'failed',
                'failure_message' => 'File ekspor tidak tersedia. Silakan buat ekspor baru.',
            ]);

            return back()->with('error', 'File ekspor tidak tersedia. Silakan buat ekspor baru.');
        }

        return $disk->download($reportExport->file_path, $reportExport->downloadFilename());
    }
}
