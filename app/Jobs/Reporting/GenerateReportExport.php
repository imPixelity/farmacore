<?php

namespace App\Jobs\Reporting;

use App\Modules\Reporting\Models\ReportExport;
use App\Modules\Reporting\Services\ReportExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [10, 30];

    public function __construct(public int $reportExportId) {}

    public function handle(ReportExportService $reportExportService): void
    {
        try {
            $reportExportService->generate($this->reportExportId);
        } catch (AuthorizationException|InvalidArgumentException $exception) {
            $this->fail($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        ReportExport::query()
            ->whereKey($this->reportExportId)
            ->where('status', '!=', 'completed')
            ->update([
                'status' => 'failed',
                'failure_message' => 'Ekspor gagal diproses. Silakan coba lagi.',
            ]);

        Log::error('Report export job failed.', [
            'report_export_id' => $this->reportExportId,
            'exception' => $exception,
        ]);
    }
}
