<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $baseUnitId = $this->input('base_unit_id');
        $conversions = $this->input('conversions', []);

        if (is_numeric($baseUnitId) && is_array($conversions)) {
            $conversions[(string) $baseUnitId] = 1;
            $this->merge(['conversions' => $conversions]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $item = $this->route('item');
        $categoryType = $this->input('item_type') === 'obat' ? 'obat' : 'non_obat';
        $uniqueCode = Rule::unique('items', 'code');
        $uniqueBarcode = Rule::unique('items', 'barcode');

        if ($item instanceof Item) {
            $uniqueCode->ignore($item);
            $uniqueBarcode->ignore($item);
        }

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'item_type' => ['required', Rule::in(['obat', 'alkes', 'non_obat'])],
            'name' => ['required', 'string', 'max:150'],
            'dosage_form' => ['nullable', 'string', 'max:50'],
            'strength_volume' => ['nullable', 'string', 'max:50'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('item_categories', 'id')->where('type', $categoryType)->where('is_active', true),
            ],
            'base_unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('is_active', true)],
            'order_unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('is_active', true)],
            'barcode' => ['nullable', 'string', 'max:50', $uniqueBarcode],
            'is_taxable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'conversions' => ['required', 'array', 'min:1'],
            'conversions.*' => ['nullable', 'numeric', 'decimal:0,3', 'min:0.001'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $conversions = $this->input('conversions', []);
            $baseUnitId = (string) $this->input('base_unit_id');
            $orderUnitId = (string) $this->input('order_unit_id');

            if (! array_key_exists($baseUnitId, $conversions)) {
                $validator->errors()->add('conversions', 'Satuan terkecil harus ada di editor konversi.');
            }

            if ($orderUnitId !== $baseUnitId && blank($conversions[$orderUnitId] ?? null)) {
                $validator->errors()->add('conversions', 'Satuan pemesanan harus memiliki faktor konversi.');
            }

            $unitIds = array_keys($conversions);
            $validIds = array_filter($unitIds, fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false);
            $activeUnitCount = Unit::query()
                ->active()
                ->whereIn('id', $validIds)
                ->count();

            if (count($validIds) !== count($unitIds) || $activeUnitCount !== count(array_unique($validIds))) {
                $validator->errors()->add('conversions', 'Konversi hanya dapat menggunakan satuan aktif yang tersedia.');
            }
        }];
    }
}
