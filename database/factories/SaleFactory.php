<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Pos\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_no' => fake()->unique()->bothify('INV-######'),
            'sale_type' => 'obat_bebas',
            'sale_date' => now(),
            'cashier_id' => User::factory(),
            'subtotal' => '1000.00',
            'discount_total' => '0.00',
            'doctor_fee' => '0.00',
            'tuslah_fee' => '0.00',
            'service_fee_total' => '0.00',
            'tax_amount' => '0.00',
            'grand_total' => '1000.00',
            'payment_status' => 'lunas',
            'status' => 'completed',
        ];
    }
}
