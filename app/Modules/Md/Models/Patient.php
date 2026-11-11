<?php

namespace App\Modules\Md\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'name', 'identity_no', 'phone', 'address', 'birth_date', 'is_active', 'created_by', 'updated_by'])]
#[Hidden(['identity_no', 'phone', 'address', 'birth_date'])]
#[UseFactory(PatientFactory::class)]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Patient> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @return array<int, string> */
    protected function auditIgnoredAttributes(): array
    {
        return ['identity_no', 'phone', 'address', 'birth_date'];
    }

    /** @param array<int, string> $changedFields */
    public function recordSensitiveDataChange(array $changedFields): void
    {
        $this->writeAudit('updated', [], ['redacted_fields' => $changedFields]);
    }

    public function maskedIdentityNo(): ?string
    {
        return $this->mask($this->identity_no, 4);
    }

    public function maskedPhone(): ?string
    {
        return $this->mask($this->phone, 3);
    }

    public function maskedAddress(): ?string
    {
        return filled($this->address) ? 'Disamarkan' : null;
    }

    public function maskedBirthDate(): ?string
    {
        return $this->birth_date?->format('Y-**-**');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    private function mask(?string $value, int $visibleCharacters): ?string
    {
        if (blank($value)) {
            return $value;
        }

        $length = mb_strlen($value);
        $visibleLength = min($visibleCharacters, max(0, $length - 1));

        return str_repeat('•', $length - $visibleLength)
            .($visibleLength > 0 ? mb_substr($value, -$visibleLength) : '');
    }
}
