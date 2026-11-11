<?php

namespace App\Http\Requests\Rx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionPrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('rx.screening.perform') ?? false)
            || ($this->user()?->can('rx.prescription.manage') ?? false);
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        $target = $this->input('target');

        return [
            'target' => ['required', Rule::in(['received', 'verified', 'rejected', 'prepared', 'dispensed', 'cancelled'])],
            'checklist' => [Rule::requiredIf(in_array($target, ['verified', 'rejected'], true)), 'nullable', 'array'],
            'checklist.patient_match' => ['required_if:target,verified,rejected', 'boolean'],
            'checklist.dose_safe' => ['required_if:target,verified,rejected', 'boolean'],
            'checklist.interaction_checked' => ['required_if:target,verified,rejected', 'boolean'],
            'prepared_items' => ['required_if:target,prepared', 'nullable', 'array'],
            'prepared_items.*' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999999999.999'],
            'compoundings' => ['nullable', 'array'],
            'compoundings.*.compound_name' => ['required', 'string', 'max:100'],
            'compoundings.*.yield_qty' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999999999.999'],
            'compoundings.*.yield_unit' => ['required', 'string', 'max:30'],
            'received_by_name' => ['required_if:target,dispensed', 'nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
