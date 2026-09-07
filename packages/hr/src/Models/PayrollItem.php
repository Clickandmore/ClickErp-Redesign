<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollItem extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $table = 'hr_payroll_items';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'maid_id',
        'employee_name',
        'nationality_name',
        'basic_salary',
        'work_days',
        'absence_days',
        'incentive',
        'other_additions',
        'gross_salary',
        'advance',
        'penalty',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'notes',
        'branch_id',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function maid()
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class, 'maid_id');
    }
}
