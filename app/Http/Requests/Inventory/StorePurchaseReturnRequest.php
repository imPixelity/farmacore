<?php

namespace App\Http\Requests\Inventory;

use App\Modules\Inventory\Services\PurchaseReturnService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.purchase_return.manage') ?? false;
    }

    /** @return array<string, array<int, string|Rule>> */
    public function rules(): array
    {
        return [
            'goods_receipt_id' => ['required', 'integer', Rule::exists('goods_receipts', 'id')->where('status', 'confirmed')],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.goods_receipt_item_id' => ['required', 'integer', 'distinct', Rule::exists('goods_receipt_items', 'id')],
            'lines.*.item_batch_id' => ['required', 'integer', Rule::exists('item_batches', 'id')],
            'lines.*.qty_base' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'min:0', 'max:999999999999.999'],
            'lines.*.reason' => ['required', Rule::in(PurchaseReturnService::REASONS)],
        ];
    }
}
