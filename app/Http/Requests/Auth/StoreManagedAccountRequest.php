<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManagedAccountRequest extends FormRequest
{
    public const MANAGED_ROLES = [
        'cswdo-coordinator',
        'disaster-operation-officer',
        'cares-social-worker',
        'payout-payroll-staff',
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
            'contact_number' => ['required', 'string', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'distinct', Rule::in(self::MANAGED_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_number.regex' => 'Enter a valid Philippine mobile number (for example, 09171234567).',
        ];
    }
}
