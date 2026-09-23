<?php

namespace App\Models\Disaster;

use App\Models\Cms\Barangay;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Integration\PersonAffected;

class EvacuationCenter extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'cswdo_catalog_id', 'disaster_id', 'barangay_id', 'district', 'name', 'address', 'latitude', 'longitude', 'contact_person', 'assistant_coordinator', 'contact_number', 'capacity', 'description', 'date_opened', 'disaster_class_name', 'status', 'payout_availability', 'default_payout_date', 'default_payout_start_time', 'default_payout_end_time', 'created_by', 'updated_by', 'is_active', 'closed_at', 'closed_by', 'closure_notes'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'latitude' => 'float', 'longitude' => 'float', 'is_active' => 'boolean', 'date_opened' => 'date', 'default_payout_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function scopeCreatedCenters(Builder $query): Builder
    {
        return $query->whereNotNull('disaster_id');
    }

    public function disaster(): BelongsTo { return $this->belongsTo(Disaster::class); }
    public function closedBy(): BelongsTo { return $this->belongsTo(\App\Models\Auth\User::class, 'closed_by'); }

    public function affectedFamilies(): HasMany
    {
        return $this->hasMany(AffectedFamily::class);
    }

    public function assignments(): HasMany { return $this->hasMany(EvacuationCenterAssignment::class); }
    public function activeAssignments(): HasMany { return $this->assignments()->where('status', 'ACTIVE')->whereNull('unassigned_at'); }
    public function payoutSessions(): HasMany { return $this->hasMany(EvacuationCenterPayoutSession::class); }
    public function payoutReleases(): HasMany { return $this->hasMany(PayoutRelease::class); }
    public function personAffecteds(): HasMany { return $this->hasMany(PersonAffected::class); }
    public function unlinkedPersonAffecteds(): HasMany { return $this->personAffecteds()->whereNull('affected_family_id'); }
    public function documents(): MorphMany { return $this->morphMany(UploadedDocument::class, 'documentable'); }
}
