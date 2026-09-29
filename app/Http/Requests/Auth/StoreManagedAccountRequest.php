<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManagedAccountRequest extends FormRequest
{
    public const MANAGED_ROLES = [
        'admin',
        'paymaster-cashier',
        'encoder',
    ];

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'superadmin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'id_number' => ['required', 'string', 'max:50', 'unique:users,id_number'],
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => ['required', 'distinct', Rule::in(self::MANAGED_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_number' => strtoupper(trim((string) $this->input('id_number'))),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
