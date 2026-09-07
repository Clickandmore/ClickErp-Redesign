<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeAllowance extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hr_employee_allowances';

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'hr_employee_allowances', 'allowances_id', 'employee_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }
    public function allowances()
    {
        return $this->belongsTo(Allowance::class, 'allowances_id');
    }

}
