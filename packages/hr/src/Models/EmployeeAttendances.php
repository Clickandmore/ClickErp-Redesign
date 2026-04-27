<?php

namespace Nami\Hr\Models;

use App\Models\Nami\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendances extends Model
{
    use HasFactory;
    protected $table = 'hr_employee_attendances';
    protected $fillable = ['date','type','attendance_time','leave_time','month_year'
    ,'deducted_from_salary','deducted_from_salary_vlaue','shift_id','created_by_id','employee_id','have_permission'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }
}
