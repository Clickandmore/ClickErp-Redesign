<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;

use App\Models\auth\Admin;

class Supplier extends Finance
{
    // الفرع يُسجَّل على الحساب للتقارير، لكن بلا عزل قراءة تلقائي:
    // `code` فريد على مستوى الشجرة كلها، وعشرة مواضع تولّده من
    // `max('code')` — استعلام معزول ينتج كوداً مستعملاً في فرع آخر.
    // عزل أرصدة الفروع يتحقق من عزل القيود والسندات، لا من إخفاء الحسابات.
    use AssignsBranch;

    protected $guarded =[];

    protected $table = 'fn_suppliers';

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'address',
        'phone',
        'email',
        'tax_number',
        'tax_image',
        'commercial_register_number',
        'commercial_register_image',
        'days_number', 'exceeding_minimum_cash_limit','exceeding_minimum_days_limit',
        'credit_limit',
        'account_id',
        'created_by_id',
        'national_address',
        'supplier_type_id'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(SupplierFile::class, 'supplier_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function supplierType()
    {
        return $this->belongsTo(SupplierType::class, 'supplier_type_id');
    }
}
