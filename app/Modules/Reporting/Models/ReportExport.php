<?php

namespace App\Modules\Reporting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'report_key', 'format', 'from_date', 'to_date', 'status', 'file_path', 'failure_message', 'generated_at'])]
class ReportExport extends Model
{
    protected function casts(): array
    {
        return [
            'from_date' => 'immutable_date',
            'to_date' => 'immutable_date',
            'generated_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function downloadFilename(): string
    {
        return sprintf(
            'laporan-%s-%s-sampai-%s-%d.%s',
            $this->report_key,
            $this->from_date->format('Ymd'),
            $this->to_date->format('Ymd'),
            $this->getKey(),
            $this->format,
        );
    }
}
