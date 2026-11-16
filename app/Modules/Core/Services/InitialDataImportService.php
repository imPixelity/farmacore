<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\InitialDataImportRun;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class InitialDataImportService
{
    public function __construct(
        private InitialDataImportReader $reader,
        private StockService $stock,
    ) {}

    /**
     * @return array{valid: bool, errors: array<int, string>, counts: array<string, int>, content_hash: ?string}
     */
    public function preview(string $path, ?string $csvType): array
    {
        $plan = $this->buildPlan($path, $csvType);

        return [
            'valid' => $plan['errors'] === [],
            'errors' => $plan['errors'],
            'counts' => array_map('count', $plan['rows']),
            'content_hash' => $plan['errors'] === [] ? $plan['content_hash'] : null,
        ];
    }

    /**
     * @return array{duplicate: bool, summary: array<string, int>}
     */
    public function import(string $path, ?string $csvType): array
    {
        $plan = $this->buildPlan($path, $csvType);

        if ($plan['errors'] !== []) {
            throw ValidationException::withMessages(['file' => $plan['errors']]);
        }

        try {
            return DB::transaction(function () use ($plan): array {
                $existingRun = InitialDataImportRun::query()
                    ->where('content_hash', $plan['content_hash'])
                    ->lockForUpdate()
                    ->first();

                if ($existingRun !== null) {
                    return ['duplicate' => true, 'summary' => $existingRun->summary];
                }

                $run = InitialDataImportRun::query()->create([
                    'content_hash' => $plan['content_hash'],
                    'summary' => [],
                    'created_by' => Auth::id(),
                ]);

                $summary = [
                    'items_created' => 0,
                    'items_unchanged' => 0,
                    'suppliers_created' => 0,
                    'suppliers_unchanged' => 0,
                    'units_created' => 0,
                    'categories_created' => 0,
                    'batches_created' => 0,
                    'opening_movements_created' => 0,
                    'opening_movements_unchanged' => 0,
                ];

                $itemsByCode = [];
                foreach ($plan['rows']['items'] as $row) {
                    $item = $this->findItemByCode($row['code']);

                    if ($item !== null) {
                        $summary['items_unchanged']++;
                        $itemsByCode[mb_strtolower($row['code'])] = $item;

                        continue;
                    }

                    $categoryType = $row['category_type'];
                    $category = $this->findCategory($row['category_name'], $categoryType);
                    if ($category === null) {
                        $category = ItemCategory::query()->create([
                            'name' => $row['category_name'],
                            'type' => $categoryType,
                            'is_active' => true,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                        $summary['categories_created']++;
                    }

                    $baseUnit = $this->findUnit($row['base_unit']);
                    if ($baseUnit === null) {
                        $baseUnit = Unit::query()->create([
                            'name' => $row['base_unit'],
                            'is_active' => true,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                        $summary['units_created']++;
                    }

                    $orderUnit = $this->findUnit($row['order_unit']);
                    if ($orderUnit === null) {
                        $orderUnit = Unit::query()->create([
                            'name' => $row['order_unit'],
                            'is_active' => true,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                        $summary['units_created']++;
                    }

                    $item = Item::query()->create([
                        'code' => $row['code'],
                        'item_type' => $row['item_type'],
                        'name' => $row['name'],
                        'dosage_form' => $row['dosage_form'],
                        'strength_volume' => $row['strength_volume'],
                        'category_id' => $category->id,
                        'base_unit_id' => $baseUnit->id,
                        'order_unit_id' => $orderUnit->id,
                        'barcode' => $row['barcode'],
                        'is_taxable' => $row['is_taxable'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    ItemUnit::query()->create([
                        'item_id' => $item->id,
                        'unit_id' => $baseUnit->id,
                        'factor_to_base' => '1.000',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    if (! $orderUnit->is($baseUnit)) {
                        ItemUnit::query()->create([
                            'item_id' => $item->id,
                            'unit_id' => $orderUnit->id,
                            'factor_to_base' => $row['factor_to_base'],
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }

                    $summary['items_created']++;
                    $itemsByCode[mb_strtolower($row['code'])] = $item;
                }

                foreach ($plan['rows']['suppliers'] as $row) {
                    $supplier = $this->findSupplier($row['name']);

                    if ($supplier !== null) {
                        $summary['suppliers_unchanged']++;

                        continue;
                    }

                    Supplier::query()->create([
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'contact_person' => $row['contact_person'],
                        'payment_terms' => $row['payment_terms'],
                        'payment_term_days' => $row['payment_term_days'],
                        'lead_time_days' => $row['lead_time_days'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    $summary['suppliers_created']++;
                }

                foreach ($plan['rows']['stock'] as $row) {
                    $item = $itemsByCode[mb_strtolower($row['item_code'])] ?? $this->findItemByCode($row['item_code']);
                    if ($item === null) {
                        throw ValidationException::withMessages(['file' => 'Barang '.$row['item_code'].' tidak ditemukan saat pencatatan stok awal.']);
                    }

                    $batch = ItemBatch::query()
                        ->where('item_id', $item->id)
                        ->whereRaw('LOWER(batch_no) = ?', [mb_strtolower($row['batch_no'])])
                        ->whereDate('expiry_date', $row['expiry_date'])
                        ->lockForUpdate()
                        ->first();

                    if ($batch !== null) {
                        $summary['opening_movements_unchanged']++;

                        continue;
                    }

                    $batch = ItemBatch::query()->create([
                        'item_id' => $item->id,
                        'batch_no' => $row['batch_no'],
                        'expiry_date' => $row['expiry_date'],
                        'unit_cost_base' => $row['unit_cost_base'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    $this->stock->increase(
                        $batch,
                        $row['qty_base'],
                        'manual_in',
                        'Saldo awal migrasi data',
                        InitialDataImportRun::class,
                        (int) $run->getKey(),
                    );

                    $summary['batches_created']++;
                    $summary['opening_movements_created']++;
                }

                $run->forceFill(['summary' => $summary])->save();

                return ['duplicate' => false, 'summary' => $summary];
            }, attempts: 5);
        } catch (QueryException $exception) {
            $existingRun = InitialDataImportRun::query()->where('content_hash', $plan['content_hash'])->first();

            if ($existingRun !== null) {
                return ['duplicate' => true, 'summary' => $existingRun->summary];
            }

            throw $exception;
        }
    }

    /**
     * @return array{rows: array{items: array<int, array<string, mixed>>, suppliers: array<int, array<string, mixed>>, stock: array<int, array<string, mixed>>}, errors: array<int, string>, content_hash: string}
     */
    private function buildPlan(string $path, ?string $csvType): array
    {
        $read = $this->reader->read($path, $csvType);
        $rows = ['items' => [], 'suppliers' => [], 'stock' => []];
        $errors = $read['errors'];
        $seen = ['items' => [], 'suppliers' => [], 'stock' => []];
        $seenBarcodes = [];

        foreach ($read['rows'] as $type => $sourceRows) {
            foreach ($sourceRows as $sourceRow) {
                $rowNumber = $sourceRow['row'];
                $data = $this->normalizeRow($sourceRow['data']);
                $rules = $this->rulesFor($type);
                $validator = Validator::make($data, $rules, $this->messages(), $this->attributes($type));

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $message) {
                        $errors[] = $this->rowLabel($type, $rowNumber).': '.$message;
                    }

                    continue;
                }

                $data = $validator->validated();
                $data = $this->normalizeValidatedRow($type, $data, $rowNumber, $errors);
                if ($data === null) {
                    continue;
                }

                if ($type === 'items' && filled($data['barcode'] ?? null)) {
                    $barcodeKey = mb_strtolower($data['barcode']);
                    if (isset($seenBarcodes[$barcodeKey])) {
                        $errors[] = $this->rowLabel($type, $rowNumber).': Barcode sudah digunakan oleh baris '.$seenBarcodes[$barcodeKey].'.';

                        continue;
                    }
                    $seenBarcodes[$barcodeKey] = $rowNumber;
                }

                $key = $this->duplicateKey($type, $data);

                if (isset($seen[$type][$key])) {
                    $errors[] = $this->rowLabel($type, $rowNumber).': Baris ini menduplikasi kunci data pada baris '.$seen[$type][$key].'.';

                    continue;
                }

                $seen[$type][$key] = $rowNumber;
                $rows[$type][] = $data;
                $this->validateExistingData($type, $data, $rowNumber, $errors);
            }
        }

        $itemCodes = array_fill_keys(array_map(
            static fn (array $row): string => mb_strtolower($row['code']),
            $rows['items'],
        ), true);

        foreach ($read['rows']['stock'] as $sourceRow) {
            $source = $this->normalizeRow($sourceRow['data']);
            $itemCode = is_scalar($source['item_code'] ?? null) ? trim((string) $source['item_code']) : '';
            if ($itemCode !== '' && ! isset($itemCodes[mb_strtolower($itemCode)]) && $this->findItemByCode($itemCode) === null) {
                $errors[] = $this->rowLabel('stock', $sourceRow['row']).': Kode barang tidak ditemukan di database maupun sheet Barang.';
            }
        }

        if ($errors !== []) {
            return ['rows' => $rows, 'errors' => array_values(array_unique($errors)), 'content_hash' => ''];
        }

        $canonicalRows = $rows;
        foreach ($canonicalRows as &$typeRows) {
            foreach ($typeRows as &$row) {
                ksort($row);
            }
            unset($row);
            usort($typeRows, static fn (array $left, array $right): int => strcmp(json_encode($left) ?: '', json_encode($right) ?: ''));
        }
        unset($typeRows);

        try {
            $contentHash = hash('sha256', json_encode($canonicalRows, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
        } catch (JsonException) {
            $contentHash = hash('sha256', serialize($canonicalRows));
        }

        return ['rows' => $rows, 'errors' => [], 'content_hash' => $contentHash];
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            } elseif (is_int($value) || is_float($value)) {
                $value = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
            }

            $row[$key] = $value === '' ? null : $value;
        }

        if (is_numeric($row['expiry_date'] ?? null)) {
            try {
                $row['expiry_date'] = ExcelDate::excelToDateTimeObject((float) $row['expiry_date'])->format('Y-m-d');
            } catch (Throwable) {
                $row['expiry_date'] = null;
            }
        }

        return $row;
    }

    /** @return array<string, array<int, string>> */
    private function rulesFor(string $type): array
    {
        return match ($type) {
            'items' => [
                'code' => ['required', 'string', 'max:30'],
                'item_type' => ['required', 'in:obat,alkes,non_obat'],
                'name' => ['required', 'string', 'max:150'],
                'category_name' => ['required', 'string', 'max:100'],
                'category_type' => ['required', 'in:obat,non_obat'],
                'base_unit' => ['required', 'string', 'max:30'],
                'order_unit' => ['required', 'string', 'max:30'],
                'factor_to_base' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999999999.999'],
                'barcode' => ['nullable', 'string', 'max:50'],
                'is_taxable' => ['required', 'boolean'],
                'dosage_form' => ['nullable', 'string', 'max:50'],
                'strength_volume' => ['nullable', 'string', 'max:50'],
            ],
            'suppliers' => [
                'name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:5000'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:100'],
                'payment_terms' => ['nullable', 'string', 'max:100'],
                'payment_term_days' => ['nullable', 'integer', 'min:0', 'max:65535'],
                'lead_time_days' => ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:9999.9'],
            ],
            'stock' => [
                'item_code' => ['required', 'string', 'max:30'],
                'batch_no' => ['required', 'string', 'max:60'],
                'expiry_date' => ['required', 'date_format:Y-m-d'],
                'qty_base' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999999.999'],
                'unit_cost_base' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999.9999'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $errors
     * @return array<string, mixed>|null
     */
    private function normalizeValidatedRow(string $type, array $data, int $rowNumber, array &$errors): ?array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value) === '' ? null : trim($value);
            }
        }

        if ($type === 'items') {
            $expectedCategoryType = $data['item_type'] === 'obat' ? 'obat' : 'non_obat';
            if ($data['category_type'] !== $expectedCategoryType) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Tipe kategori tidak sesuai dengan tipe barang.';

                return null;
            }

            $data['is_taxable'] = in_array($data['is_taxable'], [true, 1, '1'], true);
            $data['factor_to_base'] = bcadd((string) $data['factor_to_base'], '0', 3);
            if (mb_strtolower($data['base_unit']) === mb_strtolower($data['order_unit'])
                && bccomp($data['factor_to_base'], '1.000', 3) !== 0) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Faktor konversi harus 1 jika satuan dasar dan satuan pesan sama.';

                return null;
            }
        }

        if ($type === 'stock') {
            $data['qty_base'] = bcadd((string) $data['qty_base'], '0', 3);
            $data['unit_cost_base'] = $data['unit_cost_base'] === null
                ? null
                : bcadd((string) $data['unit_cost_base'], '0', 4);
        }

        return $data;
    }

    /** @param array<string, mixed> $row
     * @param  array<int, string>  $errors
     */
    private function validateExistingData(string $type, array $row, int $rowNumber, array &$errors): void
    {
        if ($type === 'items') {
            $existingItem = $this->findItemByCode($row['code']);

            if ($existingItem !== null && ! $this->itemMatches($existingItem, $row)) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Kode barang sudah terdaftar dengan data berbeda.';
            }

            if (filled($row['barcode'] ?? null) && Item::query()
                ->whereRaw('LOWER(barcode) = ?', [mb_strtolower($row['barcode'])])
                ->whereRaw('LOWER(code) <> ?', [mb_strtolower($row['code'])])
                ->exists()) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Barcode sudah digunakan oleh barang lain.';
            }

            $category = $this->findCategory($row['category_name'], $row['category_type']);
            if ($category !== null && ! $category->is_active) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Kategori barang yang dipilih sedang nonaktif.';
            }

            foreach ([$row['base_unit'], $row['order_unit']] as $unitName) {
                $unit = $this->findUnit($unitName);
                if ($unit !== null && ! $unit->is_active) {
                    $errors[] = $this->rowLabel($type, $rowNumber).': Satuan '.$unitName.' sedang nonaktif.';
                }
            }
        }

        if ($type === 'suppliers') {
            $supplier = $this->findSupplier($row['name']);
            if ($supplier !== null && ! $this->supplierMatches($supplier, $row)) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Nama supplier sudah terdaftar dengan data berbeda.';
            }
        }

        if ($type === 'stock') {
            $item = $this->findItemByCode($row['item_code']);
            if ($item === null) {
                return;
            }

            $batch = ItemBatch::query()
                ->where('item_id', $item->id)
                ->whereRaw('LOWER(batch_no) = ?', [mb_strtolower($row['batch_no'])])
                ->whereDate('expiry_date', $row['expiry_date'])
                ->first();

            if ($batch === null) {
                return;
            }

            $openingMovement = StockMovement::query()
                ->where('item_batch_id', $batch->id)
                ->where('type', 'manual_in')
                ->where('reason', 'Saldo awal migrasi data')
                ->where('ref_type', InitialDataImportRun::class)
                ->where('qty_change', $row['qty_base'])
                ->exists();

            $sameUnitCost = $batch->unit_cost_base === null
                ? $row['unit_cost_base'] === null
                : $row['unit_cost_base'] !== null && bccomp((string) $batch->unit_cost_base, (string) $row['unit_cost_base'], 4) === 0;
            if (! $openingMovement || ! $sameUnitCost) {
                $errors[] = $this->rowLabel($type, $rowNumber).': Batch sudah ada dan tidak cocok dengan stok awal yang diimpor; saldo tidak akan ditimpa.';
            }
        }
    }

    /** @param array<string, mixed> $row */
    private function itemMatches(Item $item, array $row): bool
    {
        $item->loadMissing(['category', 'baseUnit', 'orderUnit', 'itemUnits']);
        $orderConversion = $item->itemUnits->firstWhere('unit_id', $item->order_unit_id);
        $baseConversion = $item->itemUnits->firstWhere('unit_id', $item->base_unit_id);

        return $item->item_type === $row['item_type']
            && $item->name === $row['name']
            && $item->category?->name === $row['category_name']
            && $item->category?->type === $row['category_type']
            && mb_strtolower((string) $item->baseUnit?->name) === mb_strtolower($row['base_unit'])
            && mb_strtolower((string) $item->orderUnit?->name) === mb_strtolower($row['order_unit'])
            && (string) $item->barcode === (string) ($row['barcode'] ?? '')
            && $item->is_taxable === $row['is_taxable']
            && (string) $item->dosage_form === (string) ($row['dosage_form'] ?? '')
            && (string) $item->strength_volume === (string) ($row['strength_volume'] ?? '')
            && $orderConversion !== null
            && $baseConversion !== null
            && bccomp((string) $baseConversion->factor_to_base, '1.000', 3) === 0
            && bccomp((string) $orderConversion->factor_to_base, (string) $row['factor_to_base'], 3) === 0;
    }

    /** @param array<string, mixed> $row */
    private function supplierMatches(Supplier $supplier, array $row): bool
    {
        foreach (['address', 'phone', 'email', 'contact_person', 'payment_terms'] as $attribute) {
            if ((string) ($supplier->{$attribute} ?? '') !== (string) ($row[$attribute] ?? '')) {
                return false;
            }
        }

        if ((int) ($supplier->payment_term_days ?? 0) !== (int) ($row['payment_term_days'] ?? 0)) {
            return false;
        }

        $leadTime = $supplier->lead_time_days;
        $requestedLeadTime = $row['lead_time_days'] ?? null;
        if ($leadTime === null ? $requestedLeadTime !== null : $requestedLeadTime === null || bccomp((string) $leadTime, (string) $requestedLeadTime, 1) !== 0) {
            return false;
        }

        return $supplier->is_active;
    }

    private function findItemByCode(string $code): ?Item
    {
        return Item::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();
    }

    private function findCategory(string $name, string $type): ?ItemCategory
    {
        return ItemCategory::query()
            ->where('type', $type)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function findUnit(string $name): ?Unit
    {
        return Unit::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    private function findSupplier(string $name): ?Supplier
    {
        return Supplier::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    /** @param array<string, mixed> $row */
    private function duplicateKey(string $type, array $row): string
    {
        return match ($type) {
            'items' => mb_strtolower((string) $row['code']),
            'suppliers' => mb_strtolower((string) $row['name']),
            'stock' => mb_strtolower((string) $row['item_code'].'|'.$row['batch_no'].'|'.$row['expiry_date']),
            default => '',
        };
    }

    /** @return array<string, string> */
    private function attributes(string $type): array
    {
        return match ($type) {
            'items' => [
                'code' => 'kode barang',
                'item_type' => 'tipe barang',
                'name' => 'nama barang',
                'category_name' => 'nama kategori',
                'category_type' => 'tipe kategori',
                'base_unit' => 'satuan dasar',
                'order_unit' => 'satuan pesan',
                'factor_to_base' => 'faktor konversi',
                'barcode' => 'barcode',
                'is_taxable' => 'status kena pajak',
                'dosage_form' => 'bentuk sediaan',
                'strength_volume' => 'kekuatan/volume',
            ],
            'suppliers' => [
                'name' => 'nama supplier',
                'address' => 'alamat',
                'phone' => 'telepon',
                'email' => 'email',
                'contact_person' => 'kontak',
                'payment_terms' => 'syarat pembayaran',
                'payment_term_days' => 'jangka pembayaran',
                'lead_time_days' => 'lead time',
            ],
            'stock' => [
                'item_code' => 'kode barang',
                'batch_no' => 'nomor batch',
                'expiry_date' => 'tanggal kedaluwarsa',
                'qty_base' => 'jumlah stok dasar',
                'unit_cost_base' => 'harga pokok satuan dasar',
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Nilai :attribute terlalu besar atau melebihi batas panjang.',
            'in' => 'Nilai :attribute tidak dikenal.',
            'boolean' => 'Kolom :attribute harus bernilai 1 atau 0.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'decimal' => 'Kolom :attribute memiliki jumlah angka desimal yang tidak sesuai.',
            'min' => 'Nilai :attribute terlalu kecil.',

            'gt' => 'Nilai :attribute harus lebih besar dari nol.',
            'email' => 'Format email supplier tidak valid.',
            'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
            'date_format' => 'Tanggal kedaluwarsa harus menggunakan format YYYY-MM-DD.',
        ];
    }

    private function rowLabel(string $type, int $row): string
    {
        $name = ['items' => 'Barang', 'suppliers' => 'Supplier', 'stock' => 'Stok awal'][$type] ?? 'Data';

        return $name.' baris '.$row;
    }
}
