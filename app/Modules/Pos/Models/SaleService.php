<?php

namespace App\Modules\Pos\Models;

use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\HealthService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'health_service_id', 'service_name', 'fee', 'result_note'])]
class SaleService extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['fee' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function healthService(): BelongsTo
    {
        return $this->belongsTo(HealthService::class);
    }
}
