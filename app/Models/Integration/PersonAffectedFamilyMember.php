<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonAffectedFamilyMember extends Model
{
    protected $fillable = ['control_number', 'full_name', 'relationship', 'age', 'sex', 'code', 'housing'];

    public function personAffected(): BelongsTo
    {
        return $this->belongsTo(PersonAffected::class);
    }
}
