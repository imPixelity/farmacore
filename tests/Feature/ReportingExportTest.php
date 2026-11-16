<?php

use App\Jobs\Reporting\GenerateReportExport;
use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Reporting\Models\ReportExport;
use App\Modules\Reporting\Services\ReportExportService;
use App\Modules\Reporting\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('queues an authorized report export with its selected period and format', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    Queue::fake([GenerateReportExport::class]);

    $this->actingAs($financeUser)
        ->post(route('reports.exports.store', 'pemasukan-pengeluaran'), [
            'from' => '2026-11-01',
            'to' => '2026-11-30',
            'format' => 'xlsx',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Permintaan ekspor masuk antrian. Muat ulang halaman untuk memeriksa status.');

    $reportExport = ReportExport::query()->firstOrFail();

    $this->assertDatabaseHas('report_exports', [
        'id' => $reportExport->id,
        'user_id' => $financeUser->id,
        'report_key' => 'pemasukan-pengeluaran',
        'format' => 'xlsx',
        'status' => 'queued',
    ]);

    expect($reportExport->from_date->toDateString())->toBe('2026-11-01')
        ->and($reportExport->to_date->toDateString())->toBe('2026-11-30');

    Queue::assertPushed(GenerateReportExport::class, fn (GenerateReportExport $job): bool => $job->reportExportId === $reportExport->id);
});

it('rejects unauthorized or invalid export requests without creating an export', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');
    Queue::fake([GenerateReportExport::class]);

    $this->actingAs($cashier)
        ->post(route('reports.exports.store', 'pemasukan-pengeluaran'), [
            'from' => '2026-11-01',
            'to' => '2026-11-30',
            'format' => 'pdf',
        ])
        ->assertForbidden();

    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');

    $this->actingAs($financeUser)
        ->post(route('reports.exports.store', 'pemasukan-pengeluaran'), [
            'from' => '2026-11-30',
            'to' => '2026-11-01',
            'format' => 'csv',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['to', 'format']);

    $this->assertDatabaseCount('report_exports', 0);
    Queue::assertNothingPushed();
});

it('generates a PDF with a valid PDF signature and report metadata', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    Storage::fake('local');

    $reportExport = ReportExport::query()->create([
        'user_id' => $financeUser->id,
        'report_key' => 'laba-rugi',
        'format' => 'pdf',
        'from_date' => '2026-11-01',
        'to_date' => '2026-11-30',
        'status' => 'queued',
    ]);

    app(ReportExportService::class)->generate($reportExport->id);

    $reportExport->refresh();
    $disk = Storage::disk('local');

    expect($reportExport->status)->toBe('completed')
        ->and($reportExport->generated_at)->not->toBeNull()
        ->and(substr($disk->get($reportExport->file_path), 0, 5))->toBe('%PDF-');

    $disk->assertExists($reportExport->file_path);
});

it('renders the complete metadata header in the PDF export template', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    Storage::fake('local');

    $reportExport = ReportExport::query()->create([
        'user_id' => $financeUser->id,
        'report_key' => 'laba-rugi',
        'format' => 'pdf',
        'from_date' => '2026-11-01',
        'to_date' => '2026-11-30',
        'status' => 'queued',
    ]);

    $pdfDocument = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfDocument->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $pdfDocument->shouldReceive('output')->once()->andReturn('%PDF-metadata-test');

    Pdf::shouldReceive('loadView')
        ->once()
        ->withArgs(function (string $viewName, array $data) use ($financeUser): bool {
            $renderedView = view($viewName, $data)->render();

            expect($viewName)->toBe('reporting.exports.pdf')
                ->and($renderedView)->toContain((string) config('app.name'))
                ->and($renderedView)->toContain('Laba rugi')
                ->and($renderedView)->toContain('01-11-2026 sampai 30-11-2026')
                ->and($renderedView)->toContain('Waktu cetak')
                ->and($data['printedAt'])->not->toBeEmpty()
                ->and($renderedView)->toContain($financeUser->name);

            return true;
        })
        ->andReturn($pdfDocument);

    app(ReportExportService::class)->generate($reportExport->id);

    expect($reportExport->refresh()->status)->toBe('completed');
});

it('exports every report row to XLSX and writes formula-like text as a string', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();
    Storage::fake('local');

    foreach (range(1, 26) as $number) {
        $category = TransactionCategory::factory()->create([
            'direction' => 'in',
            'name' => $number === 1 ? '=SUM(1,1)' : 'Kategori ekspor '.sprintf('%02d', $number),
        ]);

        FinancialTransaction::factory()->create([
            'cash_bank_account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'in',
            'amount' => '10.00',
            'trx_date' => '2026-11-15',
        ]);
    }

    $reportExport = ReportExport::query()->create([
        'user_id' => $financeUser->id,
        'report_key' => 'pemasukan-pengeluaran',
        'format' => 'xlsx',
        'from_date' => '2026-11-01',
        'to_date' => '2026-11-30',
        'status' => 'queued',
    ]);

    app(ReportExportService::class)->generate($reportExport->id);

    $reportExport->refresh();
    $spreadsheet = IOFactory::load(Storage::disk('local')->path($reportExport->file_path));
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray(null, true, true, false);
    $headerIndex = null;

    foreach ($rows as $index => $row) {
        if (($row[0] ?? null) === 'Arah' && ($row[1] ?? null) === 'Kategori') {
            $headerIndex = $index;
            break;
        }
    }

    expect($reportExport->status)->toBe('completed')
        ->and($headerIndex)->not->toBeNull()
        ->and(count(array_slice($rows, $headerIndex + 1)))->toBe(26)
        ->and($rows[$headerIndex + 1][1])->toBe('=SUM(1,1)')
        ->and($worksheet->getCell('B'.($headerIndex + 2))->getDataType())->toBe(DataType::TYPE_STRING);

    expect($rows[0][0])->toBe('Nama apotek')
        ->and($rows[0][1])->toBe((string) config('app.name'))
        ->and($rows[1][1])->toBe('Pemasukan dan pengeluaran')
        ->and($rows[3][1])->toBe('01-11-2026 sampai 30-11-2026')
        ->and($rows[4][1])->not->toBeEmpty()
        ->and($rows[5][1])->toBe($financeUser->name)
        ->and((float) $rows[$headerIndex + 1][3])->toBe(10.0)
        ->and($worksheet->getCell('D'.($headerIndex + 2))->getDataType())->toBe(DataType::TYPE_NUMERIC);

    Storage::disk('local')->assertExists($reportExport->file_path);
    $spreadsheet->disconnectWorksheets();
});

