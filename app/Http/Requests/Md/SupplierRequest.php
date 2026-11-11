<?php

namespace App\Http\Requests\Md;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'lead_time_days' => ['nullable', 'numeric', 'decimal:0,1', 'min:0', 'max:9999.9'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
