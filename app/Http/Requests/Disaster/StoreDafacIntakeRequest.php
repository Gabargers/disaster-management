<?php

namespace App\Http\Requests\Disaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDafacIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage dafac intake') === true;
    }

    protected function prepareForValidation(): void
    {
        $members = collect($this->input('family_members', []))->filter(fn ($member) => collect($member)->contains(fn ($value) => $value !== null && trim((string) $value) !== ''))->values()->all();
        $this->merge(['family_members' => $members]);
    }

    public function rules(): array
    {
        return [
            'disaster_id' => ['required', 'integer', Rule::exists('disasters', 'id')->where('is_active', true)],
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'evacuation_center_id' => ['nullable', 'integer', Rule::exists('evacuation_centers', 'id')->where(fn ($q) => $q->where('is_active', true)->where('barangay_id', $this->integer('barangay_id')))],
            'intake_date' => ['required', 'date', 'before_or_equal:today'],
            'household_head.surname' => ['required', 'string', 'max:255'],
            'household_head.given_name' => ['required', 'string', 'max:255'],
            'household_head.middle_name' => ['nullable', 'string', 'max:255'],
            'household_head.complete_address' => ['required', 'string', 'max:2000'],
            'household_head.birthdate' => ['required', 'date', 'before_or_equal:today'],
            'household_head.occupation' => ['nullable', 'string', 'max:255'],
            'household_head.monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'household_head.contact_number' => ['nullable', 'string', 'max:30'],
            'house_ownership' => ['required', Rule::in(['Owner', 'Renter', 'Sharer'])],
            'housing_condition' => ['required', Rule::in(['Totally Damaged', 'Partially Damaged', 'Water Damage'])],
            'health_condition' => ['nullable', Rule::in(['Dead', 'Injured', 'Missing', 'With Illness'])],
            'interviewed_by' => ['required', 'string', 'max:255'],
            'attestation_confirmed' => ['accepted'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'thumbmark' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'family_members' => ['array', 'max:30'],
            'family_members.*.full_name' => ['required', 'string', 'max:255'],
            'family_members.*.birthdate' => ['required', 'date', 'before_or_equal:today'],
            'family_members.*.relationship_to_head' => ['required', 'string', 'max:100'],
            'family_members.*.sex' => ['required', Rule::in(['Male', 'Female'])],
            'family_members.*.occupation' => ['nullable', 'string', 'max:255'],
            'family_members.*.monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'family_members.*.health_condition' => ['nullable', Rule::in(['Dead', 'Injured', 'Missing', 'With Illness'])],
            'family_members.*.remarks_code' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'E', 'F'])],
        ];
    }
}
