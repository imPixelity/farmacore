<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\HealthService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $healthService = $this->route('healthService');
        $uniqueName = Rule::unique('health_services', 'name');

        if ($healthService instanceof HealthService) {
            $uniqueName->ignore($healthService);
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'default_fee' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
