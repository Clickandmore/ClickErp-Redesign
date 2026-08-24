<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Insurance extends HrModel
{
    use HasFactory;
    protected $table = 'hr_insurances';
    protected $fillable = ['title','type','value','emp_value','company_value','status'];

    public function employee() {
        return $this->belongsToMany(Employee::class, 'hr_employee_insurances', 'insurances_id', 'employee_id');
    }
}
