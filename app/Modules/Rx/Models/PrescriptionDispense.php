<?php

namespace App\Modules\Rx\Models;

use App\Modules\Md\Models\PharmacyStaff;
use Database\Factories\PrescriptionDispenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prescription_id', 'status', 'received_by_name', 'pharmacy_staff_id', 'dispensed_at'])]
#[UseFactory(PrescriptionDispenseFactory::class)]
class PrescriptionDispense extends Model
{
    /** @use HasFactory<PrescriptionDispenseFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['dispensed_at' => 'datetime'];
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
