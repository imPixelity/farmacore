<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrescriptionItem> */
class PrescriptionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),
            'qty_prescribed' => '1.000',
            'dosage_instruction' => '3 kali sehari sesudah makan',
            'is_compound' => false,
            'compound_group' => null,
            'prepared_qty' => null,
        ];
    }
}
