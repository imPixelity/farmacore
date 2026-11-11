<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $patient = $this->route('patient');
        $uniqueCode = Rule::unique('patients', 'code');

        if ($patient instanceof Patient) {
            $uniqueCode->ignore($patient);
        }

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'name' => ['required', 'string', 'max:150'],
            'identity_no' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:5000'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
