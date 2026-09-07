<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ContractReturnRequest extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

    protected $fillable = [
        'contract_id',
        'branch_id',
        'user_id',
        'notes',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\ingaz\User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'processed_by');
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

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            default => $this->status
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">قيد الانتظار</span>',
            'approved' => '<span class="badge bg-success">تمت الموافقة</span>',
            'rejected' => '<span class="badge bg-danger">مرفوض</span>',
            default => '<span class="badge bg-secondary">' . $this->status . '</span>'
        };
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromAdmin($this->processed_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
