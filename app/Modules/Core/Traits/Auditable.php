<?php

namespace App\Modules\Core\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            $model->writeAudit('created', [], $model->auditValues($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            $fields = array_diff(array_keys($changes), [...$model->auditIgnoredAttributes(), 'updated_at']);

            if ($fields === []) {
                return;
            }

            $model->writeAudit(
                'updated',
                Arr::only($model->getPrevious(), $fields),
                Arr::only($changes, $fields),
            );
        });

        static::deleted(function (Model $model): void {
            $model->writeAudit('deleted', $model->auditValues($model->getAttributes()), []);
        });
    }

    /** @return array<int, string> */
    protected function auditIgnoredAttributes(): array
    {
        return ['password', 'remember_token'];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function auditValues(array $attributes): array
    {
        return Arr::except($attributes, [...$this->auditIgnoredAttributes(), 'created_at', 'updated_at']);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    protected function writeAudit(string $event, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
