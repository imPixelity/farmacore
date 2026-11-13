<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from.required_with' => 'Tanggal mulai wajib diisi jika tanggal akhir dipilih.',
            'from.date_format' => 'Tanggal mulai harus menggunakan format YYYY-MM-DD yang valid.',
            'to.required_with' => 'Tanggal akhir wajib diisi jika tanggal mulai dipilih.',
            'to.date_format' => 'Tanggal akhir harus menggunakan format YYYY-MM-DD yang valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau sesudah tanggal mulai.',
        ];
    }
}
