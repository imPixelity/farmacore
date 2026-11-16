<?php

namespace App\Modules\Reporting\Services;

use App\Models\User;
use App\Modules\Reporting\Exports\ReportSpreadsheetExport;
use App\Modules\Reporting\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class ReportExportService
{
    public function __construct(
        private ReportService $reports,
        private ReportAccessAuditService $accessAudit,
        private ReportValueFormatter $formatter,
    ) {}

    public function generate(int $reportExportId): void
    {
        $reportExport = ReportExport::query()->with('user')->findOrFail($reportExportId);
        $reportExport->update([
            'status' => 'processing',
            'file_path' => null,
            'generated_at' => null,
            'failure_message' => null,
        ]);

        $filePath = 'report-exports/'.$reportExport->getKey().'.'.$reportExport->format;

        try {
            $user = $reportExport->user;

            if (! $user instanceof User) {
                throw new AuthorizationException('Pengguna ekspor tidak tersedia.');
            }

            $definition = $this->reports->definitions()[$reportExport->report_key]
                ?? throw new InvalidArgumentException('Jenis laporan tidak dikenal.');

            $this->authorizeExport($user, $reportExport->report_key, $definition['permission']);

            $from = CarbonImmutable::parse($reportExport->from_date)->startOfDay();
            $to = CarbonImmutable::parse($reportExport->to_date)->endOfDay();
            $result = $this->reports->run($reportExport->report_key, $from, $to, $user, true);
            $rows = collect($result['rows']);

            if ($reportExport->report_key === 'aktivitas-farmasi') {
                $this->accessAudit->recordPatientHistoryAccess($user, $rows->all(), action: 'patient_history_report_exported');
            }

            $generatedAt = CarbonImmutable::now();
            $period = $from->format('d-m-Y').' sampai '.$to->format('d-m-Y');
            $metadata = [
                'applicationName' => (string) config('app.name'),
                'definition' => $result['definition'],
                'from' => $from,
                'to' => $to,
                'period' => $period,
                'printedAt' => $generatedAt->format('d-m-Y H:i:s'),
                'createdBy' => $user->name,
                'summary' => $result['summary'],
                'rows' => $rows,
                'formatter' => $this->formatter,
            ];

            if ($reportExport->format === 'pdf') {
                $contents = Pdf::loadView('reporting.exports.pdf', $metadata)
                    ->setPaper('a4', 'landscape')
                    ->output();

                $stored = Storage::disk('local')->put($filePath, $contents);
            } elseif ($reportExport->format === 'xlsx') {
                $spreadsheet = new ReportSpreadsheetExport(
                    $result['definition'],
                    $result['summary'],
                    $rows,
                    $metadata['applicationName'],
                    $period,
                    $metadata['printedAt'],
                    $user->name,
                    $this->formatter,
                );

                $stored = Excel::store($spreadsheet, $filePath, 'local', \Maatwebsite\Excel\Excel::XLSX);
            } else {
                throw new InvalidArgumentException('Format ekspor tidak dikenal.');
            }

            if (! $stored) {
                throw new RuntimeException('File laporan tidak dapat disimpan.');
            }

            $reportExport->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'generated_at' => $generatedAt,
                'failure_message' => null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($filePath);

            throw $exception;
        }
    }

    private function authorizeExport(User $user, string $reportKey, string $permission): void
    {
        if (! $user->is_active) {
            throw new AuthorizationException;
        }

        Gate::forUser($user)->authorize($permission);

        if ($reportKey === 'aktivitas-farmasi') {
            Gate::forUser($user)->authorize('rx.patient_history.view');
        }
    }
}
