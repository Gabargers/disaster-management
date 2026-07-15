<?php

namespace App\Models\Disaster;

use App\Enums\FamilyStatus;
use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AffectedFamily extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'disaster_id',
        'barangay_id',
        'evacuation_center_id',
        'household_head_surname',
        'household_head_given_name',
        'household_head_middle_name',
        'birthdate',
        'age',
        'occupation',
        'monthly_income',
        'contact_number',
        'complete_address',
        'house_ownership',
        'housing_condition',
        'health_condition',
        'status',
        'exact_household_hash',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'monthly_income' => 'decimal:2',
            'status' => FamilyStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AffectedFamily $family) {
            if ($family->birthdate && empty($family->age)) {
                $family->age = Carbon::parse($family->birthdate)->age;
            }

            $family->exact_household_hash = $family->buildHouseholdHash();
        });
    }

    public function getHouseholdHeadFullNameAttribute(): string
    {
        return trim(collect([
            $this->household_head_given_name,
            $this->household_head_middle_name,
            $this->household_head_surname,
        ])->filter()->implode(' '));
    }

    public function canMoveTo(FamilyStatus $status): bool
    {
        return $this->status instanceof FamilyStatus && $this->status->canTransitionTo($status);
    }

    public function buildHouseholdHash(): string
    {
        return hash('sha256', Str::lower(implode('|', [
            $this->disaster_id,
            $this->barangay_id,
            $this->household_head_surname,
            $this->household_head_given_name,
            $this->birthdate?->format('Y-m-d') ?: $this->birthdate,
            preg_replace('/\s+/', ' ', trim($this->complete_address ?? '')),
        ])));
    }

    public function disaster(): BelongsTo
    {
        return $this->belongsTo(Disaster::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function dafacRecord(): HasOne
    {
        return $this->hasOne(DafacRecord::class);
    }

    public function tcissMasterlistRecord(): HasOne { return $this->hasOne(TcissMasterlistRecord::class); }

    public function validationRecords(): HasMany
    {
        return $this->hasMany(ValidationRecord::class);
    }

    public function duplicateChecks(): HasMany
    {
        return $this->hasMany(DuplicateCheck::class);
    }

    public function assistanceRecords(): HasMany
    {
        return $this->hasMany(AssistanceRecord::class);
    }

    public function payoutReleases(): HasMany
    {
        return $this->hasMany(PayoutRelease::class);
    }

    public function workflowHistories(): HasMany { return $this->hasMany(WorkflowHistory::class); }
    public function payrollRecord(): HasOne { return $this->hasOne(PayrollRecord::class); }
    public function uploadedDocuments() { return $this->morphMany(UploadedDocument::class, 'documentable'); }
    public function auditLogs() { return $this->morphMany(AuditLog::class, 'auditable'); }

    public function postPayoutRequirement(): HasOne
    {
        return $this->hasOne(PostPayoutRequirement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
