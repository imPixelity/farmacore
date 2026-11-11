<?php

namespace Database\Factories;

use App\Modules\Md\Models\DiscountRule;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscountRule> */
class DiscountRuleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'rule_type' => 'qty',
            'sales_type' => null,
            'item_id' => null,
            'category_id' => null,
            'min_qty' => '2.000',
            'min_amount' => null,
            'discount_type' => 'percent',
            'discount_value' => '10.00',
            'effective_from' => today(),
            'effective_until' => null,
            'is_active' => true,
        ];
    }

    public function forItem(Item $item): static
    {
        return $this->state(fn (): array => ['item_id' => $item->id, 'category_id' => null]);
    }

    public function forCategory(ItemCategory $category): static
    {
        return $this->state(fn (): array => ['item_id' => null, 'category_id' => $category->id]);
    }

    public function nominal(string $minimumAmount): static
    {
        return $this->state(fn (): array => [
            'rule_type' => 'nominal',
            'min_qty' => null,
            'min_amount' => $minimumAmount,
        ]);
    }
}
