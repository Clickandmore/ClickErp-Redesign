<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    use HasFactory;
    protected $table = 'hr_employee_shift';
    protected $fillable = ['employee_id','shift_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class,'shift_id');
    }
}
