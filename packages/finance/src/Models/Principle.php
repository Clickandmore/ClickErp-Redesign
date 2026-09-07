<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use App\Models\auth\Admin;
use Nami\Finance\Enums\PerishedTypeEnum;

class Principle extends Finance
{
    use AssignsBranch, BranchScoped;

    protected $table = 'fn_principles';
    protected $fillable = [
        'branch_id',
        'name',
        'created_by_id',
        'parent_account_id',
        'description',
        'hypothetical_years',
        'perished_type',
        'principle_website_id',
        'image',
        'code',
        'purchase_date',
        'service_start_date',
        'employee_id',
        'purchase_value',
        'principle_value',
        'creditor_account_id',
        'account_id',
        'first_tax',
        'second_tax',
        'status',
        'registration_type',
    ];

    // protected $casts = [
    //     'perished_type' => PerishedTypeEnum::class
    // ];
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
    public function parent_account()
    {
        return $this->belongsTo(Account::class, 'parent_account_id');
    }

    public function perishedAccount()
    {
        return $this->belongsTo(Account::class, 'perished_account_id');
    }

    public function principle_website()
    {
        return $this->belongsTo(PrincipleWebsite::class, 'principle_website_id');
    }

    public function principlePerished()
    {
        return $this->hasOne(PrinciplePerished::class, 'principle_id');
    }

    public function files()
    {
        return $this->hasMany(PrincipleImage::class, 'principle_id');
    }

    public function depreciationPrinciple()
    {
        return $this->hasMany(DepreciationPrinciple::class, 'principle_id');
    }

    public function firstTax()
    {
        return $this->belongsTo(TaxSetting::class, 'first_tax');
    }

    public function secondTax()
    {
        return $this->belongsTo(TaxSetting::class, 'second_tax');
    }

//    public function employee()
//    {
//        return $this->belongsTo(Employee::class, 'employee_id');
//    }

    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by_id');
    }
}
