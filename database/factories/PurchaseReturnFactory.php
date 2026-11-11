<?php

namespace Database\Factories;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Inventory\Models\PurchaseReturn;
use App\Modules\Md\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseReturn> */
class PurchaseReturnFactory extends Factory
{
    public function definition(): array
    {
        $supplier = Supplier::factory();

        return [
            'return_no' => fake()->unique()->bothify('RET-######'),
            'supplier_id' => $supplier,
            'goods_receipt_id' => GoodsReceipt::factory()->for($supplier),
            'return_date' => today(),
            'total_value' => '1000.00',
            'payable_adjustment_amount' => '0.00',
            'supplier_credit_amount' => '0.00',
            'status' => 'draft',
        ];
    }
}
