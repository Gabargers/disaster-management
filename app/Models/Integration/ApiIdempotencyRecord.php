<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class ApiIdempotencyRecord extends Model
{
    protected $fillable = [
        'client_id',
        'idempotency_key',
        'request_hash',
        'response_status',
        'response_body',
    ];

    protected function casts(): array
    {
        return ['response_body' => 'array'];
    }
}
