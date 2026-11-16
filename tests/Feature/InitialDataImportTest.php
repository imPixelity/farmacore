<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Modules\Core\Models\InitialDataImportRun;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('restricts templates and imports to users with the migration permission', function () {
    $this->get(route('core.data-imports.index'))->assertRedirectToRoute('login');

    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('core.data-imports.index'))
        ->assertForbidden();

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');

    $this->actingAs($apoteker)
        ->get(route('core.data-imports.index'))
        ->assertOk()
        ->assertSee('Template XLSX lengkap');

    $this->get(route('core.data-imports.template.xlsx'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->get(route('core.data-imports.template.csv', 'items'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('imports all supported workbook sheets in one confirmed operation', function () {
    $workbook = new Spreadsheet;
    $itemsSheet = $workbook->getActiveSheet();
    $itemsSheet->setTitle('Barang');
    $itemsSheet->fromArray([
        ['code', 'item_type', 'name', 'category_name', 'category_type', 'base_unit', 'order_unit', 'factor_to_base', 'barcode', 'is_taxable', 'dosage_form', 'strength_volume'],
        ['ITM-XLSX-01', 'obat', 'Barang XLSX', 'Obat', 'obat', 'tablet', 'strip', 10, null, 1, 'tablet', '250 mg'],
    ], null, 'A1');

    $supplierSheet = $workbook->createSheet();
    $supplierSheet->setTitle('Supplier');
    $supplierSheet->fromArray([
        ['name', 'address', 'phone', 'email', 'contact_person', 'payment_terms', 'payment_term_days', 'lead_time_days'],
        ['PBF XLSX', null, null, null, null, null, null, null],
    ], null, 'A1');

    $stockSheet = $workbook->createSheet();
    $stockSheet->setTitle('Stok Awal');
    $stockSheet->fromArray([
        ['item_code', 'batch_no', 'expiry_date', 'qty_base', 'unit_cost_base'],
        ['ITM-XLSX-01', 'LOT-XLSX-01', '2027-12-31', 3.25, 125.5],
    ], null, 'A1');

    $temporaryPath = tempnam(sys_get_temp_dir(), 'farmacore-import-');
    (new Xlsx($workbook))->save($temporaryPath);
    $xlsxContents = file_get_contents($temporaryPath);
    unlink($temporaryPath);
    $workbook->disconnectWorksheets();

    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $this->actingAs($apoteker)
        ->post(route('core.data-imports.preview'), [
            'file' => UploadedFile::fake()->createWithContent('migrasi.xlsx', $xlsxContents),
        ])
        ->assertOk()
        ->assertSee('Konfirmasi & impor', escape: false);

    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $batch = ItemBatch::query()->firstOrFail();
    expect($batch->qty_on_hand)->toBe('3.250');
    expect(Item::query()->where('code', 'ITM-XLSX-01')->firstOrFail()->is_taxable)->toBeTrue();
    expect(Supplier::query()->where('name', 'PBF XLSX')->exists())->toBeTrue();
    $this->assertDatabaseCount('stock_movements', 1);
});

it('imports master data and opening stock through the stock ledger and supports an initial stock opname', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $this->actingAs($apoteker);

    $itemCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-OPEN-01,obat,Paracetamol,Obat,obat,tablet,strip,10,,0,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('items.csv', $itemCsv),
        'csv_type' => 'items',
    ])->assertOk()->assertSee('Konfirmasi & impor', escape: false);
    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $supplierCsv = implode("\n", [
        'name,address,phone,email,contact_person,payment_terms,payment_term_days,lead_time_days',
        'PBF Utama,Alamat apotek,081234567890,pbf@example.test,Kontak,Tempo,30,4.5',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('suppliers.csv', $supplierCsv),
        'csv_type' => 'suppliers',
    ])->assertOk()->assertSee('Konfirmasi & impor', escape: false);
    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $stockCsv = implode("\n", [
        'item_code,batch_no,expiry_date,qty_base,unit_cost_base',
        'ITM-OPEN-01,LOT-OPEN-01,2027-12-31,12.500,250.0000',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('stock.csv', $stockCsv),
        'csv_type' => 'stock',
    ])->assertOk()->assertSee('Konfirmasi & impor', escape: false);
    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $item = Item::query()->where('code', 'ITM-OPEN-01')->firstOrFail();
    $batch = ItemBatch::query()->where('item_id', $item->id)->firstOrFail();
    $this->assertDatabaseHas('stock_movements', [
        'item_batch_id' => $batch->id,
        'type' => 'manual_in',
        'qty_change' => '12.500',
        'qty_after' => '12.500',
    ]);
    expect($item->is_taxable)->toBeFalse();
    expect($batch->fresh()->qty_on_hand)->toBe('12.500');
    expect(bcadd((string) StockMovement::query()->where('item_batch_id', $batch->id)->sum('qty_change'), '0', 3))->toBe('12.500');
    expect(Supplier::query()->where('name', 'PBF Utama')->exists())->toBeTrue();

    $this->post(route('inventory.opnames.store'), [
        'opname_date' => today()->toDateString(),
        'notes' => 'Stock opname awal sebelum go-live.',
    ])->assertRedirect();

    $opname = StockOpname::query()->firstOrFail();
    expect($opname->items()->firstOrFail()->system_qty)->toBe('12.500');
    expect(Unit::query()->where('name', 'tablet')->exists())->toBeTrue();
    expect(InitialDataImportRun::query()->count())->toBe(3);
});

it('rejects invalid rows and lets the operator cancel a valid preview without writing data', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $this->actingAs($apoteker);

    $invalidCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-BAD-01,invalid,Barang,Obat,obat,tablet,strip,10,,1,tablet,500 mg',
        'ITM-BAD-TAX,obat,Barang pajak,Obat,obat,tablet,strip,10,,yes,tablet,500 mg',
        'ITM-BAD-TAX-EMPTY,obat,Barang tanpa pilihan pajak,Obat,obat,tablet,strip,10,,,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('invalid.csv', $invalidCsv),
        'csv_type' => 'items',
    ])->assertOk()
        ->assertSee('Nilai tipe barang tidak dikenal.')
        ->assertSee('Kolom status kena pajak harus bernilai 1 atau 0.')
        ->assertSee('Kolom status kena pajak wajib diisi.');

    $validCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-CANCEL-01,obat,Barang batal,Obat,obat,tablet,strip,10,,0,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('valid.csv', $validCsv),
        'csv_type' => 'items',
    ])->assertOk()->assertSee('Konfirmasi & impor', escape: false);
    $this->get(route('core.data-imports.index'))->assertOk()->assertSee('Konfirmasi & impor', escape: false);
    $this->post(route('core.data-imports.cancel'))->assertRedirectToRoute('core.data-imports.index');

    $this->assertDatabaseCount('items', 0);
    $this->assertDatabaseCount('stock_movements', 0);
    $this->assertDatabaseCount('initial_data_import_runs', 0);
});

