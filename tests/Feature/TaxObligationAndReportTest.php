<?php

use App\Models\User;
use App\Modules\Accounting\Models\TaxObligation;
use App\Modules\Accounting\Services\TaxObligationService;
use App\Modules\Accounting\Services\TaxReportService;
use App\Modules\Md\Models\TaxRecord;
use App\Modules\Pos\Models\Sale;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('keuangan');
    $this->actingAs($user);
});

it('records a tax obligation and marks it paid without calculating PPh', function () {
    $obligation = app(TaxObligationService::class)->create([
        'tax_type' => 'PPh',
        'period' => '2026-11',
        'amount_due' => '250000.00',
        'due_date' => '2026-12-10',
        'notes' => 'Input manual staf keuangan',
    ]);

    expect($obligation->status)->toBe('belum');
    expect(TaxObligation::query()->count())->toBe(1);

    $paid = app(TaxObligationService::class)->markPaid($obligation, '2026-12-05');

    expect($paid->status)->toBe('dibayar');
    expect($paid->paid_at->toDateString())->toBe('2026-12-05');
});

it('rejects editing a paid tax obligation', function () {
    $obligation = TaxObligation::factory()->create(['status' => 'dibayar', 'paid_at' => '2026-12-05']);

    expect(fn () => app(TaxObligationService::class)->update($obligation, [
        'tax_type' => 'PPN',
        'period' => '2026-11',
        'amount_due' => '100.00',
        'due_date' => null,
        'notes' => null,
    ]))->toThrow(ValidationException::class);
});

it('summarizes input and output tax records and gross sales by period', function () {
    $sale = Sale::factory()->create([
        'sale_date' => '2026-11-20 10:00:00',
        'grand_total' => '1500.00',
    ]);
    TaxRecord::factory()->create([
        'source_type' => Sale::class,
        'source_id' => $sale->id,
        'direction' => 'output',
        'taxable_amount' => '1351.35',
        'tax_amount' => '148.65',
        'transaction_date' => '2026-11-20',
    ]);
    TaxRecord::factory()->create([
        'direction' => 'input',
        'taxable_amount' => '1000.00',
        'tax_amount' => '110.00',
        'transaction_date' => '2026-11-15',
    ]);
    TaxRecord::factory()->create(['transaction_date' => '2026-10-31']);

    $summary = app(TaxReportService::class)->summarize('2026-11');

    expect($summary['input_base'])->toBe('1000.00');
    expect($summary['input_tax'])->toBe('110.00');
    expect($summary['output_base'])->toBe('1351.35');
    expect($summary['output_tax'])->toBe('148.65');
    expect($summary['tax_difference'])->toBe('38.65');
    expect($summary['gross_sales'])->toBe('1500.00');
    expect($summary['records'])->toHaveCount(2);
});

it('serves the filtered tax report and obligation screen', function () {
    $response = $this->get(route('accounting.taxes.index', ['period' => '2026-11']));

    $response->assertOk()->assertSee('Laporan pendukung pajak')->assertSee('Catat kewajiban pajak');
});
