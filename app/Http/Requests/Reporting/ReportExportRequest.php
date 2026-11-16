<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'format' => ['required', Rule::in(['pdf', 'xlsx'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required' => 'Tanggal mulai wajib diisi.',
            'from.date_format' => 'Tanggal mulai harus menggunakan format YYYY-MM-DD yang valid.',
            'to.required' => 'Tanggal akhir wajib diisi.',
            'to.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD yang valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau sesudah tanggal mulai.',
            'format.required' => 'Format ekspor wajib dipilih.',
            'format.in' => 'Format ekspor harus PDF atau XLSX.',
        ];
    }
}
