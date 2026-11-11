<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['movement_class', 'min_stock_days', 'safety_factor', 'created_by', 'updated_by'])]
class StockPolicy extends Model
{
    use Auditable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_stock_days' => 'integer',
            'safety_factor' => 'decimal:2',
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
