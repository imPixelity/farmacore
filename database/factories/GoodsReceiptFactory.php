<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Md\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'receipt_no' => 'GR-'.Str::ulid(),
            'supplier_id' => Supplier::factory(),
            'invoice_no' => fake()->unique()->bothify('INV-######'),
            'invoice_date' => today()->toDateString(),
            'due_date' => null,
            'payment_method' => 'tunai',
            'discount_amount' => '0.00',
            'total_before_tax' => '0.00',
            'tax_amount' => '0.00',
            'total_after_tax' => '0.00',
            'matching_status' => 'tidak_ada_po',
            'status' => 'draft',
        ];
    }
}
