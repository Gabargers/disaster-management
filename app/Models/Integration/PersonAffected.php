<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonAffected extends Model
{
    protected $fillable = ['control_number'];

    public function statuses(): HasMany
    {
        return $this->hasMany(PersonAffectedStatus::class);
    }
}
