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
        'contract_sent_at',
        'contract_signed_at',
        'maid_delivered_at',
        'contract_sent_by',
        'contract_signed_by',
        'maid_delivered_by',
        'trial_started_at',
        'transferred_at',
        'failed_at',
        'failure_reason',
        'transferred_by',
        'failed_by',
        'signature',
        'company_signature',
        'rejection_reason'
    ];

    protected $casts = [
        'trial_start_date' => 'date',
        'approved_at' => 'datetime',
        'contract_sent_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'maid_delivered_at' => 'datetime',
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

    public function contractSentBy()
    {
        return $this->belongsTo(Admin::class, 'contract_sent_by');
    }

    public function contractSignedBy()
    {
        return $this->belongsTo(Admin::class, 'contract_signed_by');
    }

    public function maidDeliveredBy()
    {
        return $this->belongsTo(Admin::class, 'maid_delivered_by');
    }

    public function transferServiceContract()
    {
        return $this->hasOne(\App\Models\TransferServiceContract::class);
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

    public function scopeWaitingContractSend($query)
    {
        return $query->where('status', 'waiting_contract_send');
    }

    public function scopeContractSent($query)
    {
        return $query->where('status', 'contract_sent');
    }

    public function scopeContractSigned($query)
    {
        return $query->where('status', 'contract_signed');
    }

    public function scopeMaidDelivered($query)
    {
        return $query->where('status', 'maid_delivered');
    }

    public function scopeTrialStarted($query)
    {
        return $query->where('status', 'trial_started');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statusTexts = [
            'pending' => 'في الانتظار',
            'waiting_contract_send' => 'انتظار إرسال العقد',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'trial_started' => 'بدأت التجربة',
            'approved' => 'موافق عليه',
            'trial' => 'في التجربة',
            'transferred' => 'تم النقل',
            'failed' => 'فشل',
            'rejected' => 'مرفوض',
            'completed' => 'مكتمل'
        ];

        return $statusTexts[$this->status] ?? 'غير محدد';
    }

    // Methods
    public function canPrintContract()
    {
        return $this->status === 'waiting_contract_send';
    }

    public function canSendContract()
    {
        return $this->status === 'waiting_contract_send';
    }

    public function canSignContract()
    {
        return $this->status === 'contract_sent';
    }

    public function canDeliverMaid()
    {
        return $this->status === 'contract_signed';
    }

    public function canStartTrial()
    {
        return $this->status === 'maid_delivered';
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
