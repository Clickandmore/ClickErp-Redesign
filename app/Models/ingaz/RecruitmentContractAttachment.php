<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\Contract;
use App\Services\ManagedFileStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentContractAttachment extends Model
{
    use AssignsBranch, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function uploader()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return (string) app(ManagedFileStorage::class)->url($this->path);
    }

    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->uploaded_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
