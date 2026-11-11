<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $categories = [
            ['source_code' => 'SALE_RX', 'name' => 'Penjualan resep', 'direction' => 'in'],
            ['source_code' => 'SALE_OTC', 'name' => 'Penjualan obat bebas', 'direction' => 'in'],
            ['source_code' => 'SALE_NONDRUG', 'name' => 'Penjualan produk lainnya', 'direction' => 'in'],
            ['source_code' => 'DOCTOR_FEE', 'name' => 'Jasa dokter', 'direction' => 'in'],
            ['source_code' => 'HEALTH_SERVICE', 'name' => 'Jasa pemeriksaan kesehatan', 'direction' => 'in'],
            ['source_code' => 'RECEIVABLE_PAYMENT', 'name' => 'Pembayaran piutang', 'direction' => 'in'],
            ['source_code' => 'PURCHASE', 'name' => 'Pembelian barang', 'direction' => 'out'],
            ['source_code' => 'PAYABLE_PAYMENT', 'name' => 'Pembayaran utang supplier', 'direction' => 'out'],
            ['source_code' => 'ELECTRICITY', 'name' => 'Listrik', 'direction' => 'out'],
            ['source_code' => 'WATER', 'name' => 'Air', 'direction' => 'out'],
            ['source_code' => 'OPERATIONAL', 'name' => 'Biaya operasional', 'direction' => 'out'],
            ['source_code' => 'SALE_REFUND', 'name' => 'Retur penjualan', 'direction' => 'out'],
        ];

        DB::table('transaction_categories')->upsert(
            array_map(fn (array $category): array => [
                ...$category,
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $categories),
            ['source_code'],
            ['name', 'direction', 'is_system', 'is_active', 'updated_at'],
        );
    }
}
