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

    protected $fillable = ['uuid', 'barangay_id', 'name', 'address', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function affectedFamilies(): HasMany
    {
        return $this->hasMany(AffectedFamily::class);
    }
}
