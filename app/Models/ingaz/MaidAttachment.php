<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MaidAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'maid_id',
        'uploaded_by',
        'title',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function uploader()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function existsOnDisk(): bool
    {
        return $this->path && Storage::disk('public')->exists($this->path);
    }
}
