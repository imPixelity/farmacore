<?php

namespace App\Modules\Rx\Models;

use App\Modules\Md\Models\PharmacyStaff;
use Database\Factories\PrescriptionScreeningFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_id', 'checklist', 'result', 'notes', 'pharmacy_staff_id', 'performed_at'])]
#[UseFactory(PrescriptionScreeningFactory::class)]
class PrescriptionScreening extends Model
{
    /** @use HasFactory<PrescriptionScreeningFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['checklist' => 'array', 'performed_at' => 'datetime'];
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
