<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\Payable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payable>
 */
class PayableFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $supplier = Supplier::factory();

        return [
            'supplier_id' => $supplier,
            'goods_receipt_id' => GoodsReceipt::factory()->for($supplier),
            'invoice_no' => fake()->unique()->bothify('INV-######'),
            'total_amount' => '1000.00',
            'paid_amount' => '0.00',
            'due_date' => today()->addDays(30)->toDateString(),
            'status' => 'belum_dibayar',
        ];
    }
}
