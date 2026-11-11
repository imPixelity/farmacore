<?php

namespace App\Http\Requests\Md;

use App\Modules\Md\Models\ItemCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $category = $this->route('category');
        $uniqueName = Rule::unique('item_categories', 'name')->where('type', $this->input('type'));

        if ($category instanceof ItemCategory) {
            $uniqueName->ignore($category);
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'type' => ['required', Rule::in(['obat', 'non_obat'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
