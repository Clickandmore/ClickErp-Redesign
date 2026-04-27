<?php

namespace Nami\Hr\Models;


use Illuminate\Database\Eloquent\Model;
class EmployeeResponsible extends Model
{
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