it('does not duplicate master data or opening stock when the same content is imported again', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $this->actingAs($apoteker);

    $itemCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-REPEAT-01,obat,Barang ulang,Obat,obat,tablet,strip,10,,1,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('items.csv', $itemCsv),
        'csv_type' => 'items',
    ])->assertOk();
    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('items.csv', $itemCsv),
        'csv_type' => 'items',
    ])->assertOk();
    $this->post(route('core.data-imports.confirm'))
        ->assertRedirectToRoute('core.data-imports.index')
        ->assertSessionHas('success', 'Berkas ini sudah pernah diimpor. Tidak ada data atau stok yang ditambahkan ulang.');

    $changedTaxCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-REPEAT-01,obat,Barang ulang,Obat,obat,tablet,strip,10,,0,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('changed-tax.csv', $changedTaxCsv),
        'csv_type' => 'items',
    ])->assertOk()->assertSee('Kode barang sudah terdaftar dengan data berbeda.');

    $stockCsv = implode("\n", [
        'item_code,batch_no,expiry_date,qty_base,unit_cost_base',
        'ITM-REPEAT-01,LOT-REPEAT-01,2027-12-31,5.000,100.0000',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('stock.csv', $stockCsv),
        'csv_type' => 'stock',
    ])->assertOk();
    $this->post(route('core.data-imports.confirm'))->assertRedirectToRoute('core.data-imports.index');

    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('stock.csv', $stockCsv),
        'csv_type' => 'stock',
    ])->assertOk();
    $this->post(route('core.data-imports.confirm'))
        ->assertRedirectToRoute('core.data-imports.index')
        ->assertSessionHas('success', 'Berkas ini sudah pernah diimpor. Tidak ada data atau stok yang ditambahkan ulang.');

    $batch = ItemBatch::query()->firstOrFail();
    expect($batch->qty_on_hand)->toBe('5.000');
    $this->assertDatabaseCount('items', 1);
    $this->assertDatabaseCount('item_batches', 1);
    $this->assertDatabaseCount('stock_movements', 1);
    $this->assertDatabaseCount('initial_data_import_runs', 2);
});

it('rejects a changed opening quantity for an existing imported batch', function () {
    $apoteker = User::factory()->create();
    $apoteker->assignRole('apoteker');
    $this->actingAs($apoteker);

    $itemCsv = implode("\n", [
        'code,item_type,name,category_name,category_type,base_unit,order_unit,factor_to_base,barcode,is_taxable,dosage_form,strength_volume',
        'ITM-CHANGE-01,obat,Barang stok,Obat,obat,tablet,strip,10,,0,tablet,500 mg',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('items.csv', $itemCsv),
        'csv_type' => 'items',
    ]);
    $this->post(route('core.data-imports.confirm'));

    $originalStock = implode("\n", [
        'item_code,batch_no,expiry_date,qty_base,unit_cost_base',
        'ITM-CHANGE-01,LOT-CHANGE-01,2027-12-31,5.000,100.0000',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('stock.csv', $originalStock),
        'csv_type' => 'stock',
    ]);
    $this->post(route('core.data-imports.confirm'));

    $changedStock = implode("\n", [
        'item_code,batch_no,expiry_date,qty_base,unit_cost_base',
        'ITM-CHANGE-01,LOT-CHANGE-01,2027-12-31,6.000,100.0000',
    ]);
    $this->post(route('core.data-imports.preview'), [
        'file' => UploadedFile::fake()->createWithContent('stock.csv', $changedStock),
        'csv_type' => 'stock',
    ])->assertOk()->assertSee('Batch sudah ada dan tidak cocok dengan stok awal yang diimpor');

    expect(ItemBatch::query()->firstOrFail()->qty_on_hand)->toBe('5.000');
    $this->assertDatabaseCount('stock_movements', 1);
    $this->assertDatabaseCount('initial_data_import_runs', 2);
});

it('seeds production defaults idempotently without overwriting configured settings', function () {
    $this->seed(DatabaseSeeder::class);
    AppSetting::query()->where('key', 'expiry_alert_days')->update(['value' => 45]);

    $this->seed(DatabaseSeeder::class);

    expect(Unit::query()->count())->toBe(8);
    $this->assertDatabaseCount('item_categories', 3);
    $this->assertDatabaseCount('cash_bank_accounts', 1);
    expect(AppSetting::query()->findOrFail('expiry_alert_days')->value)->toBe(45);
    expect(AppSetting::query()->findOrFail('payable_alert_days')->value)->toBe(7);
});
