<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['opname_no', 'opname_date', 'status', 'performed_by', 'approved_by', 'approved_at', 'notes'])]
#[UseFactory(StockOpnameFactory::class)]
class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['opname_date' => 'date', 'approved_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
