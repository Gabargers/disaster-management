<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PostPayoutRequirement extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'affected_family_id',
        'bfp_certificate_status',
        'barangay_certification_status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function isComplete(): bool
    {
        return $this->bfp_certificate_status === 'Verified'
            && $this->barangay_certification_status === 'Verified';
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(UploadedDocument::class, 'documentable');
    }
}
