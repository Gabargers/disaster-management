<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PersonAffected extends Model
{
    protected $fillable = [
        'control_number', 'full_name', 'birthdate', 'age', 'sex', 'code', 'occupation',
        'monthly_income', 'health_condition', 'district', 'barangay', 'street', 'city',
        'family_head_name', 'family_head_control_number', 'relationship', 'housing',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'monthly_income' => 'decimal:2'];
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(PersonAffectedStatus::class);
    }

    public function latestStatus(): HasOne
    {
        return $this->hasOne(PersonAffectedStatus::class)->latestOfMany('date_tagged');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(PersonAffectedFamilyMember::class);
    }
}
