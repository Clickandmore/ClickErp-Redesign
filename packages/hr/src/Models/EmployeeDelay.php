<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDelay extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_employee_delays';
    protected $fillable = ['month_year','value','employee_id','employee_attendances_id','sanction_id'];

    public function employees()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function employeeAttendances()
    {
        return $this->belongsTo(EmployeeAttendances::class,'employee_attendances_id');
    }

    public function sanctions()
    {
        return $this->belongsTo(Sanction::class,'sanction_id');
    }
}
