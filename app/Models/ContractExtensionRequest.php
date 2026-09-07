<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ContractExtensionRequest extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, BranchScoped;

    protected $fillable = [
        'contract_id',
        'branch_id',
        'extension_years',
        'reason',
        'status',
        'admin_notes',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'extension_years' => 'integer',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'requested_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'processed_by');
    }

    public function notes()
    {
        return $this->hasMany(ContractExtensionRequestNote::class, 'extension_request_id')->latest();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending'  => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            default    => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'  => '<span class="badge bg-warning text-dark">قيد الانتظار</span>',
            'approved' => '<span class="badge bg-success">تمت الموافقة</span>',
            'rejected' => '<span class="badge bg-danger">مرفوض</span>',
            default    => '<span class="badge bg-secondary">' . e($this->status) . '</span>',
        };
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->requested_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
