<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nami\Finance\Models\Bond;

use App\Models\Concerns\BranchScoped;

class FinancialRequest extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, SoftDeletes, BranchScoped;

    public const STATUS_PREPARING = 'preparing';
    public const STATUS_FOR_APPROVAL = 'for_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_REQUEST_CLOSING = 'request_closing';
    public const STATUS_RECEIPT_ATTACHED = 'receipt_attached';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    // Legacy aliases kept for reports and older integrations.
    public const STATUS_PENDING_REVIEW = self::STATUS_FOR_APPROVAL;
    public const STATUS_IN_PROGRESS = self::STATUS_APPROVED;

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_IMPORTANT = 'important';
    public const PRIORITY_CRITICAL = 'critical';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'request_date' => 'date',
        'paid_at' => 'datetime',
        'closed_at' => 'datetime',
        'amount' => 'decimal:2',
        'contract_cost' => 'decimal:2',
        'contract_salary' => 'decimal:2',
        'branch_id' => 'integer',
    ];

    public static function types(): array
    {
        return trans('financial_requests.types');
    }

    public function canChangeAmount(): bool
    {
        return ! $this->finance_bond_id && ! $this->paid_at && ! $this->closed_at
            && in_array($this->status, [self::STATUS_PREPARING, self::STATUS_FOR_APPROVAL, self::STATUS_REJECTED, self::STATUS_APPROVED], true);
    }

    public static function typeFields(): array
    {
        $fields = trans('financial_requests.fields');
        return [
            'musaned_payment' => array_intersect_key($fields, array_flip(['musaned_request_no', 'musaned_invoice_no'])),
            'visa_fee' => array_intersect_key($fields, array_flip(['visa_number', 'government_reference'])),
            'enjaz_fee' => array_intersect_key($fields, array_flip(['enjaz_number', 'authorization_no'])),
            'iqama_fee' => array_intersect_key($fields, array_flip(['iqama_number', 'period'])),
            'work_permit_fee' => array_intersect_key($fields, array_flip(['permit_number', 'period'])),
            'medical_insurance' => array_intersect_key($fields, array_flip(['policy_number', 'insurance_company'])),
            'salary_payment' => array_intersect_key($fields, array_flip(['salary_month', 'salary_days'])),
            'client_refund' => array_intersect_key($fields, array_flip(['refund_reason'])),
            'other_financial' => array_intersect_key($fields, array_flip(['subject', 'reference_no'])),
        ];
    }

    public static function typeFieldLabels(?string $type): array
    {
        if (! $type) {
            return [];
        }

        return self::typeFields()[$type] ?? [];
    }

    public static function labelForPayloadField(?string $type, string $field): string
    {
        return self::typeFieldLabels($type)[$field] ?? str_replace('_', ' ', $field);
    }

    public static function contractTypes(): array
    {
        return trans('financial_requests.contract_types');
    }

    public static function statuses(): array
    {
        return trans('financial_requests.statuses');
    }

    public static function priorities(): array
    {
        return trans('financial_requests.priorities');
    }

    public static function labelFromMap(array $labels, ?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '-';
        }

        return $labels[$value]
            ?? $labels[strtolower($value)]
            ?? str_replace('_', ' ', $value);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::labelFromMap(self::types(), $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::labelFromMap(self::statuses(), $this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::labelFromMap(self::priorities(), $this->priority);
    }

    public function getContractTypeLabelAttribute(): string
    {
        return self::labelFromMap(self::contractTypes(), $this->contract_type);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function attachments()
    {
        return $this->hasMany(FinancialRequestAttachment::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function financeBond()
    {
        return $this->belongsTo(Bond::class, 'finance_bond_id');
    }

    public function returnSettlement()
    {
        return $this->belongsTo(ContractReturnSettlement::class, 'return_settlement_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(FinancialRequestStatusLog::class)->oldest();
    }

    public function isCreatorStage(): bool
    {
        return in_array($this->status, [self::STATUS_PREPARING, self::STATUS_APPROVED, self::STATUS_REQUEST_CLOSING, self::STATUS_REJECTED], true);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        $contractBranchId = $this->contract_type === 'rental'
            ? $this->resolveBranchIdFromRentalContract($this->contract_id)
            : $this->resolveBranchIdFromContract($this->contract_id);

        return $contractBranchId
            ?? $this->resolveBranchIdFromUser($this->client_id)
            ?? $this->resolveBranchIdFromMaid($this->maid_id)
            ?? $this->resolveBranchIdFromAdmin($this->created_by_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
