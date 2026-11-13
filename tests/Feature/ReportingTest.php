<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Accounting\Models\FinancialTransaction;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Accounting\Models\TransactionCategory;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Models\StockOpnameItem;
use App\Modules\Inventory\Models\StockWriteOff;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Models\Supplier;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Models\SaleItem;
use App\Modules\Pos\Models\SaleItemBatch;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Reporting\Services\ReportService;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionCompounding;
use App\Modules\Rx\Models\PrescriptionDispense;
use App\Modules\Rx\Models\PrescriptionScreening;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('renders all twelve reports for an authorized pharmacist', function () {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    $this->actingAs($pharmacist);

    $this->get(route('reports.index'))->assertOk()->assertSee('REQ-RPT-12');

    foreach (app(ReportService::class)->definitions() as $key => $definition) {
        $this->get(route('reports.show', ['report' => $key]))
            ->assertOk()
            ->assertSee($definition['title']);
    }
});

it('shows only permitted reports and denies access to other report categories', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Penjualan per jenis')
        ->assertDontSee('Pemasukan dan pengeluaran');

    $this->get(route('reports.show', 'pemasukan-pengeluaran'))->assertForbidden();
    $this->get(route('reports.show', 'penjualan-per-jenis'))->assertOk();
});

it('validates report date ranges and rejects unknown report keys', function () {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    $this->actingAs($pharmacist);

    $this->get(route('reports.show', ['report' => 'arus-kas', 'from' => '2026-11-20', 'to' => '2026-11-01']))
        ->assertRedirect()
        ->assertSessionHasErrors('to');

    $this->from(route('reports.show', ['report' => 'arus-kas']))
        ->get(route('reports.show', ['report' => 'arus-kas', 'from' => '20-11-2026']))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'from' => 'Tanggal mulai harus menggunakan format YYYY-MM-DD yang valid.',
        ]);

    $this->get(route('reports.show', 'laporan-yang-tidak-ada'))->assertNotFound();
});

