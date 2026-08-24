<?php

namespace Nami\Hr\Models;

use Nami\Hr\Enums\AdvanceDeliveryMethod;
use Nami\Hr\Enums\AdvanceMethod;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advance extends HrModel
{
    use HasFactory;
    protected $table = 'hr_advances';
    protected $fillable = ['employee_id','value','file','date','notes','from_date','to_date','payment_method','delivery_method'];

    protected $casts = [
        'payment_method' => AdvanceMethod::class,
        'delivery_method' => AdvanceDeliveryMethod::class
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function advanceValues()
    {
        return $this->hasMany(AdvanceValue::class,'advance_id');
    }
    public function advanceValuePaid()
    {
        return $this->hasMany(AdvanceValue::class,'advance_id')->where('status','paid');
    }
}
