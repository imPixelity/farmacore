<?php

namespace App\Modules\Rx\Models;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_id', 'item_id', 'unit_id', 'qty_prescribed', 'dosage_instruction', 'is_compound', 'compound_group', 'prepared_qty'])]
#[UseFactory(PrescriptionItemFactory::class)]
class PrescriptionItem extends Model
{
    /** @use HasFactory<PrescriptionItemFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['qty_prescribed' => 'decimal:3', 'is_compound' => 'boolean', 'prepared_qty' => 'decimal:3'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
