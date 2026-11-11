<?php

namespace Database\Factories;

use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Patient;
use App\Modules\Rx\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prescription> */
class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rx_no' => fake()->unique()->bothify('RX-######'),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'rx_date' => today(),
            'rx_group' => 'dokter_apotek',
            'status' => 'received',
            'notes' => null,
        ];
    }
}
