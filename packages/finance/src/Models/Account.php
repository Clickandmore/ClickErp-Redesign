<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Enums\AccountTypesEnum;

class Account extends Finance
{
    public const MUSANED_PARENT_CODES = [122, 156];

    protected $guarded =[];

    protected $table = 'fn_accounts';

    protected $casts = [
        'status'=> AccountStatusEnum::class,
        'type'=> AccountTypesEnum::class
    ];

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id', 'id');
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
            $parentQuery->whereIn('code', self::MUSANED_PARENT_CODES);
        });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
}
