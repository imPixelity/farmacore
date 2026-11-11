<?php

namespace App\Modules\Rx\Models;

use App\Modules\Md\Models\PharmacyStaff;
use Database\Factories\PrescriptionCompoundingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_id', 'compound_name', 'yield_qty', 'yield_unit', 'pharmacy_staff_id', 'performed_at'])]
#[UseFactory(PrescriptionCompoundingFactory::class)]
class PrescriptionCompounding extends Model
{
    /** @use HasFactory<PrescriptionCompoundingFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['yield_qty' => 'decimal:3', 'performed_at' => 'datetime'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function pharmacyStaff(): BelongsTo
    {
        return $this->belongsTo(PharmacyStaff::class);
    }
}
