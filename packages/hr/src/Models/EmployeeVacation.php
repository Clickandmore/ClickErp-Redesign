<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nami\Hr\Enums\EmployeeVacationStatus;

class EmployeeVacation extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_employee_vacations';
    protected $fillable = ['employee_id','vacation_id','vacation_year_id','vacation_file','return_file','value','from_date','return_date','to_date','days','reason','status'];

    protected $casts = [
        'status' => EmployeeVacationStatus::class
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function vacation()
    {
        return $this->belongsTo(Vacation::class, 'vacation_id');
    }
}
