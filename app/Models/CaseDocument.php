<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseDocument extends Model
{
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];

    private const PREVIEW_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'text/plain'];

    protected $fillable = ['name', 'path', 'mime_type', 'size'];

    protected $hidden = ['path'];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function canPreview(): bool
    {
        return in_array($this->mime_type, self::PREVIEW_TYPES, true);
    }
}
