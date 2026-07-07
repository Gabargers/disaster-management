<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarangayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null,
            'district' => $this->filled('district') ? trim((string) $this->input('district')) : null,
            'captain_name' => $this->filled('captain_name') ? trim((string) $this->input('captain_name')) : null,
            'contact_number' => $this->filled('contact_number') ? trim((string) $this->input('contact_number')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $barangay = $this->route('barangay');
        $barangayId = $barangay?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('barangays', 'name')->ignore($barangayId),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('barangays', 'code')->ignore($barangayId),
            ],
            'district' => ['nullable', 'string', 'max:100'],
            'captain_name' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
