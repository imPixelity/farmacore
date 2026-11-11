<?php

namespace Database\Factories;

use App\Modules\Md\Models\TaxSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaxSetting> */
class TaxSettingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'PPN',
            'rate_percent' => '11.00',
            'effective_from' => today(),
        ];
    }
}
