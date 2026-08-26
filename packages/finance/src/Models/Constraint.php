<?php

namespace Nami\Finance\Models;

use App\Models\RentalContractInvoice;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\ConstraintTypiesEnum;

class Constraint extends Finance
{
    use SoftDeletes;

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_SETTING_KEY = 'daily_constraints';
    public const APPROVE_PERMISSION = 'finance.constraints-approve';

    protected $table = 'fn_constraints';

    protected $fillable = [
        'type',
        'value',
        'date',
        'statement',
        'year_id',
        'code',
        'legacy_document_type',
        'legacy_document_number',
        'legacy_source_file',
        'legacy_source_row',
        'legacy_invoice_type',
        'legacy_invoice_number',
        'legacy_rental_order_number',
        'created_by_id',
        'approval_status',
        'approved_at',
        'approved_by_id',
    ];

    protected $casts = [
        'type' => ConstraintTypiesEnum::class,
        'approved_at' => 'datetime',
    ];

    private static ?bool $approvalColumnsExist = null;

    protected $with = ['admin'];

    protected static function booted(): void
    {
        static::creating(function (Constraint $constraint) {
            self::guardClosedFinancialPeriod($constraint);

            if (!self::hasApprovalColumns()) {
                return;
            }

            if (!$constraint->approval_status) {
                $constraint->approval_status = self::requiresApproval()
                    ? self::APPROVAL_PENDING
                    : self::APPROVAL_APPROVED;
            }

            if ($constraint->approval_status === self::APPROVAL_APPROVED && !$constraint->approved_at) {
                $constraint->approved_at = now();
                $constraint->approved_by_id = $constraint->approved_by_id ?? auth('admin')->id();
            }
        });

        static::updating(function (Constraint $constraint) {
            self::guardClosedFinancialPeriod($constraint);
        });

        static::deleting(function (Constraint $constraint) {
            self::guardClosedFinancialPeriod($constraint);
        });
    }

    private static function guardClosedFinancialPeriod(Constraint $constraint): void
    {
        $type = $constraint->getAttribute('type');
        $typeValue = $type instanceof ConstraintTypiesEnum ? $type->value : (int) $type;

        if (
            !$constraint->year_id
            || !$constraint->date
            || $typeValue === ConstraintTypiesEnum::FiscalYearClosing->value
        ) {
            return;
        }

        $closingConstraint = null;
        $closingOperation = null;

        if (Schema::hasTable('fn_fiscal_year_closures')) {
            $year = FnYear::query()->find($constraint->year_id, ['from_date', 'to_date']);
            if ($year) {
                $closingOperation = FiscalYearClosure::query()
                    ->whereNull('reversed_at')
                    ->whereBetween('closing_date', [$year->from_date, $year->to_date])
                    ->whereDate('closing_date', '>=', $constraint->date)
                    ->when($constraint->exists, fn ($query) => $query->where(function ($query) use ($constraint) {
                        $query->whereNull('constraint_id')->orWhere('constraint_id', '!=', $constraint->id);
                    }))
                    ->first(['id', 'operation_number', 'constraint_id', 'closing_date']);
            }
        } else {
            $closingConstraint = self::query()
                ->where('year_id', $constraint->year_id)
                ->where('type', ConstraintTypiesEnum::FiscalYearClosing->value)
                ->whereDate('date', '>=', $constraint->date)
                ->when($constraint->exists, fn ($query) => $query->where('id', '!=', $constraint->id))
                ->first(['id', 'code', 'date']);
        }

        if ($closingOperation || $closingConstraint) {
            $closedUntil = $closingOperation?->closing_date ?? $closingConstraint->date;
            $reference = $closingOperation
                ? 'عملية الإقفال رقم ' . ($closingOperation->operation_number ?? $closingOperation->id)
                : 'القيد رقم ' . ($closingConstraint->code ?? $closingConstraint->id);

            throw ValidationException::withMessages([
                'date' => 'الفترة المالية مقفلة حتى ' . $closedUntil . ' بواسطة ' . $reference . '.',
            ]);
        }
    }

