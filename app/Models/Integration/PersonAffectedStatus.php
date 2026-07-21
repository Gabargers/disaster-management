<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonAffectedStatus extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['person_affected_id', 'status', 'date_tagged'];

    protected function casts(): array
    {
        return ['date_tagged' => 'immutable_datetime'];
    }

    public function personAffected(): BelongsTo
    {
        return $this->belongsTo(PersonAffected::class);
    }
}
