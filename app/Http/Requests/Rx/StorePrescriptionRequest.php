<?php

namespace App\Http\Requests\Rx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rx.prescription.manage') ?? false;
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('is_active', true)],
            'doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)],
            'rx_date' => ['required', 'date_format:Y-m-d'],
            'rx_group' => ['required', Rule::in(['dokter_apotek', 'dokter_luar', 'bpjs'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'items.*.unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'items.*.qty_prescribed' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999999999.999'],
            'items.*.dosage_instruction' => ['required', 'string', 'max:255'],
            'items.*.is_compound' => ['nullable', 'boolean'],
            'items.*.compound_group' => ['nullable', 'string', 'max:30'],
        ];
    }
}
