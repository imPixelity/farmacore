<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class CoreSettingsSeeder extends Seeder
{
    /**
     * Seed default values without overwriting settings already configured by the apoteker.
     */
    public function run(): void
    {
        foreach ([
            'login_max_attempts' => 5,
            'login_lockout_minutes' => 15,
            'session_timeout_minutes' => 30,
        ] as $key => $value) {
            AppSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
