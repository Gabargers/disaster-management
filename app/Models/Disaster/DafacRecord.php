<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DafacRecord extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'affected_family_id', 'reference_number', 'interview_date', 'thumbmark_signature_path', 'interviewed_by', 'interviewed_by_name', 'attestation_confirmed', 'validated_by'];

    protected function casts(): array
    {
        return ['interview_date' => 'date', 'attestation_confirmed' => 'boolean'];
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

    public function documents()
    {
        return $this->morphMany(UploadedDocument::class, 'documentable');
    }
}
