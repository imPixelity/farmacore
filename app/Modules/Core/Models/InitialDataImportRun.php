<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\Auditable;
use Database\Factories\InitialDataImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content_hash', 'summary', 'created_by'])]
#[UseFactory(InitialDataImportRunFactory::class)]
class InitialDataImportRun extends Model
{
    /** @use HasFactory<InitialDataImportRunFactory> */
    use Auditable, HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['summary' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
