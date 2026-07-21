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
        ];
    }
}
