<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\MarginSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scope_key', 'scope_type', 'item_id', 'category_id', 'sales_type', 'margin_percent', 'created_by', 'updated_by'])]
#[UseFactory(MarginSettingFactory::class)]
class MarginSetting extends Model
{
    /** @use HasFactory<MarginSettingFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['margin_percent' => 'decimal:2'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
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
