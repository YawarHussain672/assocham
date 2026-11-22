<?php

namespace App\Models;

use App\Support\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'file_path', 'disk', 'original_name', 'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    /**
     * Resolves the download/view URL for this attachment across S3 or local public storage.
     */
    public function getUrlAttribute(): string
    {
        return StorageHelper::url($this->file_path, $this->disk ?: 'public');
    }

    public function isImage(): bool
    {
        $ext = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
        return in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'], true);
    }
}
