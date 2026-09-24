<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;

class ContractReturnSettlementLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'account_snapshot' => 'array',
        'metadata' => 'array',
        'reversed_at' => 'datetime',
    ];

    public function settlement() { return $this->belongsTo(ContractReturnSettlement::class, 'settlement_id'); }
    public function debitAccount() { return $this->belongsTo(Account::class, 'debit_account_id'); }
    public function creditAccount() { return $this->belongsTo(Account::class, 'credit_account_id'); }
    public function constraint() { return $this->belongsTo(Constraint::class); }
    public function reversalConstraint() { return $this->belongsTo(Constraint::class, 'reversal_constraint_id'); }
    public function bond() { return $this->belongsTo(Bond::class); }
    public function creditNote() { return $this->belongsTo(RecruitmentInvoiceCreditNote::class, 'credit_note_id'); }
    public function financialRequest() { return $this->belongsTo(FinancialRequest::class); }

    public function typeLabel(): string
    {
        return [
            'credit_note' => 'إشعار دائن',
            'client_deduction' => 'خصم على العميل',
            'client_payout' => 'صرف للعميل',
            'office_charge' => 'تحميل المكتب الخارجي',
            'shelter_cost' => 'تكلفة الإيواء',
            'marketer_commission' => 'استرجاع عمولة المسوق',
            'adjustment' => 'تسوية إضافية',
        ][$this->type] ?? $this->type;
    }
}
