<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                'bail',
                'required',
                'date',
                'regex:/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:?\d{2})$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) &&
                        Carbon::parse($value)->greaterThan(now()->addSeconds(
                            (int) config('services.system_a.clock_skew_seconds', 300)
                        ))) {
                        $fail('The :attribute must not be in the future beyond the allowed clock skew.');
                    }
                },
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
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

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['control_number', 'family_head_control_number'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = $this->normalizeControlNumber($this->input($field));
            }
        }

        if (is_array($this->input('family_members'))) {
            $normalized['family_members'] = collect($this->input('family_members'))
                ->map(function (mixed $member): mixed {
                    if (is_array($member) && isset($member['control_number']) && is_string($member['control_number'])) {
                        $member['control_number'] = $this->normalizeControlNumber($member['control_number']);
                    }

                    return $member;
                })->all();
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $allowed = [
                'control_number', 'status', 'date_tagged', 'full_name', 'birthdate', 'age', 'sex',
                'code', 'occupation', 'monthly_income', 'health_condition', 'district', 'barangay',
                'street', 'city', 'family_head_name', 'family_head_control_number', 'relationship',
                'housing', 'family_members',
            ];

            foreach (array_diff(array_keys($this->all()), $allowed) as $field) {
                $validator->errors()->add($field, "The {$field} field is not allowed.");
            }

            $memberFields = ['control_number', 'full_name', 'relationship', 'age', 'sex', 'code', 'housing'];
            $members = $this->input('family_members', []);
            if (! is_array($members)) {
                return;
            }

            foreach ($members as $index => $member) {
                if (! is_array($member)) {
                    continue;
                }

                foreach (array_diff(array_keys($member), $memberFields) as $field) {
                    $validator->errors()->add(
                        "family_members.{$index}.{$field}",
                        "The family_members.{$index}.{$field} field is not allowed."
                    );
                }
            }
        });
    }

    private function normalizeControlNumber(string $value): string
    {
        return Str::upper((string) preg_replace('/\s+/', '', trim($value)));
    }
}
