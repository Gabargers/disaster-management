<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutSchedule extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'disaster_id', 'title', 'scheduled_date', 'venue', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date'];
    }

    public function disaster(): BelongsTo
    {
        return $this->belongsTo(Disaster::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(PayoutRelease::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
