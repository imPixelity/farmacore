<?php

namespace App\Http\Requests\Md;

use App\Models\User;
use App\Modules\Md\Models\PharmacyStaff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PharmacyStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('md.item.manage') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $pharmacyStaff = $this->route('pharmacyStaff');
        $uniqueUser = Rule::unique('pharmacy_staff', 'user_id');

        if ($pharmacyStaff instanceof PharmacyStaff) {
            $uniqueUser->ignore($pharmacyStaff->user_id, 'user_id');
        }

        return [
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                $uniqueUser,
            ],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('user_id')) {
                return;
            }

            $hasPharmacyStaffRole = User::query()
                ->whereKey($this->integer('user_id'))
                ->whereHas('roles', fn ($query) => $query->where('name', 'tenaga_farmasi')->where('guard_name', 'web'))
                ->exists();

            if (! $hasPharmacyStaffRole) {
                $validator->errors()->add('user_id', 'Akun harus memiliki peran tenaga farmasi.');
            }
        }];
    }
}
