<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ingaz\User;
use App\Models\auth\Admin;

class SponsorshipTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'old_sponsor_id',
        'new_sponsor_id',
        'trial_days',
        'trial_start_date',
        'contract_value',
        'paid_amount',
        'remaining_amount',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'trial_started_at',
        'transferred_at',
        'failed_at',
        'failure_reason',
        'transferred_by',
        'failed_by',
        'rejection_reason'
    ];

    protected $casts = [
        'trial_start_date' => 'date',
        'approved_at' => 'datetime',
        'trial_started_at' => 'datetime',
        'transferred_at' => 'datetime',
        'failed_at' => 'datetime',
        'contract_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];

    // العلاقات
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function oldSponsor()
    {
        return $this->belongsTo(User::class, 'old_sponsor_id');
    }

    public function newSponsor()
    {
        return $this->belongsTo(User::class, 'new_sponsor_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function transferredBy()
    {
        return $this->belongsTo(Admin::class, 'transferred_by');
    }

    public function failedBy()
    {
        return $this->belongsTo(Admin::class, 'failed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeTransferred($query)
    {
        return $query->where('status', 'transferred');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'approved' => 'تمت الموافقة',
            'trial' => 'فترة التجربة',
            'transferred' => 'تم النقل',
            'failed' => 'فشل التجربة',
            'rejected' => 'مرفوض',
            'completed' => 'مكتمل'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-warning',
            'approved' => 'badge-info',
            'trial' => 'badge-primary',
            'transferred' => 'badge-success',
            'failed' => 'badge-danger',
            'rejected' => 'badge-danger',
            'completed' => 'badge-success'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }
}
