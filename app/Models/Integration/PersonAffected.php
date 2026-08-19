<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Auth\User;
use App\Models\Disaster\EvacuationCenter;
use App\Models\Disaster\AffectedFamily;
use Illuminate\Database\Eloquent\Builder;

class PersonAffected extends Model
{
    protected $fillable = [
        'affected_family_id', 'control_number', 'full_name', 'birthdate', 'age', 'sex', 'code', 'occupation',
        'monthly_income', 'health_condition', 'district', 'barangay', 'street', 'city',
        'family_head_name', 'family_head_control_number', 'relationship', 'housing', 'housing_condition',
        'evacuation_center_id', 'evacuation_center_assigned_by', 'evacuation_center_assigned_at',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'evacuation_center_assigned_at' => 'datetime'];
    }

    public function scopeFamilyHeads(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('family_head_control_number')
                ->orWhere('family_head_control_number', '')
                ->orWhereColumn('control_number', 'family_head_control_number')
                ->orWhereRaw('LOWER(relationship) IN (?, ?)', ['family head', 'head']);
        });
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

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }
}
