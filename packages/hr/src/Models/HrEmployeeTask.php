<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeTask extends Model
{
    use HasFactory;
    protected $table = 'hr_employee_tasks';
    protected $fillable = ['task_id', 'employee_id', 'status'];

    public $timestamps = true;

    public function task()
    {
        return $this->belongsTo(HrTask::class,'task_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id');
    }
}
