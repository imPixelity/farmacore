<?php

namespace Database\Factories;

use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionScreening;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrescriptionScreening> */
class PrescriptionScreeningFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'checklist' => ['patient_match' => true, 'dose_safe' => true, 'interaction_checked' => true],
            'result' => 'disetujui',
            'notes' => null,
            'pharmacy_staff_id' => PharmacyStaff::factory(),
            'performed_at' => now(),
        ];
    }
}
