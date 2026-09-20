<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ingaz\User;
use App\Models\ingaz\Maid;
use App\Models\auth\Admin;

use App\Models\Concerns\BranchScoped;

class TransferServiceContract extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    protected $fillable = [
        'external_source',
        'external_operation_number',
        'legacy_worker_number',
        'legacy_invoice_number',
        'trial_cost',
        'old_sponsor_compensation_type',
        'old_sponsor_compensation_amount',
        'worker_replacement_status',
        'profession_change_status',
        'legacy_status',
        'legacy_trial_status',
        'musaned_status',
        'conversion_status',
        'commission_amount',
        'refundable_tax_amount',
        'conversion_date',
        'arrival_warranty_status',
        'delivery_warranty_status',
        'source_created_by',
        'source_marketer',
        'source_created_at',
        'legacy_payload',
        'sponsorship_transfer_id',
        'contract_id',
        'branch_id',
        'old_sponsor_id',
        'new_sponsor_id',
        'maid_id',
        'contract_number',
        'contract_value',
        'paid_amount',
        'remaining_amount',
        'trial_days',
        'trial_start_date',
        'contract_start_date',
        'contract_end_date',
        'contract_terms',
        'special_conditions',
        'notes',
        'status',
        'sent_at',
        'signed_at',
        'activated_at',
        'completed_at',
        'cancelled_at',
        'created_by',
        'sent_by',
        'signed_by',
        'activated_by',
        'completed_by',
        'cancelled_by',
        'cancellation_reason'
    ];

    protected $casts = [
        'contract_terms' => 'array',
        'contract_value' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'trial_cost' => 'decimal:2',
        'old_sponsor_compensation_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'refundable_tax_amount' => 'decimal:2',
        'conversion_date' => 'date',
        'source_created_at' => 'datetime',
        'legacy_payload' => 'array',
        'trial_start_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    // العلاقات
    public function sponsorshipTransfer()
    {
        return $this->belongsTo(SponsorshipTransfer::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function oldSponsor()
    {
        return $this->belongsTo(User::class, 'old_sponsor_id')->withTrashed();
    }

    public function newSponsor()
    {
        return $this->belongsTo(User::class, 'new_sponsor_id')->withTrashed();
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(Admin::class, 'sent_by');
    }

    public function signedBy()
    {
        return $this->belongsTo(Admin::class, 'signed_by');
    }

    public function activatedBy()
    {
        return $this->belongsTo(Admin::class, 'activated_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(Admin::class, 'completed_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(Admin::class, 'cancelled_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statusTexts = [
            'draft' => 'مسودة',
            'sent' => 'تم الإرسال',
            'signed' => 'تم التوقيع',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى'
        ];

        return $statusTexts[$this->status] ?? 'غير محدد';
    }

    public function getContractDurationAttribute()
    {
        if ($this->contract_start_date && $this->contract_end_date) {
            return $this->contract_start_date->diffInDays($this->contract_end_date);
        }
        return null;
    }

    // Methods
    public function generateContractNumber()
    {
        $prefix = 'TSC'; // Transfer Service Contract
        $year = now()->year;
        $lastContract = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastContract ? (intval(substr($lastContract->contract_number, -4)) + 1) : 1;

        return $prefix . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function canBeSent()
    {
        return $this->status === 'draft';
    }

    public function canBeSigned()
    {
        return $this->status === 'sent';
    }

    public function canBeActivated()
    {
        return $this->status === 'signed';
    }

    public function canBeCompleted()
    {
        return $this->status === 'active';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'sent', 'signed', 'active']);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (!$contract->contract_number) {
                $contract->contract_number = $contract->generateContractNumber();
            }
            if (!$contract->created_by) {
                $contract->created_by = auth('admin')->id();
            }
        });
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromUser($this->new_sponsor_id)
            ?? $this->resolveBranchIdFromUser($this->old_sponsor_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
