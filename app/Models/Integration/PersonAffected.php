<?php

namespace App\Models\Integration;

use App\Models\Auth\User;
use App\Models\Disaster\AffectedFamily;
use App\Models\Disaster\EvacuationCenter;
use App\Support\PersonSex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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

    /**
     * Family composition received from TCISS.
     *
     * TCISS sends one resident per API request, so both the household head and
     * their members live in person_affecteds. Members point back to the head's
     * control number through family_head_control_number.
     */
    public function householdMembers(): HasMany
    {
        return $this->hasMany(self::class, 'family_head_control_number', 'control_number')
            ->whereColumn('person_affecteds.control_number', '!=', 'person_affecteds.family_head_control_number');
    }

    /**
     * Resolve household members from either supported TCISS payload shape.
     * Newer integrations send one person per row while older snapshots place
     * the composition in person_affected_family_members.
     */
    public function householdComposition(): Collection
    {
        $residents = $this->relationLoaded('householdMembers')
            ? $this->householdMembers
            : $this->householdMembers()->get();
        $snapshots = ($this->relationLoaded('familyMembers')
            ? $this->familyMembers
            : $this->familyMembers()->get())
            ->reject(fn (PersonAffectedFamilyMember $member) => strcasecmp($member->control_number, $this->control_number) === 0)
            ->values();
        $snapshotsByControl = $snapshots->keyBy(fn (PersonAffectedFamilyMember $member) => mb_strtoupper(trim($member->control_number)));
        $snapshotsByName = $snapshots->keyBy(fn (PersonAffectedFamilyMember $member) => mb_strtolower(trim($member->full_name)));
        $seen = collect();

        $resolved = $residents->map(function (PersonAffected $resident) use ($snapshotsByControl, $snapshotsByName, $seen) {
            $snapshot = $snapshotsByControl->get(mb_strtoupper(trim($resident->control_number)))
                ?? $snapshotsByName->get(mb_strtolower(trim((string) $resident->full_name)));
            $seen->push($snapshot?->id);

            foreach (['relationship', 'age', 'sex', 'code', 'housing'] as $field) {
                if (blank($resident->{$field}) && filled($snapshot?->{$field})) {
                    $resident->setAttribute($field, $snapshot->{$field});
                }
            }
            $resident->setAttribute('sex', PersonSex::normalizeOrPreserve($resident->sex));

            return $resident;
        });

        return $resolved->concat($snapshots->reject(fn (PersonAffectedFamilyMember $member) => $seen->contains($member->id))
            ->each(fn (PersonAffectedFamilyMember $member) => $member->setAttribute('sex', PersonSex::normalizeOrPreserve($member->sex))))
            ->values();
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
