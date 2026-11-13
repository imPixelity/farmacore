<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accounting.tax.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $obligation = $this->route('taxObligation');

        return [
            'tax_type' => [
                'required',
                'string',
                'max:30',
                Rule::in(['PPN', 'PPh', 'lainnya']),
                Rule::unique('tax_obligations', 'tax_type')
                    ->where(fn (Builder $query): Builder => $query->where('period', $this->input('period')))
                    ->ignore($obligation?->id),
            ],
            'period' => ['required', 'date_format:Y-m'],
            'amount_due' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
