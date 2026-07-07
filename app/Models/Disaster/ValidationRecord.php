<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ValidationRecord extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'affected_family_id',
        'validated_house_ownership',
        'validated_housing_condition',
        'notes',
        'status',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime'];
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(UploadedDocument::class, 'documentable');
    }
}
