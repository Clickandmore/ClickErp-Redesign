<?php

namespace Nami\Hr\Models;

use Nami\Hr\Enums\AllowanceTypeis;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSalary extends HrModel
{
    use HasFactory;
    protected $table = 'hr_employee_salary';
    protected $fillable = [
        'month_year',
        'sanctions',
        'employee_id',
        'allowances',
        'Insurances',
        'tax',
        'delays',
        'attandance_days',
        'final',
        'month_days',
        'absence_days',
        'basic_salary',
        'incentive',
        'other_additions',
        'gross_salary',
        'advance',
        'penalty',
        'other_deductions',
        'total_deductions',
        'notes',
    ];

    public function employees()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
