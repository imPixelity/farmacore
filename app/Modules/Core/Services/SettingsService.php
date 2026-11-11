<?php

namespace App\Modules\Core\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettingsService
{
    /**
     * @var array<string, array{label: string, type: string, default: mixed, help: string}>
     */
    private const DEFINITIONS = [
        'expiry_alert_days' => [
            'label' => 'Ambang peringatan kedaluwarsa (hari)',
            'type' => 'number',
            'default' => null,
            'help' => 'Dibiarkan kosong sampai kebijakan apotek ditetapkan.',
        ],
        'payable_alert_days' => [
            'label' => 'Ambang peringatan utang (hari)',
            'type' => 'number',
            'default' => null,
            'help' => 'Dibiarkan kosong sampai kebijakan apotek ditetapkan.',
        ],
        'login_max_attempts' => [
            'label' => 'Batas percobaan login gagal',
            'type' => 'number',
            'default' => 5,
            'help' => 'Perubahan berlaku pada login berikutnya.',
        ],
        'login_lockout_minutes' => [
            'label' => 'Durasi penguncian akun (menit)',
            'type' => 'number',
            'default' => 15,
            'help' => 'Perubahan berlaku pada login berikutnya.',
        ],
        'session_timeout_minutes' => [
            'label' => 'Batas sesi tidak aktif (menit)',
            'type' => 'number',
            'default' => 30,
            'help' => 'Perubahan berlaku pada permintaan berikutnya.',
        ],
        'price_rounding_step' => [
            'label' => 'Langkah pembulatan harga',
            'type' => 'number',
            'default' => 1,
            'help' => 'Harga jual dibulatkan ke kelipatan nilai ini; default Rp 1.',
        ],
        'usage_average_months' => [
            'label' => 'Periode rata-rata pemakaian (bulan)',
            'type' => 'number',
            'default' => 3,
            'help' => 'Jumlah bulan yang digunakan untuk menghitung rata-rata pemakaian.',
        ],
        'pharmacy_receipt_identity' => [
            'label' => 'Identitas apotek pada struk',
            'type' => 'text',
            'default' => null,
            'help' => 'Nama dan informasi identitas yang akan dicetak pada struk.',
        ],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, self::DEFINITIONS)) {
            throw new InvalidArgumentException("Unknown application setting [{$key}].");
        }

        $value = Cache::remember(
            $this->cacheKey($key),
            now()->addHour(),
            fn (): mixed => AppSetting::query()->find($key)?->value ?? $default,
        );

        return $value ?? $default;
    }

    /**
     * @return array<string, array{label: string, type: string, default: mixed, help: string, value: mixed}>
     */
    public function all(): array
    {
        $settings = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $settings[$key] = [
                ...$definition,
                'value' => $this->get($key, $definition['default']),
            ];
        }

        return $settings;
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        foreach (array_keys($values) as $key) {
            if (! array_key_exists($key, self::DEFINITIONS)) {
                throw new InvalidArgumentException("Unknown application setting [{$key}].");
            }
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $setting = AppSetting::query()->firstOrNew(['key' => $key]);
                $setting->value = $value;
                $setting->updated_by = Auth::id();
                $setting->save();
            }
        });

        foreach (array_keys($values) as $key) {
            Cache::forget($this->cacheKey($key));
        }
    }

    private function cacheKey(string $key): string
    {
        return 'core.settings.'.$key;
    }
}
