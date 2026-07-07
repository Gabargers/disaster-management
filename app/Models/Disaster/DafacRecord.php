<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DafacRecord extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'affected_family_id', 'interview_date', 'thumbmark_signature_path', 'interviewed_by', 'validated_by'];

    protected function casts(): array
    {
        return ['interview_date' => 'date'];
    }

    public function affectedFamily(): BelongsTo
    {
        return $this->belongsTo(AffectedFamily::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewed_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
