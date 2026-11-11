<?php

namespace Database\Factories;

use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionDispense;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrescriptionDispense> */
class PrescriptionDispenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'status' => 'diserahkan',
            'received_by_name' => fake()->name(),
            'pharmacy_staff_id' => PharmacyStaff::factory(),
            'dispensed_at' => now(),
        ];
    }
}
