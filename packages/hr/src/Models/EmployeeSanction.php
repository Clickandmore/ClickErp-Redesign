<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSanction extends Model
{
    use HasFactory;
    protected $table = 'hr_employee_sanctions';
    protected $fillable = ['employee_id','sanction_id','value','date','with_salary','reason','status', 'file'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function sanction()
    {
        return $this->belongsTo(Sanction::class, 'sanction_id');
    }
}