    public static function requiresApproval(): bool
    {
        if (!self::hasApprovalColumns()) {
            return false;
        }

        return ConfirmSetting::query()
            ->where('title', self::APPROVAL_SETTING_KEY)
            ->latest('updated_at')
            ->value('value') === 'on';
    }

    public function scopeApproved(Builder $query): Builder
    {
        if (!self::hasApprovalColumns()) {
            return $query;
        }

        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        if (!self::hasApprovalColumns()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function constraintDetails()
    {
        return $this->hasMany(ConstraintDetails::class,'constraint_id','id');
    }

    public function created_by()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'fn_constraint_details')
            ->select('fn_accounts.id', 'fn_accounts.name');
    }

    public function files()
    {
        return $this->hasMany(ConstraintFile::class, 'constraint_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(ConstraintLog::class, 'constraint_id', 'id')->latest();
    }

    public function calculateTotal($constraintDetails, $type): string
    {
        $accounts = '';
        $total = 0;
        foreach ($constraintDetails as $con) {
            if ($con->type === $type) {
                $accounts .= $con->account->name . " <br> ";
                $total += $con->type === 'debtor' ? $con->debtor_value : $con->creditor_value;
            }
        }
        return $accounts . " بأجمالي " . ($total ?? 0);
    }


    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by_id');
    }

    public function approve(?int $adminId = null): bool
    {
        if (!self::hasApprovalColumns()) {
            return false;
        }

        return $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by_id' => $adminId ?? auth('admin')->id(),
        ]);
    }

    public static function hasApprovalColumns(): bool
    {
        if (self::$approvalColumnsExist !== null) {
            return self::$approvalColumnsExist;
        }

        try {
            self::$approvalColumnsExist = Schema::hasColumn('fn_constraints', 'approval_status')
                && Schema::hasColumn('fn_constraints', 'approved_at')
                && Schema::hasColumn('fn_constraints', 'approved_by_id');
        } catch (\Throwable $exception) {
            self::$approvalColumnsExist = false;
        }

        return self::$approvalColumnsExist;
    }

    public function accountsOfConstraint()
    {
        return $this->belongsToMany(Account::class, 'fn_constraint_details')
            ->select('fn_accounts.id');
    }

    public function bond()
    {
        return $this->hasOne(Bond::class, 'constraint_id', 'id');
    }
    public function transfer_entry()
    {
        return $this->hasOne(AccountTransfer::class, 'constraint_id', 'id');
    }

    public function rentalInvoice()
    {
        return $this->belongsTo(RentalContractInvoice::class, 'legacy_invoice_number', 'legacy_invoice_id');
    }

    public function recruitmentInvoice()
    {
        return $this->belongsTo(Invoice::class, 'legacy_invoice_number', 'legacy_invoice_number');
    }

    public function rentalInvoiceConstraint()
    {
        return $this->hasOne(RentalContractInvoice::class, 'invoice_constraint_id');
    }

    public function rentalReceiptInvoice()
    {
        return $this->hasOne(RentalContractInvoice::class, 'receipt_constraint_id');
    }

    public function arrivalRevenueInvoice()
    {
        return $this->hasOne(Invoice::class, 'arrival_revenue_constraint_id');
    }

    public function externalOfficeCostInvoice()
    {
        return $this->hasOne(Invoice::class, 'external_office_cost_constraint_id');
    }

    public function ingazDelegationCostInvoice()
    {
        return $this->hasOne(Invoice::class, 'ingaz_delegation_cost_constraint_id');
    }



    public function getShowIdAttribute()
    {
        switch ($this->type) {
            case ConstraintTypiesEnum::SarfBond:
                return $this->bond->id;
                break;
           case ConstraintTypiesEnum::QapdBond:
                return $this->bond->code;
                break;
           case ConstraintTypiesEnum::TransferEntry:
                return $this->transfer_entry->id;
                break;
            default:
                return $this->id;
                break;
        }
    }


    public function depreciationPrinciple()
    {
        return $this->hasOne(DepreciationPrinciple::class);
    }

}
