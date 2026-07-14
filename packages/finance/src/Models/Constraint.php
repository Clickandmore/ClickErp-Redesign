<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
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



    public function getShowIdAttribute()
    {
        switch ($this->type) {
            case ConstraintTypiesEnum::SarfBond:
                return $this->bond->id;
                break;
           case ConstraintTypiesEnum::QapdBond:
                return $this->bond->id;
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
