<?php

namespace App\Models\Disaster;

use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disaster extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'name', 'type', 'incident_date', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['incident_date' => 'date', 'is_active' => 'boolean'];
    }

    public function affectedFamilies(): HasMany
    {
        return $this->hasMany(AffectedFamily::class);
    }

    public function payoutSchedules(): HasMany
    {
        return $this->hasMany(PayoutSchedule::class);
    }
}
