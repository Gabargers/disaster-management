<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonAffectedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'control_number' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['affected'])],
            'date_tagged' => [
                'required',
                'date',
                'regex:/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:?\d{2})?$/',
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'sex' => ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'health_condition' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'family_head_name' => ['nullable', 'string', 'max:255'],
            'family_head_control_number' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'housing' => ['nullable', 'string', 'max:255'],
            'family_members' => ['sometimes', 'array', 'max:100'],
            'family_members.*.control_number' => ['required', 'string', 'max:255', 'distinct'],
            'family_members.*.full_name' => ['required', 'string', 'max:255'],
            'family_members.*.relationship' => ['nullable', 'string', 'max:255'],
            'family_members.*.age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'family_members.*.sex' => ['nullable', 'string', 'max:30'],
            'family_members.*.code' => ['nullable', 'string', 'max:255'],
            'family_members.*.housing' => ['nullable', 'string', 'max:255'],
        ];
    }
}
