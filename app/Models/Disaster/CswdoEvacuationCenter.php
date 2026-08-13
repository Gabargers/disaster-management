<?php

namespace App\Models\Disaster;

use App\Models\Cms\Barangay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CswdoEvacuationCenter extends Model
{
    protected $table = 'cswdo_evacuation_center_catalog';

    protected $fillable = [
        'district', 'barangay_id', 'barangay_name', 'name', 'street',
        'coordinator', 'assistant_coordinator', 'capacity',
    ];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
