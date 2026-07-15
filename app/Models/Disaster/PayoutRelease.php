<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRelease extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'payout_schedule_id',
        'affected_family_id',
        'assistance_record_id',
        'status',
        'release_photo_path',
        'photo_override',
        'override_reason',
        'released_at',
        'released_by',
        'evacuation_center_id', 'payout_session_id', 'assistance_kind', 'quantity', 'amount', 'provider',
        'payout_photo_path', 'photo_caption', 'photo_taken_at', 'photo_uploaded_by', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return ['photo_override' => 'boolean', 'released_at' => 'datetime', 'photo_taken_at' => 'datetime', 'amount' => 'decimal:2', 'quantity' => 'decimal:2'];
    }

    public function canBeReleased(): bool
    {
        return $this->status === 'Scheduled' && ($this->release_photo_path || $this->photo_override);
    }

    public function payoutSchedule(): BelongsTo
    {
        return $this->belongsTo(PayoutSchedule::class);
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function assistanceRecord(): BelongsTo
    {
        return $this->belongsTo(AssistanceRecord::class);
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function center(): BelongsTo { return $this->belongsTo(EvacuationCenter::class, 'evacuation_center_id'); }
    public function payoutSession(): BelongsTo { return $this->belongsTo(EvacuationCenterPayoutSession::class, 'payout_session_id'); }
}
