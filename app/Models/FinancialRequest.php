<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nami\Finance\Models\Bond;

class FinancialRequest extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_IMPORTANT = 'important';
    public const PRIORITY_CRITICAL = 'critical';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'request_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'contract_cost' => 'decimal:2',
        'contract_salary' => 'decimal:2',
    ];

    public static function types(): array
    {
        return [
            'musaned_payment' => 'سداد مساند',
            'visa_fee' => 'رسوم تأشيرة',
            'enjaz_fee' => 'رسوم إنجاز',
            'iqama_fee' => 'رسوم إقامة',
            'work_permit_fee' => 'رسوم رخصة عمل',
            'medical_insurance' => 'تأمين طبي',
            'salary_payment' => 'صرف راتب عاملة',
            'client_refund' => 'استرداد عميل',
            'other_financial' => 'طلب مالي آخر',
        ];
    }

    public static function typeFields(): array
    {
        return [
            'musaned_payment' => ['musaned_request_no' => 'رقم طلب مساند', 'musaned_invoice_no' => 'رقم فاتورة مساند'],
            'visa_fee' => ['visa_number' => 'رقم التأشيرة', 'government_reference' => 'رقم المرجع الحكومي'],
            'enjaz_fee' => ['enjaz_number' => 'رقم إنجاز', 'authorization_no' => 'رقم التفويض'],
            'iqama_fee' => ['iqama_number' => 'رقم الإقامة', 'period' => 'المدة'],
            'work_permit_fee' => ['permit_number' => 'رقم رخصة العمل', 'period' => 'المدة'],
            'medical_insurance' => ['policy_number' => 'رقم الوثيقة', 'insurance_company' => 'شركة التأمين'],
            'salary_payment' => ['salary_month' => 'شهر الراتب', 'salary_days' => 'عدد الأيام'],
            'client_refund' => ['refund_reason' => 'سبب الاسترداد', 'iban' => 'آيبان العميل'],
            'other_financial' => ['subject' => 'موضوع الطلب', 'reference_no' => 'رقم مرجعي'],
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
        return [
            'recruitment' => 'استقدام',
            'rental' => 'تأجير',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING_REVIEW => 'قيد المراجعة',
            self::STATUS_IN_PROGRESS => 'تحت الإجراء',
            self::STATUS_PAID => 'تم السداد',
            self::STATUS_REJECTED => 'مرفوض',
            self::STATUS_CLOSED => 'مقفلة',
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_NORMAL => 'عادي',
            self::PRIORITY_IMPORTANT => 'هام',
            self::PRIORITY_CRITICAL => 'شديد الأهمية',
        ];
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
        return $this->belongsTo(User::class, 'client_id');
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

    public function financeBond()
    {
        return $this->belongsTo(Bond::class, 'finance_bond_id');
    }
}
