<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Cms\Barangay;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TcissMasterlistRecord extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'affected_family_id',
        'dafac_record_id',
        'barangay_id',
        'evacuation_center_id',
        'household_head_full_name',
        'birthdate',
        'address',
        'source_reference',
        'source',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'verified_at' => 'datetime'];
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function dafacRecord(): BelongsTo
    {
        return $this->belongsTo(DafacRecord::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
