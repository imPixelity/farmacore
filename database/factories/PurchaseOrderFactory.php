<?php

namespace Database\Factories;

use App\Modules\Md\Models\Supplier;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'po_no' => 'PO-'.Str::upper(Str::random(10)),
            'supplier_id' => Supplier::factory(),
            'order_date' => today(),
            'status' => 'draft',
            'total_estimated' => '0.00',
        ];
    }
}
