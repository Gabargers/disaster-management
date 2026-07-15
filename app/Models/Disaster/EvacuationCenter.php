<?php

namespace App\Models\Disaster;

use App\Models\Cms\Barangay;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvacuationCenter extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'disaster_id', 'barangay_id', 'name', 'address', 'contact_person', 'contact_number', 'capacity', 'description', 'status', 'payout_availability', 'default_payout_date', 'default_payout_start_time', 'default_payout_end_time', 'created_by', 'updated_by', 'is_active'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean', 'default_payout_date' => 'date'];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function disaster(): BelongsTo { return $this->belongsTo(Disaster::class); }

    public function affectedFamilies(): HasMany
    {
        return $this->hasMany(AffectedFamily::class);
    }

    public function assignments(): HasMany { return $this->hasMany(EvacuationCenterAssignment::class); }
    public function activeAssignments(): HasMany { return $this->assignments()->where('status', 'ACTIVE')->whereNull('unassigned_at'); }
    public function payoutSessions(): HasMany { return $this->hasMany(EvacuationCenterPayoutSession::class); }
    public function payoutReleases(): HasMany { return $this->hasMany(PayoutRelease::class); }
}
