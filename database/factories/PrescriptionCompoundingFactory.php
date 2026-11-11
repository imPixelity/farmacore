<?php

namespace Database\Factories;

use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionCompounding;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrescriptionCompounding> */
class PrescriptionCompoundingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'compound_name' => 'Racikan '.$this->faker->word(),
            'yield_qty' => '1.000',
            'yield_unit' => 'bungkus',
            'pharmacy_staff_id' => PharmacyStaff::factory(),
            'performed_at' => now(),
        ];
    }
}
