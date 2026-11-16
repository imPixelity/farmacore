<?php

namespace Database\Seeders;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'tablet',
            'kapsul',
            'botol',
            'strip',
            'box',
            'pcs',
            'ml',
            'gram',
        ] as $unitName) {
            Unit::query()->firstOrCreate(
                ['name' => $unitName],
                ['is_active' => true],
            );
        }

        foreach ([
            ['name' => 'Obat', 'type' => 'obat'],
            ['name' => 'Alat kesehatan', 'type' => 'non_obat'],
            ['name' => 'Produk non-obat', 'type' => 'non_obat'],
        ] as $category) {
            ItemCategory::query()->firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                ['is_active' => true],
            );
        }

        CashBankAccount::query()->firstOrCreate(
            ['name' => 'Kas Utama', 'type' => 'kas'],
            [
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'is_active' => true,
            ],
        );
    }
}
