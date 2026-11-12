<?php

namespace Database\Factories;

use App\Modules\Accounting\Models\Receivable;
use App\Modules\Md\Models\Patient;
use App\Modules\Pos\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Receivable> */
class ReceivableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'patient_id' => Patient::factory(),
            'total_amount' => '1000.00',
            'paid_amount' => '0.00',
            'due_date' => null,
            'status' => 'belum_dibayar',
        ];
    }
}
