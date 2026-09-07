<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;


use App\Models\auth\Admin;

class Box extends Finance
{
    // الفرع يُسجَّل على الحساب للتقارير، لكن بلا عزل قراءة تلقائي:
    // `code` فريد على مستوى الشجرة كلها، وعشرة مواضع تولّده من
    // `max('code')` — استعلام معزول ينتج كوداً مستعملاً في فرع آخر.
    // عزل أرصدة الفروع يتحقق من عزل القيود والسندات، لا من إخفاء الحسابات.
    use AssignsBranch;

    protected $table ='fn_boxes';
    protected $fillable =[
        'branch_id','title','account_id', 'created_by_id'];


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
    public function account(){
        return $this->belongsTo(Account::class, 'account_id');
    }
}
