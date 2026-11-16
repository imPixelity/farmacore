<?php

namespace App\Http\Requests\Core;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewInitialDataImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.data_import.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $extension = strtolower($this->file('file')?->getClientOriginalExtension() ?? '');

        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,xlsx', 'extensions:csv,xlsx'],
            'csv_type' => $extension === 'csv'
                ? ['required', Rule::in(['items', 'suppliers', 'stock'])]
                : ['nullable', Rule::in(['items', 'suppliers', 'stock'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih berkas CSV atau XLSX untuk diperiksa.',
            'file.file' => 'Berkas impor tidak valid.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
            'file.mimes' => 'Format berkas harus CSV atau XLSX.',
            'file.extensions' => 'Ekstensi nama berkas harus .csv atau .xlsx.',
            'csv_type.required' => 'Pilih jenis template untuk berkas CSV.',
            'csv_type.in' => 'Jenis template CSV tidak dikenal.',
        ];
    }
}
