<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $unit = $this->route('unit');
        $uniqueName = Rule::unique('units', 'name');

        if ($unit instanceof Unit) {
            $uniqueName->ignore($unit);
        }

        return [
            'name' => ['required', 'string', 'max:30', $uniqueName],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
