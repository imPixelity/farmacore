<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Services\GoodsReceiptService;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Models\TaxRule;
use App\Modules\Md\Models\TaxSetting;
use App\Modules\Md\Models\Unit;
use App\Modules\Md\Services\PricingService;
use App\Modules\Md\Services\TaxService;
use App\Modules\Pos\Models\Sale;
use App\Modules\Pos\Services\SaleService;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Services\PrescriptionWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    private const DEMO_USERS = [
        ['username' => 'demo.apoteker', 'name' => 'Apoteker Demo', 'role' => 'apoteker'],
        ['username' => 'demo.kasir', 'name' => 'Kasir Demo', 'role' => 'kasir'],
        ['username' => 'demo.gudang', 'name' => 'Petugas Gudang Demo', 'role' => 'gudang'],
        ['username' => 'demo.farmasi', 'name' => 'Tenaga Farmasi Demo', 'role' => 'tenaga_farmasi'],
        ['username' => 'demo.keuangan', 'name' => 'Staf Keuangan Demo', 'role' => 'keuangan'],
    ];

    private const DEMO_RX_NOTES = 'DATA DEMO FARMA CORE - resep menunggu screening';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoDataSeeder hanya boleh dijalankan di lingkungan non-production.');
        }

        $password = config('demo.password');

        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException('Set FARMACORE_DEMO_PASSWORD (minimal 12 karakter) sebelum menjalankan DemoDataSeeder.');
        }

        $this->call([
            RolesAndPermissionsSeeder::class,
            CoreSettingsSeeder::class,
            TransactionCategorySeeder::class,
            InitialDataSeeder::class,
        ]);

        $users = $this->createDemoUsers($password);
        Auth::login($users['demo.apoteker']);

        try {
            $demoData = $this->createDemoMasterData($users);
            $this->seedReceipt($demoData['supplier'], $demoData['items']);
            $this->seedSales($users['demo.kasir'], $demoData);
            $this->seedPrescription($demoData);
        } finally {
            Auth::logout();
        }
    }

    /** @return array<string, User> */
    private function createDemoUsers(string $password): array
    {
        $users = [];

        foreach (self::DEMO_USERS as $definition) {
            $username = $definition['username'];
            $user = User::query()->firstOrNew(['username' => $username]);
            $user->forceFill([
                'username' => $username,
                'name' => $definition['name'],
                'email' => $username.'@farmacore.test',
                'password' => Hash::make($password),
                'is_active' => true,
                'failed_attempts' => 0,
                'locked_until' => null,
            ])->save();
            $user->syncRoles($definition['role']);
            $users[$username] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     * @return array{supplier: Supplier, patients: array{cash: Patient, credit: Patient}, doctor: Doctor, pharmacist_staff: PharmacyStaff, cash_account: CashBankAccount, items: array{paracetamol: Item, amoxicillin: Item, mask: Item}, health_service: HealthService}
     */
    private function createDemoMasterData(array $users): array
    {
        $tablet = Unit::query()->firstOrCreate(['name' => 'tablet'], ['is_active' => true]);
        $pcs = Unit::query()->firstOrCreate(['name' => 'pcs'], ['is_active' => true]);
        $medicineCategory = ItemCategory::query()->firstOrCreate(
            ['name' => 'Obat', 'type' => 'obat'],
            ['is_active' => true],
        );
        $nonDrugCategory = ItemCategory::query()->firstOrCreate(
            ['name' => 'Produk non-obat', 'type' => 'non_obat'],
            ['is_active' => true],
        );

        TaxRule::query()->firstOrCreate(['item_type' => 'obat'], ['is_taxable' => true]);
        TaxRule::query()->firstOrCreate(['item_type' => 'alkes'], ['is_taxable' => false]);
        TaxRule::query()->firstOrCreate(['item_type' => 'non_obat'], ['is_taxable' => false]);
        $demoTaxSetting = TaxSetting::query()
            ->where('name', 'PPN')
            ->whereDate('effective_from', today()->toDateString())
            ->first();

        if (! $demoTaxSetting) {
            app(TaxService::class)->addRate([
                'name' => 'PPN',
                'rate_percent' => '11.00',
                'effective_from' => today()->toDateString(),
            ]);
        }

        $supplier = Supplier::query()->firstOrCreate(
            ['name' => 'PBF Demo Sejahtera'],
            [
                'address' => 'Alamat fiktif untuk presentasi',
                'phone' => '080000000001',
                'email' => 'pbf-demo@farmacore.test',
                'contact_person' => 'Kontak Demo',
                'payment_terms' => 'Kredit 30 hari',
                'payment_term_days' => 30,
                'lead_time_days' => '2.0',
                'is_active' => true,
            ],
        );

        $patients = [
            'cash' => Patient::query()->firstOrCreate(
                ['code' => 'DEMO-PAT-001'],
                [
                    'name' => 'Alya Pasien Demo',
                    'identity_no' => '0000000000000001',
                    'phone' => '080000000011',
                    'address' => 'Alamat fiktif untuk presentasi',
                    'birth_date' => '1995-01-01',
                    'is_active' => true,
                    'created_by' => $users['demo.apoteker']->id,
                ],
            ),
            'credit' => Patient::query()->firstOrCreate(
                ['code' => 'DEMO-PAT-002'],
                [
                    'name' => 'Bima Pasien Demo',
                    'identity_no' => '0000000000000002',
                    'phone' => '080000000012',
                    'address' => 'Alamat fiktif untuk presentasi',
                    'birth_date' => '1990-01-01',
                    'is_active' => true,
                    'created_by' => $users['demo.apoteker']->id,
                ],
            ),
        ];

        $doctor = Doctor::query()->firstOrCreate(
            ['identity_no' => 'DEMO-SIP-001'],
            [
                'name' => 'Dr. Adi Pratama (Demo)',
                'phone' => '080000000021',
                'origin' => 'luar',
                'is_active' => true,
                'created_by' => $users['demo.apoteker']->id,
            ],
        );

        $pharmacistStaff = PharmacyStaff::query()->firstOrCreate(
            ['user_id' => $users['demo.farmasi']->id],
            [
                'name' => 'Nadia Tenaga Farmasi Demo',
                'position' => 'Tenaga farmasi',
                'is_active' => true,
                'created_by' => $users['demo.apoteker']->id,
            ],
        );

        $cashAccount = CashBankAccount::query()->firstOrCreate(
            ['name' => 'Kas Utama'],
            [
                'type' => 'kas',
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'is_active' => true,
                'created_by' => $users['demo.apoteker']->id,
            ],
        );

        $items = [
            'paracetamol' => $this->createDemoItem(
                code: 'DEMO-OBT-001',
                barcode: '8990000000001',
                name: 'Paracetamol 500 mg',
                itemType: 'obat',
                category: $medicineCategory,
                unit: $tablet,
                taxable: true,
                dosageForm: 'tablet',
                strengthVolume: '500 mg',
            ),
            'amoxicillin' => $this->createDemoItem(
                code: 'DEMO-OBT-002',
                barcode: '8990000000002',
                name: 'Amoxicillin 500 mg',
                itemType: 'obat',
                category: $medicineCategory,
                unit: $tablet,
                taxable: true,
                dosageForm: 'kapsul',
                strengthVolume: '500 mg',
            ),
            'mask' => $this->createDemoItem(
                code: 'DEMO-NON-001',
                barcode: '8990000000003',
                name: 'Masker Medis 3 Ply',
                itemType: 'non_obat',
                category: $nonDrugCategory,
                unit: $pcs,
                taxable: false,
                dosageForm: null,
                strengthVolume: null,
            ),
        ];

        foreach ([
            [$items['paracetamol'], 'obat_bebas'],
            [$items['amoxicillin'], 'resep'],
            [$items['mask'], 'produk_lain'],
        ] as [$item, $salesType]) {
            app(PricingService::class)->saveMarginSetting([
                'scope_type' => 'item',
                'scope_id' => $item->id,
                'sales_type' => $salesType,
                'margin_percent' => '25.00',
            ]);
        }

        $healthService = HealthService::query()->firstOrCreate(
            ['name' => 'Konsultasi kesehatan demo'],
            [
                'default_fee' => '25000.00',
                'is_active' => true,
                'created_by' => $users['demo.apoteker']->id,
            ],
        );

        return [
            'supplier' => $supplier,
            'patients' => $patients,
            'doctor' => $doctor,
            'pharmacist_staff' => $pharmacistStaff,
            'cash_account' => $cashAccount,
            'items' => $items,
            'health_service' => $healthService,
        ];
    }

    private function createDemoItem(
        string $code,
        string $barcode,
        string $name,
        string $itemType,
        ItemCategory $category,
        Unit $unit,
        bool $taxable,
        ?string $dosageForm,
        ?string $strengthVolume,
    ): Item {
        $item = Item::query()->firstOrCreate(
            ['code' => $code],
            [
                'item_type' => $itemType,
                'name' => $name,
                'dosage_form' => $dosageForm,
                'strength_volume' => $strengthVolume,
                'category_id' => $category->id,
                'base_unit_id' => $unit->id,
                'order_unit_id' => $unit->id,
                'barcode' => $barcode,
                'is_taxable' => $taxable,
                'movement_class' => 'medium',
                'min_stock_override' => '10.000',
                'is_active' => true,
                'created_by' => Auth::id(),
            ],
        );

        ItemUnit::query()->firstOrCreate(
            ['item_id' => $item->id, 'unit_id' => $unit->id],
            ['factor_to_base' => '1.000', 'created_by' => Auth::id()],
        );

        return $item;
    }

    /** @param array{paracetamol: Item, amoxicillin: Item, mask: Item} $items */
    private function seedReceipt(Supplier $supplier, array $items): void
    {
        $receipt = GoodsReceipt::query()->where('invoice_no', 'DEMO-INV-001')->first();

        if (! $receipt) {
            $lines = [];
            foreach ([
                ['item' => $items['paracetamol'], 'qty' => '120.000', 'price' => '500.00', 'batch' => 'DEMO-PCT-001'],
                ['item' => $items['amoxicillin'], 'qty' => '60.000', 'price' => '1000.00', 'batch' => 'DEMO-AMX-001'],
                ['item' => $items['mask'], 'qty' => '50.000', 'price' => '200.00', 'batch' => 'DEMO-MSK-001'],
            ] as $line) {
                $itemUnit = ItemUnit::query()
                    ->where('item_id', $line['item']->id)
                    ->where('unit_id', $line['item']->base_unit_id)
                    ->firstOrFail();

                $lines[] = [
                    'item_id' => $line['item']->id,
                    'item_unit_id' => $itemUnit->id,
                    'qty_received' => $line['qty'],
                    'batch_no' => $line['batch'],
                    'expiry_date' => today()->addYears(2)->toDateString(),
                    'unit_price' => $line['price'],
                    'discount_amount' => '0.00',
                ];
            }

            $receipt = app(GoodsReceiptService::class)->createDraft([
                'supplier_id' => $supplier->id,
                'invoice_no' => 'DEMO-INV-001',
                'invoice_date' => today()->toDateString(),
                'payment_method' => 'kredit',
                'discount_amount' => '0.00',
                'lines' => $lines,
            ]);
        }

        if ($receipt->status === 'draft') {
            app(GoodsReceiptService::class)->confirm($receipt);
        }
    }

    /** @param array{patients: array{cash: Patient, credit: Patient}, cash_account: CashBankAccount, items: array{paracetamol: Item, amoxicillin: Item, mask: Item}} $demoData */
    private function seedSales(User $cashier, array $demoData): void
    {
        $this->createDemoSale(
            cashier: $cashier,
            patient: $demoData['patients']['cash'],
            item: $demoData['items']['paracetamol'],
            salesType: 'obat_bebas',
            paymentMethod: 'tunai',
            quantity: '2.000',
            cashAccount: $demoData['cash_account'],
        );
        $this->createDemoSale(
            cashier: $cashier,
            patient: $demoData['patients']['credit'],
            item: $demoData['items']['mask'],
            salesType: 'produk_lain',
            paymentMethod: 'kredit',
            quantity: '3.000',
            cashAccount: $demoData['cash_account'],
        );
    }

    private function createDemoSale(
        User $cashier,
        Patient $patient,
        Item $item,
        string $salesType,
        string $paymentMethod,
        string $quantity,
        CashBankAccount $cashAccount,
    ): void {
        $alreadySeeded = Sale::query()
            ->where('cashier_id', $cashier->id)
            ->where('patient_id', $patient->id)
            ->where('sale_type', $salesType)
            ->where('status', 'completed')
            ->whereHas('items', fn (Builder $query): Builder => $query->where('item_id', $item->id))
            ->exists();

        if ($alreadySeeded) {
            return;
        }

        $price = app(PricingService::class)->currentPrice($item, $salesType);
        if (! $price) {
            throw new RuntimeException("Harga demo untuk {$item->code} belum tersedia.");
        }

        $amount = bcmul((string) $price->selling_price, $quantity, 2);
        $payment = [
            'method' => $paymentMethod,
            'amount' => $amount,
        ];

        if ($paymentMethod === 'tunai') {
            $payment['cash_received'] = bcadd($amount, '10000.00', 2);
            $payment['cash_bank_account_id'] = $cashAccount->id;
        }

        Auth::login($cashier);
        app(SaleService::class)->checkout([
            'sale_type' => $salesType,
            'patient_id' => $patient->id,
            'cart' => [
                'item-'.$item->id => [
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                ],
            ],
            'payments' => [$payment],
        ]);
    }

    /** @param array{patients: array{cash: Patient, credit: Patient}, doctor: Doctor, pharmacist_staff: PharmacyStaff, items: array{paracetamol: Item, amoxicillin: Item, mask: Item}} $demoData */
    private function seedPrescription(array $demoData): void
    {
        if (Prescription::query()->where('notes', self::DEMO_RX_NOTES)->exists()) {
            return;
        }

        $item = $demoData['items']['amoxicillin'];
        Auth::login($demoData['pharmacist_staff']->user()->firstOrFail());
        app(PrescriptionWorkflowService::class)->create([
            'patient_id' => $demoData['patients']['cash']->id,
            'doctor_id' => $demoData['doctor']->id,
            'rx_date' => today()->toDateString(),
            'rx_group' => 'dokter_luar',
            'notes' => self::DEMO_RX_NOTES,
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $item->base_unit_id,
                'qty_prescribed' => '10.000',
                'dosage_instruction' => '1 kapsul, 3 kali sehari (contoh demo)',
            ]],
        ]);
    }
}
