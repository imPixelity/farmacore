<?php

namespace App\Modules\Rx\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Patient;
use Database\Factories\PrescriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

#[Fillable(['rx_no', 'patient_id', 'doctor_id', 'rx_date', 'rx_group', 'status', 'notes', 'created_by', 'updated_by'])]
#[UseFactory(PrescriptionFactory::class)]
class Prescription extends Model
{
    /** @use HasFactory<PrescriptionFactory> */
    use Auditable, HasFactory;

    private static bool $workflowChanging = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['rx_date' => 'date'];
    }

    /** @template TReturn
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function duringWorkflow(callable $callback): mixed
    {
        $previous = self::$workflowChanging;
        self::$workflowChanging = true;

        try {
            return $callback();
        } finally {
            self::$workflowChanging = $previous;
        }
    }

    protected static function booted(): void
    {
        static::creating(function (Prescription $prescription): void {
            if ($prescription->status !== 'received') {
                throw new LogicException('Resep baru harus berstatus received.');
            }
        });

        static::updating(function (Prescription $prescription): void {
            if ($prescription->isDirty('status') && ! self::$workflowChanging) {
                throw new LogicException('Status resep hanya dapat diubah melalui PrescriptionWorkflowService.');
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(PrescriptionScreening::class);
    }

    public function compoundings(): HasMany
    {
        return $this->hasMany(PrescriptionCompounding::class);
    }

    public function dispense(): HasOne
    {
        return $this->hasOne(PrescriptionDispense::class);
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
