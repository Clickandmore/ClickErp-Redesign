<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Services\ManagedFileStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ClientAttachment extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

    protected $fillable = [
        'user_id',
        'branch_id',
        'uploaded_by',
        'title',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
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
        return app(ManagedFileStorage::class)->exists($this->path);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromAdmin($this->uploaded_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
