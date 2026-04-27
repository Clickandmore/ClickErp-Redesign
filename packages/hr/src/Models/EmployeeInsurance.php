<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInsurance extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hr_employee_insurances';

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'hr_employee_insurances', 'insurances_id', 'employee_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class, 'insurances_id');
    }
}
