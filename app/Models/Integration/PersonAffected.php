<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Auth\User;
use App\Models\Disaster\EvacuationCenter;

class PersonAffected extends Model
{
    protected $fillable = [
        'control_number', 'full_name', 'birthdate', 'age', 'sex', 'code', 'occupation',
        'monthly_income', 'health_condition', 'district', 'barangay', 'street', 'city',
        'family_head_name', 'family_head_control_number', 'relationship', 'housing',
        'evacuation_center_id', 'evacuation_center_assigned_by', 'evacuation_center_assigned_at',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'evacuation_center_assigned_at' => 'datetime'];
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

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function evacuationCenterAssigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evacuation_center_assigned_by');
    }
}
