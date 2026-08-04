<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class ApiAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'request_id',
        'event_reference',
        'control_number',
        'source_ip',
        'http_method',
        'route',
        'response_status',
        'processing_time_ms',
        'outcome',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
