<?php

namespace App\Modules\Reporting\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\Patient;

class ReportAccessAuditService
{
    /** @param array<int, array<string, mixed>|object> $rows */
    public function recordPatientHistoryAccess(
        User $user,
        array $rows,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $action = 'patient_history_report_viewed',
    ): void {
        $patientIds = collect($rows)->map(fn (array|object $row): mixed => data_get($row, 'patient_id'))
            ->filter()
            ->unique();

        foreach ($patientIds as $patientId) {
            AuditLog::query()->create([
                'user_id' => $user->id,
                'event' => 'viewed',
                'auditable_type' => Patient::class,
                'auditable_id' => (string) $patientId,
                'new_values' => ['action' => $action],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }
    }
}
