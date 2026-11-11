<?php

namespace App\Http\Requests\Inventory;

use App\Modules\Inventory\Models\GoodsReceipt;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.receipt.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'purchase_order_id' => ['nullable', 'integer', Rule::exists('purchase_orders', 'id')->whereIn('status', ['sent', 'partially_received'])],
            'invoice_no' => ['required', 'string', 'max:50'],
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(['tunai', 'kredit'])],
            'discount_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'numeric', 'min:0', 'max:9999999999999.99'],
            'confirm_duplicate' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*' => ['required', 'array:item_id,item_unit_id,qty_received,batch_no,expiry_date,unit_price,discount_amount,approve_expiry,purchase_order_item_id'],
            'lines.*.purchase_order_item_id' => ['nullable', 'integer', Rule::exists('purchase_order_items', 'id')],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'lines.*.item_unit_id' => ['required', 'integer', Rule::exists('item_units', 'id')],
            'lines.*.qty_received' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999999999.999'],
            'lines.*.batch_no' => ['required', 'string', 'max:60'],
            'lines.*.expiry_date' => ['required', 'date_format:Y-m-d'],
            'lines.*.unit_price' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'lines.*.discount_amount' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/', 'numeric', 'min:0', 'max:9999999999999.99'],
            'lines.*.approve_expiry' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny([
                'supplier_id', 'invoice_date', 'payment_method', 'lines',
            ])) {
                return;
            }

            $supplier = Supplier::query()->find($this->integer('supplier_id'));
            $invoiceDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('invoice_date'));

            if ($this->input('payment_method') === 'kredit'
                && blank($this->input('due_date'))
                && $supplier?->payment_term_days === null) {
                $validator->errors()->add('due_date', 'Tanggal jatuh tempo wajib diisi untuk pembelian kredit jika supplier tidak memiliki tenor default.');
            }

            if (filled($this->input('due_date')) && $invoiceDate !== false) {
                $dueDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('due_date'));

                if ($dueDate !== false && $dueDate->lt($invoiceDate)) {
                    $validator->errors()->add('due_date', 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal faktur.');
                }
            }

            foreach ((array) $this->input('lines', []) as $index => $line) {
                if (! is_array($line) || blank($line['item_id'] ?? null) || blank($line['item_unit_id'] ?? null)) {
                    continue;
                }

                $item = Item::query()->find((int) $line['item_id']);
                $itemUnit = ItemUnit::query()->find((int) $line['item_unit_id']);

                if (! $item?->is_active) {
                    $validator->errors()->add("lines.{$index}.item_id", 'Barang tidak ditemukan atau sudah nonaktif.');
                }

                if (! $itemUnit || (int) $itemUnit->item_id !== (int) $line['item_id']) {
                    $validator->errors()->add("lines.{$index}.item_unit_id", 'Satuan tidak terdaftar untuk barang yang dipilih.');
                } elseif (filled($line['qty_received'] ?? null)
                    && preg_match('/^\d+(?:\.\d{1,3})?$/', (string) $line['qty_received'])) {
                    $qtyBase = bcmul((string) $line['qty_received'], (string) $itemUnit->factor_to_base, 6);

                    if (bccomp($qtyBase, bcadd($qtyBase, '0', 3), 6) !== 0) {
                        $validator->errors()->add("lines.{$index}.qty_received", 'Hasil konversi satuan harus memiliki maksimal tiga angka desimal.');
                    }
                }

                if ($invoiceDate === false || ! filled($line['expiry_date'] ?? null)) {
                    continue;
                }

                $expiryDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $line['expiry_date']);

                if ($expiryDate === false) {
                    continue;
                }

                $requiresApproval = $expiryDate->lt(today()) || $expiryDate->lt($invoiceDate);
                $approvalRequested = filter_var($line['approve_expiry'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $canApprove = $this->user()?->can('inventory.receipt.approve_expiry') ?? false;
                $receipt = $this->route('goodsReceipt');
                $hasExistingApproval = $receipt instanceof GoodsReceipt
                    && $receipt->status === 'draft'
                    && $receipt->invoice_date->toDateString() === $invoiceDate->toDateString()
                    && $receipt->items()
                        ->where('item_id', (int) $line['item_id'])
                        ->where('batch_no', trim((string) ($line['batch_no'] ?? '')))
                        ->whereDate('expiry_date', $expiryDate->toDateString())
                        ->whereNotNull('approved_by')
                        ->exists();

                if ($requiresApproval
                    && ! (($approvalRequested && $canApprove) || (! $approvalRequested && $hasExistingApproval))) {
                    $validator->errors()->add("lines.{$index}.expiry_date", 'ED yang sudah lewat atau lebih awal dari tanggal faktur memerlukan persetujuan apoteker.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'invoice_no.required' => 'Nomor faktur wajib diisi.',
            'invoice_date.required' => 'Tanggal faktur wajib diisi.',
            'due_date.date_format' => 'Tanggal jatuh tempo tidak valid.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'lines.required' => 'Minimal satu baris barang harus diisi.',
            'lines.min' => 'Minimal satu baris barang harus diisi.',
            'lines.*.item_id.required' => 'Barang pada setiap baris wajib dipilih.',
            'lines.*.qty_received.required' => 'Jumlah barang pada setiap baris wajib diisi.',
            'lines.*.batch_no.required' => 'Nomor batch pada setiap baris wajib diisi.',
            'lines.*.expiry_date.required' => 'Tanggal kedaluwarsa pada setiap baris wajib diisi.',
            'lines.*.unit_price.required' => 'Harga beli pada setiap baris wajib diisi.',
        ];
    }
}
