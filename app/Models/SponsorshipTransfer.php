<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ingaz\User;
use App\Models\auth\Admin;
use Nami\Finance\Models\Constraint;

class SponsorshipTransfer extends Model
{
    use AssignsBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'external_source',
        'external_operation_number',
        'contract_id',
        'branch_id',
        'old_sponsor_id',
        'new_sponsor_id',
        'trial_days',
        'trial_start_date',
        'contract_value',
        'paid_amount',
        'remaining_amount',
        'payment_type',
        'payment_status',
        'payment_receipt_path',
        'attachments',
        'payment_confirmed_at',
        'payment_confirmed_by',
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
        'failure_settlement_type',
        'failure_settlement_amount',
        'failure_clear_remaining_amount',
        'failure_client_iban',
        'failure_client_iban_attachment_path',
        'visa_fee_amount',
        'trial_start_constraint_id',
        'completion_constraint_id',
        'failure_constraint_id',
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
        'payment_confirmed_at' => 'datetime',
        'contract_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'failure_settlement_amount' => 'decimal:2',
        'failure_clear_remaining_amount' => 'decimal:2',
        'visa_fee_amount' => 'decimal:2',
        'attachments' => 'array',
        'branch_id' => 'integer',
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

    public function trialStartConstraint()
    {
        return $this->belongsTo(Constraint::class, 'trial_start_constraint_id');
    }

    public function completionConstraint()
    {
        return $this->belongsTo(Constraint::class, 'completion_constraint_id');
    }

    public function failureConstraint()
    {
        return $this->belongsTo(Constraint::class, 'failure_constraint_id');
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

    public function paymentConfirmedBy()
    {
        return $this->belongsTo(Admin::class, 'payment_confirmed_by');
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
        return $query->whereIn('status', ['trial', 'trial_started']);
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
            'waiting_contract_send' => 'بانتظار تسجيل استلام العاملة',
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

    public function getEffectiveTrialStartDateAttribute()
    {
        return $this->trial_started_at ?? $this->trial_start_date;
    }

    public function getTrialEndDateAttribute()
    {
        if (!$this->effective_trial_start_date || !$this->trial_days) {
            return null;
        }

        return $this->effective_trial_start_date->copy()->addDays((int) $this->trial_days);
    }

    public function getTrialDaysRemainingAttribute()
    {
        if (!$this->trial_end_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->trial_end_date->copy()->startOfDay(), false);
    }

    public function getPaymentTypeTextAttribute()
    {
        $types = [
            'cash' => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'mada' => 'مدى',
            'tabby' => 'تابي',
            'tamara' => 'تمارا',
            'pay_later' => 'الدفع لاحقاً',
            'other' => 'أخرى',
        ];

        return $types[$this->payment_type] ?? 'غير محدد';
    }

    public function getPaymentStatusTextAttribute()
    {
        $statuses = [
            'paid' => 'تم الدفع',
            'partial' => 'مدفوع جزئياً',
            'pending' => 'معلق',
            'later' => 'الدفع لاحقاً',
            'bank_pending' => 'بانتظار التحويل',
        ];

        return $statuses[$this->payment_status] ?? 'غير محدد';
    }

    public function getFailureSettlementTypeTextAttribute()
    {
        $types = [
            'refund' => 'استرجاع للعميل',
        ];

        return $types[$this->failure_settlement_type] ?? 'غير محدد';
    }

    // Methods
    public function canPrintContract()
    {
        return $this->status === 'waiting_contract_send';
    }

    public function canEditDetails(): bool
    {
        return in_array($this->status, ['pending', 'waiting_contract_send'], true);
    }

    public function canDeleteDetails(): bool
    {
        return in_array($this->status, ['pending', 'waiting_contract_send'], true);
    }

    public function canCreateContract()
    {
        return in_array($this->status, [
            'waiting_contract_send',
            'contract_sent',
            'contract_signed',
            'maid_delivered',
            'trial',
            'trial_started',
        ], true);
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
        return in_array($this->status, [
            'waiting_contract_send',
            'contract_sent',
            'contract_signed',
        ], true);
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
            'waiting_contract_send' => 'badge-info',
            'contract_sent' => 'badge-primary',
            'contract_signed' => 'badge-success',
            'maid_delivered' => 'badge-success',
            'approved' => 'badge-info',
            'trial' => 'badge-primary',
            'trial_started' => 'badge-primary',
            'transferred' => 'badge-success',
            'failed' => 'badge-danger',
            'rejected' => 'badge-danger',
            'completed' => 'badge-success'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromUser($this->new_sponsor_id)
            ?? $this->resolveBranchIdFromUser($this->old_sponsor_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