it('exports every report definition as XLSX without pagination errors', function () {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    Storage::fake('local');

    foreach (app(ReportService::class)->definitions() as $reportKey => $definition) {
        $reportExport = ReportExport::query()->create([
            'user_id' => $pharmacist->id,
            'report_key' => $reportKey,
            'format' => 'xlsx',
            'from_date' => '2026-11-01',
            'to_date' => '2026-11-30',
            'status' => 'queued',
        ]);

        app(ReportExportService::class)->generate($reportExport->id);

        expect($reportExport->refresh()->status)->toBe('completed');
        Storage::disk('local')->assertExists('report-exports/'.$reportExport->id.'.xlsx');
    }
});

it('requires patient-history permission both when queuing and processing pharmacy activity exports', function () {
    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('report.view.service');
    Queue::fake([GenerateReportExport::class]);

    $payload = [
        'from' => '2026-11-01',
        'to' => '2026-11-30',
        'format' => 'xlsx',
    ];

    $this->actingAs($staffUser)
        ->post(route('reports.exports.store', 'aktivitas-farmasi'), $payload)
        ->assertForbidden();

    $this->assertDatabaseCount('report_exports', 0);

    $staffUser->givePermissionTo('rx.patient_history.view');

    $this->actingAs($staffUser)
        ->post(route('reports.exports.store', 'aktivitas-farmasi'), $payload)
        ->assertRedirect();

    $reportExport = ReportExport::query()->firstOrFail();
    $staffUser->revokePermissionTo('rx.patient_history.view');
    Storage::fake('local');

    $job = (new GenerateReportExport($reportExport->id))->withFakeQueueInteractions();
    $job->handle(app(ReportExportService::class));
    $job->assertFailedWith(AuthorizationException::class);
    $job->failed(new AuthorizationException);

    expect($reportExport->refresh()->status)->toBe('failed');
    Storage::disk('local')->assertMissing('report-exports/'.$reportExport->id.'.xlsx');
});

it('marks a failed export and preserves source transactions', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();
    $category = TransactionCategory::factory()->create(['direction' => 'in']);
    $transaction = FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $category->id,
        'direction' => 'in',
        'amount' => '25.00',
        'trx_date' => '2026-11-15',
    ]);
    Storage::fake('local');

    $reportExport = ReportExport::query()->create([
        'user_id' => $financeUser->id,
        'report_key' => 'pemasukan-pengeluaran',
        'format' => 'pdf',
        'from_date' => '2026-11-01',
        'to_date' => '2026-11-30',
        'status' => 'processing',
    ]);
    Pdf::shouldReceive('loadView')
        ->once()
        ->andThrow(new RuntimeException('Internal failure details'));

    expect(fn () => app(ReportExportService::class)->generate($reportExport->id))
        ->toThrow(RuntimeException::class);

    (new GenerateReportExport($reportExport->id))->failed(new RuntimeException('Internal failure details'));

    expect($reportExport->refresh()->status)->toBe('failed')
        ->and($reportExport->failure_message)->toBe('Ekspor gagal diproses. Silakan coba lagi.');

    $this->assertDatabaseHas('financial_transactions', [
        'id' => $transaction->id,
        'amount' => '25.00',
    ]);
    Storage::disk('local')->assertMissing('report-exports/'.$reportExport->id.'.pdf');

    $this->actingAs($financeUser)
        ->get(route('reports.exports.download', $reportExport))
        ->assertRedirect()
        ->assertSessionHas('error', 'Ekspor gagal diproses. Silakan coba lagi.');
});

it('allows only the export owner with current report permission to download a completed file', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $otherFinanceUser = User::factory()->create();
    $otherFinanceUser->assignRole('keuangan');
    Storage::fake('local');

    $reportExport = ReportExport::query()->create([
        'user_id' => $financeUser->id,
        'report_key' => 'pemasukan-pengeluaran',
        'format' => 'pdf',
        'from_date' => '2026-11-01',
        'to_date' => '2026-11-30',
        'status' => 'completed',
        'file_path' => 'report-exports/'.$financeUser->id.'.pdf',
        'generated_at' => now(),
    ]);
    Storage::disk('local')->put($reportExport->file_path, '%PDF-test');

    $this->actingAs($financeUser)
        ->get(route('reports.exports.download', $reportExport))
        ->assertDownload($reportExport->downloadFilename());

    $this->actingAs($otherFinanceUser)
        ->get(route('reports.exports.download', $reportExport))
        ->assertNotFound();
});
