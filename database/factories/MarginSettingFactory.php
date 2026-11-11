<?php

namespace Database\Factories;

use App\Modules\Md\Models\MarginSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MarginSetting> */
class MarginSettingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'scope_key' => 'global',
            'scope_type' => 'global',
            'item_id' => null,
            'category_id' => null,
            'sales_type' => 'resep',
            'margin_percent' => '20.00',
        ];
    }
}
