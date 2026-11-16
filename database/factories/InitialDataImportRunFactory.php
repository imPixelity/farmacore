<?php

namespace Database\Factories;

use App\Modules\Core\Models\InitialDataImportRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InitialDataImportRun>
 */
class InitialDataImportRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'content_hash' => hash('sha256', fake()->unique()->uuid()),
            'summary' => ['items_created' => 0, 'opening_movements_created' => 0],
            'created_by' => null,
        ];
    }
}
