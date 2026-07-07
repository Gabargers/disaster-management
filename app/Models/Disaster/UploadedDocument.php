<?php

namespace App\Models\Disaster;

use App\Models\Auth\User;
use App\Models\Disaster\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UploadedDocument extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'documentable_type', 'documentable_id', 'document_type', 'file_path', 'original_name', 'mime_type', 'file_size', 'uploaded_by'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