it('filters financial reports by period and calculates cash account balances', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create([
        'opening_balance' => '500.00',
        'current_balance' => '750.00',
    ]);
    $incomeCategory = TransactionCategory::factory()->create(['direction' => 'in', 'name' => 'Pemasukan uji']);
    $expenseCategory = TransactionCategory::factory()->create(['direction' => 'out', 'name' => 'Biaya uji']);
    $purchaseCategory = TransactionCategory::factory()->create([
        'direction' => 'out',
        'name' => 'Pembelian uji',
        'source_code' => 'PURCHASE',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'direction' => 'in',
        'amount' => '100.00',
        'trx_date' => '2026-10-31',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'direction' => 'in',
        'amount' => '200.00',
        'trx_date' => '2026-11-10',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'direction' => 'out',
        'amount' => '50.00',
        'trx_date' => '2026-11-12',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'direction' => 'out',
        'amount' => '300.00',
        'trx_date' => '2026-11-15',
        'status' => 'reversed',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $purchaseCategory->id,
        'direction' => 'out',
        'amount' => '400.00',
        'trx_no' => 'FT-REPORT-PURCHASE',
        'trx_date' => '2026-11-20',
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $incomeCategory->id,
        'direction' => 'in',
        'amount' => '999.00',
        'trx_date' => '2026-12-01',
    ]);

    $this->actingAs($financeUser)
        ->get(route('reports.show', ['report' => 'arus-kas', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Rp 600,00')
        ->assertSee('Rp 200,00')
        ->assertSee('Rp 450,00')
        ->assertSee('Rp 350,00');

    $this->get(route('reports.show', ['report' => 'pemasukan-pengeluaran', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Pemasukan uji')
        ->assertSee('Biaya uji')
        ->assertSee('Pembelian uji')
        ->assertDontSee('999,00');

    $this->get(route('reports.show', ['report' => 'biaya-operasional', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Biaya uji')
        ->assertDontSee('Pembelian uji')
        ->assertDontSee('FT-REPORT-PURCHASE');

    $this->get(route('reports.show', ['report' => 'transaksi-kas-bank', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('FT-REPORT-PURCHASE')
        ->assertSee('Rp 200,00')
        ->assertSee('Rp 450,00');
});

it('paginates report rows and retains the selected period across pages', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $account = CashBankAccount::factory()->create();

    foreach (range(1, 26) as $number) {
        $category = TransactionCategory::factory()->create([
            'direction' => 'in',
            'name' => 'Kategori laporan '.sprintf('%02d', $number),
        ]);
        FinancialTransaction::factory()->create([
            'cash_bank_account_id' => $account->id,
            'category_id' => $category->id,
            'direction' => 'in',
            'amount' => '10.00',
            'trx_date' => '2026-11-15',
        ]);
    }

    $this->actingAs($financeUser)
        ->get(route('reports.show', ['report' => 'pemasukan-pengeluaran', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Kategori laporan 25')
        ->assertDontSee('Kategori laporan 26');

    $this->get(route('reports.show', ['report' => 'pemasukan-pengeluaran', 'from' => '2026-11-01', 'to' => '2026-11-30', 'page' => 2]))
        ->assertOk()
        ->assertSee('Kategori laporan 26')
        ->assertSee('Periode 01-11-2026 sampai 30-11-2026');
});

it('filters supplier payables by due date and calculates outstanding balances', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $supplier = Supplier::factory()->create(['name' => 'Supplier Utang Uji']);
    Payable::factory()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'INV-PAYABLE-REPORT',
        'total_amount' => '1000.00',
        'paid_amount' => '200.00',
        'due_date' => '2026-11-15',
        'status' => 'sebagian',
    ]);
    Payable::factory()->create([
        'invoice_no' => 'INV-PAYABLE-OUTSIDE',
        'due_date' => '2026-12-01',
    ]);

    $this->actingAs($financeUser)
        ->get(route('reports.show', ['report' => 'utang-supplier', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Supplier Utang Uji')
        ->assertSee('INV-PAYABLE-REPORT')
        ->assertSee('Rp 800,00')
        ->assertDontSee('INV-PAYABLE-OUTSIDE');
});

it('calculates profit and loss from net sales, batch cost, operating expenses, returns, and write-offs', function () {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    $account = CashBankAccount::factory()->create();
    $expenseCategory = TransactionCategory::factory()->create([
        'direction' => 'out',
        'name' => 'Listrik uji',
        'source_code' => 'ELECTRICITY',
    ]);
    $sale = Sale::factory()->create([
        'sale_date' => '2026-11-10 10:00:00',
        'grand_total' => '100.00',
        'tax_amount' => '0.00',
    ]);
    $batch = ItemBatch::factory()->create(['unit_cost_base' => '10.0000']);
    $saleItem = SaleItem::factory()->for($sale)->create(['qty_base' => '2.000']);
    SaleItemBatch::factory()->for($saleItem)->for($batch)->create(['qty_base' => '2.000']);
    $returnId = DB::table('sale_returns')->insertGetId([
        'return_no' => 'RET-REPORT-001',
        'sale_id' => $sale->id,
        'processed_by' => $pharmacist->id,
        'return_date' => '2026-11-15 10:00:00',
        'reason' => 'Uji laporan',
        'refund_amount' => '10.00',
        'restock' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('sale_return_items')->insert([
        'sale_return_id' => $returnId,
        'sale_item_id' => $saleItem->id,
        'item_batch_id' => $batch->id,
        'qty_base' => '0.500',
        'unit_price_base' => '20.00',
        'line_total' => '10.00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    FinancialTransaction::factory()->create([
        'cash_bank_account_id' => $account->id,
        'category_id' => $expenseCategory->id,
        'direction' => 'out',
        'amount' => '15.00',
        'trx_date' => '2026-11-18',
    ]);
    StockWriteOff::factory()->create([
        'item_batch_id' => $batch->id,
        'write_off_date' => '2026-11-19',
        'loss_amount' => '5.00',
        'status' => 'approved',
    ]);

    $this->actingAs($pharmacist)
        ->get(route('reports.show', ['report' => 'laba-rugi', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Rp 90,00')
        ->assertSee('Rp 15,00')
        ->assertSee('Rp 55,00');
});

it('filters batch stock by expiry date and shows the item reorder status', function () {
    $warehouseUser = User::factory()->create();
    $warehouseUser->assignRole('gudang');
    $item = Item::factory()->create([
        'code' => 'ITM-REPORT-01',
        'name' => 'Barang Laporan Stok',
        'reorder_point' => '5.000',
    ]);
    ItemBatch::factory()->for($item)->create([
        'batch_no' => 'BATCH-REPORT-01',
        'expiry_date' => '2026-11-20',
    ]);
    ItemBatch::factory()->for($item)->create([
        'batch_no' => 'BATCH-REPORT-OUTSIDE',
        'expiry_date' => '2027-01-20',
    ]);

    $this->actingAs($warehouseUser)
        ->get(route('reports.show', ['report' => 'stok-batch', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('BATCH-REPORT-01')
        ->assertSee('perlu pesan')
        ->assertDontSee('BATCH-REPORT-OUTSIDE');
});

it('combines stock opname and movement rows within the selected date range', function () {
    $warehouseUser = User::factory()->create();
    $warehouseUser->assignRole('gudang');
    $batch = ItemBatch::factory()->create();
    $opname = StockOpname::factory()->create([
        'opname_date' => '2026-11-10',
        'status' => 'approved',
    ]);
    StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
        'item_batch_id' => $batch->id,
        'system_qty' => '2.000',
        'physical_qty' => '1.000',
        'diff_qty' => '-1.000',
        'note' => 'Selisih uji',
    ]);
    DB::table('stock_movements')->insert([
        'item_batch_id' => $batch->id,
        'qty_change' => '-1.000',
        'qty_after' => '1.000',
        'type' => 'adjustment',
        'reason' => 'Mutasi stok uji',
        'created_at' => '2026-11-11 10:00:00',
        'updated_at' => '2026-11-11 10:00:00',
    ]);

    $this->actingAs($warehouseUser)
        ->get(route('reports.show', ['report' => 'opname-mutasi', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Stock opname')
        ->assertSee('adjustment')
        ->assertSee('Selisih uji')
        ->assertSee('Mutasi stok uji');
});

it('shows lead time and supplier matching metrics for confirmed receipts only', function () {
    $warehouseUser = User::factory()->create();
    $warehouseUser->assignRole('gudang');
    $supplier = Supplier::factory()->create(['name' => 'Supplier Laporan Uji']);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'po_no' => 'PO-REPORT-01',
        'sent_at' => '2026-11-01 09:00:00',
        'expected_date' => '2026-11-05',
    ]);
    $receipt = GoodsReceipt::factory()->for($supplier)->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_no' => 'INV-REPORT-01',
        'invoice_date' => '2026-11-07',
        'total_after_tax' => '125.00',
        'matching_status' => 'sesuai',
    ]);
    GoodsReceipt::duringConfirmation(fn () => $receipt->update(['status' => 'confirmed']));
    GoodsReceipt::factory()->for($supplier)->create([
        'invoice_no' => 'INV-REPORT-DRAFT',
        'invoice_date' => '2026-11-08',
    ]);

    $this->actingAs($warehouseUser)
        ->get(route('reports.show', ['report' => 'riwayat-pembelian', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('INV-REPORT-01')
        ->assertSee('Supplier Laporan Uji')
        ->assertSee('6,0')
        ->assertSee('0,00%')
        ->assertSee('100,00%')
        ->assertDontSee('INV-REPORT-DRAFT');
});

it('reports returns by their processing period and excludes returned output tax from net revenue', function () {
    $pharmacist = User::factory()->create();
    $pharmacist->assignRole('apoteker');
    Sale::factory()->create([
        'sale_type' => 'obat_bebas',
        'sale_date' => '2026-11-10 10:00:00',
        'grand_total' => '110.00',
        'tax_amount' => '10.00',
    ]);
    $olderSale = Sale::factory()->create([
        'sale_type' => 'produk_lain',
        'sale_date' => '2026-10-20 10:00:00',
        'grand_total' => '110.00',
        'tax_amount' => '10.00',
    ]);
    $olderSaleItem = SaleItem::factory()->for($olderSale)->create([
        'qty_base' => '1.000',
        'unit_price_base' => '110.00',
        'tax_amount' => '10.00',
        'line_total' => '110.00',
    ]);
    $returnId = DB::table('sale_returns')->insertGetId([
        'return_no' => 'RET-REPORT-PERIOD',
        'sale_id' => $olderSale->id,
        'processed_by' => $pharmacist->id,
        'return_date' => '2026-11-15 10:00:00',
        'reason' => 'Retur atas transaksi periode lalu',
        'refund_amount' => '55.00',
        'restock' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('sale_return_items')->insert([
        'sale_return_id' => $returnId,
        'sale_item_id' => $olderSaleItem->id,
        'qty_base' => '0.500',
        'unit_price_base' => '110.00',
        'line_total' => '55.00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($pharmacist)
        ->get(route('reports.show', ['report' => 'penjualan-per-jenis', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Obat bebas')
        ->assertSee('Produk lainnya')
        ->assertSee('Rp 55,00')
        ->assertSee('Rp 50,00')
        ->assertSee('Rp -50,00');

    $this->get(route('reports.show', ['report' => 'laba-rugi', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Pendapatan setelah retur')
        ->assertSee('Rp 50,00');
});

it('masks patient names in finance reports and limits pharmacy activity to the logged-in staff member', function () {
    $financeUser = User::factory()->create();
    $financeUser->assignRole('keuangan');
    $patient = Patient::factory()->create(['name' => 'Nama Sangat Privat', 'code' => 'PAT-REPORT-01']);
    $sale = Sale::factory()->create([
        'patient_id' => $patient->id,
        'sale_date' => '2026-11-12 10:00:00',
    ]);
    Receivable::factory()->for($sale)->for($patient)->create();

    $this->actingAs($financeUser)
        ->get(route('reports.show', ['report' => 'piutang-pelanggan', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('PAT-REPORT-01')
        ->assertDontSee('Nama Sangat Privat');

    $staffUser = User::factory()->create();
    $staffUser->assignRole('tenaga_farmasi');
    $staff = PharmacyStaff::factory()->for($staffUser, 'user')->create(['name' => 'Tenaga Uji']);
    $otherStaff = PharmacyStaff::factory()->create(['name' => 'Tenaga Lain']);
    $doctor = Doctor::factory()->create();
    $ownPatient = Patient::factory()->create(['name' => 'Pasien Milik Staff']);
    $otherPatient = Patient::factory()->create(['name' => 'Pasien Staff Lain']);
    $ownPrescription = Prescription::factory()->for($ownPatient)->for($doctor)->create([
        'rx_no' => 'RX-REPORT-OWN',
        'rx_date' => '2026-11-12',
        'created_by' => $staffUser->id,
    ]);
    $otherPrescription = Prescription::factory()->for($otherPatient)->for($doctor)->create([
        'rx_no' => 'RX-REPORT-OTHER',
        'rx_date' => '2026-11-12',
    ]);
    PrescriptionScreening::factory()->create([
        'prescription_id' => $ownPrescription->id,
        'pharmacy_staff_id' => $staff->id,
        'performed_at' => '2026-11-12 11:00:00',
    ]);
    PrescriptionCompounding::factory()->create([
        'prescription_id' => $ownPrescription->id,
        'pharmacy_staff_id' => $staff->id,
        'compound_name' => 'Racikan Uji',
        'performed_at' => '2026-11-12 11:30:00',
    ]);
    PrescriptionDispense::factory()->create([
        'prescription_id' => $ownPrescription->id,
        'pharmacy_staff_id' => $staff->id,
        'received_by_name' => 'Penerima Uji',
        'dispensed_at' => '2026-11-12 12:00:00',
    ]);
    PrescriptionScreening::factory()->create([
        'prescription_id' => $otherPrescription->id,
        'pharmacy_staff_id' => $otherStaff->id,
        'performed_at' => '2026-11-12 12:00:00',
    ]);

    $this->actingAs($staffUser)
        ->get(route('reports.show', ['report' => 'aktivitas-farmasi', 'from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertSee('RX-REPORT-OWN')
        ->assertDontSee('RX-REPORT-OTHER')
        ->assertSee('Pasien Milik Staff')
        ->assertSee('Peracikan')
        ->assertSee('Penyerahan')
        ->assertSee('Racikan Uji')
        ->assertSee('Penerima Uji');

    expect(AuditLog::query()->where('auditable_type', Patient::class)->where('auditable_id', $ownPatient->id)->where('event', 'viewed')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', Patient::class)->where('auditable_id', $otherPatient->id)->where('event', 'viewed')->exists())->toBeFalse();
});
