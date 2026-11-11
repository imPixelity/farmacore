<?php

namespace App\Models;

use App\Modules\Core\Traits\Auditable;
use Database\Factories\AppSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'updated_by'])]
class AppSetting extends Model
{
    /** @use HasFactory<AppSettingFactory> */
    use Auditable, HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'updated_by' => 'integer',
        ];
    }
}
