<?php

namespace Nami\Hr\Models;


class EmployeeResponsible extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $guarded = [];
    protected $table = 'hr_employee_responsible';
    public function employees()
    {
        return $this->belongsTo(Employee::class, 'admin_id');
    }

    public function responsible()
    {
        return $this->belongsTo(Employee::class, 'responsible_employees_id');
    }
}
