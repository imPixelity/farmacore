<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\TaxRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxRecord> */
class TaxRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'source_type' => Item::class,
            'source_id' => Item::factory(),
            'direction' => 'input',
            'rate_percent' => '11.00',
            'taxable_amount' => '1000.00',
            'tax_amount' => '110.00',
            'transaction_date' => today(),
        ];
    }
}
