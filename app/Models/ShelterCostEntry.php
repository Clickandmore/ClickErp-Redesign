<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Constraint;

/**
 * حركة في دفتر إيواء العاملة. المبلغ موجب يزيد رصيد أصل الإيواء، وسالب يُفرج عنه.
 */
class ShelterCostEntry extends Model
{
    public const KIND_CAPITALIZE_OFFICE_COST = 'capitalize_office_cost';
    public const KIND_LIVING_EXPENSE = 'living_expense';
    public const KIND_LIVING_EXPENSE_REVERSAL = 'living_expense_reversal';
    public const KIND_RELEASE_TO_TRIAL = 'release_to_trial';
    public const KIND_RETURN_FROM_TRIAL = 'return_from_trial';
    public const KIND_RELEASE_TO_DEPORTATION = 'release_to_deportation';
    public const KIND_RELEASE_TO_SALE = 'release_to_sale';
    public const KIND_WRITE_OFF = 'write_off';
    public const KIND_LEGACY_ADJUSTMENT = 'legacy_adjustment';

    public const KIND_AGENT_RECOVERY = 'agent_cost_recovery';
    public const KIND_AGENT_RECOVERY_REVERSAL = 'agent_cost_recovery_reversal';

    public const KIND_LABELS = [
        self::KIND_AGENT_RECOVERY => 'استرداد تكلفة الوكيل',
        self::KIND_AGENT_RECOVERY_REVERSAL => 'عكس استرداد تكلفة الوكيل',
        self::KIND_CAPITALIZE_OFFICE_COST => 'استلام عاملة (تكلفة المكتب الخارجي)',
        self::KIND_LIVING_EXPENSE => 'مصروف إعاشة',
        self::KIND_LIVING_EXPENSE_REVERSAL => 'عكس مصروف إعاشة',
        self::KIND_RELEASE_TO_TRIAL => 'خروج للتجربة (نقل خدمات)',
        self::KIND_RETURN_FROM_TRIAL => 'رجوع من التجربة',
        self::KIND_RELEASE_TO_DEPORTATION => 'ترحيل',
        self::KIND_RELEASE_TO_SALE => 'إعادة بيع من الإيواء',
        self::KIND_WRITE_OFF => 'تصفية رصيد',
        self::KIND_LEGACY_ADJUSTMENT => 'تسوية قيود سابقة',
    ];

    protected $fillable = [
        'office_account_id', 'supplier_constraint_id', 'asset_account_id',
        'maid_id',
        'contract_id',
        'kind',
        'amount',
        'constraint_id',
        'source_type',
        'source_id',
        'created_by_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function constraint()
    {
        return $this->belongsTo(Constraint::class, 'constraint_id');
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }
}
