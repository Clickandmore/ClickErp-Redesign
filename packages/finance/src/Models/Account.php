<?php

namespace Nami\Finance\Models;

use App\Models\Branch;
use App\Models\Concerns\AssignsBranch;

use App\Models\auth\Admin;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Enums\AccountTypesEnum;

class Account extends Finance
{
    // الفرع يُسجَّل على الحساب للتقارير، لكن بلا عزل قراءة تلقائي:
    // `code` فريد على مستوى الشجرة كلها، وعشرة مواضع تولّده من
    // `max('code')` — استعلام معزول ينتج كوداً مستعملاً في فرع آخر.
    // عزل أرصدة الفروع يتحقق من عزل القيود والسندات، لا من إخفاء الحسابات.
    use AssignsBranch;

    public const MUSANED_PARENT_CODES = [122, 156];

    protected $guarded =[];

    protected $table = 'fn_accounts';

    protected $casts = [
        'status'=> AccountStatusEnum::class,
        'type'=> AccountTypesEnum::class
    ];

    protected static function booted()
    {
        static::deleting(function (self $account) {
            if ($account->cashBoxes()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'account' => 'لا يمكن حذف حساب مرتبط بصندوق. راجع الصندوق من شاشة الصناديق أولاً.',
                ]);
            }
        });

        static::updating(function (self $account) {
            if ($account->isDirty('type') && $account->cashBoxes()->exists()
                && (int) $account->getAttributes()['type'] !== AccountTypesEnum::Box->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'type' => 'لا يمكن تغيير نوع حساب مرتبط بصندوق إلى نوع آخر.',
                ]);
            }
        });
    }

    /**
     * «النوع» كما يظهر في الدليل. إذا فُعّل العرض حسب التسلسل في إعدادات الدليل: المستوى 1 و3 اسمهما،
     * والمستوى 2 و4 وما بعده اسم الأب. لا يغيّر نوع الحساب الذي يعتمد عليه منطق البرنامج.
     */
    public function displayTypeLabel(): string
    {
        if (filled($this->type_label ?? null)) {
            return (string) $this->type_label;
        }
        if (!\Nami\Finance\Support\ChartSettings::typeDisplayByHierarchy()) {
            return $this->type?->lang() ?? '';
        }

        return in_array((int) $this->level, [1, 3], true) ? (string) $this->name : (string) ($this->parent->name ?? $this->name);
    }

    public function cashBoxes()
    {
        return $this->hasMany(Box::class, 'account_id');
    }

    public function scopeRegisteredPaymentAccount($query)
    {
        return $query->where(function ($query) {
            $query->where('type', '<>', AccountTypesEnum::Box->value)
                ->orWhereHas('cashBoxes');
        });
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id', 'id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'fn_account_branch', 'account_id', 'branch_id')
            ->withTimestamps();
    }

    public function scopeAssignableTo($query, ?int $branchId)
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->where(function ($query) use ($branchId) {
            $query->whereDoesntHave('branches')
                ->orWhereHas('branches', fn ($branches) => $branches->whereKey($branchId));
        });
    }

    public function constraintDetails()
    {
        return $this->hasMany(ConstraintDetails::class, 'account_id', 'id')
            ->whereHas('constraint');
    }

// recursive, loads all children
    public function childrenRecursive()
    {
        return $this->children()
            ->with(['childrenRecursive', 'constraintDetails'])
            ->orderBy('code');
    }

    // public function projects()
    // {
    //     return $this->hasMany(Project::class);
    // }

    public function getAllChildrenIds()
    {
        $child_ids = [];
        foreach ($this->children as $child) {
            $child_ids[] = $child->id;
            $child_ids = array_merge($child_ids, $child->getAllChildrenIds());
        }
        return $child_ids;
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id', 'id');
    }

    public function scopeUnderMusanedParent($query)
    {
        return $query->whereHas('parent', function ($parentQuery) {
            $parentQuery->whereIn('code', \Nami\Finance\Support\AccountCodeAliases::many(self::MUSANED_PARENT_CODES));
        });
    }

    public function scopeMusanedAccountCandidate($query)
    {
        return $query
            ->where('status', AccountStatusEnum::Debtor->value)
            ->where(function ($accountQuery) {
                $accountQuery->underMusanedParent()
                    ->orWhere(function ($namedAccountQuery) {
                        $namedAccountQuery
                            ->where('name', 'like', '%مساند%')
                            ->whereIn('type', [
                                AccountTypesEnum::Banks->value,
                                AccountTypesEnum::Bank->value,
                                AccountTypesEnum::Boxs->value,
                                AccountTypesEnum::Box->value,
                                AccountTypesEnum::BankName->value,
                            ]);
                    });
            });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
}
