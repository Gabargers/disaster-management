<?php

namespace App\Models\Disaster;

use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use App\Support\MemberRemark;

class FamilyMember extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'affected_family_id',
        'name',
        'birthdate',
        'age',
        'relationship_to_head',
        'sex',
        'occupation',
        'monthly_income',
        'health_condition',
        'remarks_codes',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'monthly_income' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (FamilyMember $member) {
            if ($member->birthdate && empty($member->age)) {
                $member->age = Carbon::parse($member->birthdate)->age;
            }
        });
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function getRemarksLabelAttribute(): string
    {
        return MemberRemark::label($this->remarks_codes);
    }
}
