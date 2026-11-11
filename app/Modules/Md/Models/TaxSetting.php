<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\TaxSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'rate_percent', 'effective_from', 'created_by', 'updated_by'])]
#[UseFactory(TaxSettingFactory::class)]
class TaxSetting extends Model
{
    /** @use HasFactory<TaxSettingFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
