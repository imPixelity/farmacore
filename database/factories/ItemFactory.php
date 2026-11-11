<?php

namespace Database\Factories;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $itemType = fake()->randomElement(['obat', 'alkes', 'non_obat']);

        return [
            'code' => fake()->unique()->bothify('ITM-######'),
            'item_type' => $itemType,
            'name' => fake()->unique()->words(3, true),
            'dosage_form' => $itemType === 'obat' ? fake()->randomElement(['tablet', 'sirup', 'kapsul']) : null,
            'strength_volume' => $itemType === 'obat' ? fake()->bothify('### mg') : null,
            'category_id' => ItemCategory::factory()->state(['type' => $itemType === 'obat' ? 'obat' : 'non_obat']),
            'base_unit_id' => Unit::factory(),
            'order_unit_id' => Unit::factory(),
            'barcode' => null,
            'is_taxable' => false,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Item $item): void {
            ItemUnit::query()->firstOrCreate(
                ['item_id' => $item->id, 'unit_id' => $item->base_unit_id],
                ['factor_to_base' => 1],
            );
            ItemUnit::query()->firstOrCreate(
                ['item_id' => $item->id, 'unit_id' => $item->order_unit_id],
                ['factor_to_base' => 1],
            );
        });
    }
}
