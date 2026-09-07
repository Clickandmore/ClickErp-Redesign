<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeVacationBalance extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_employee_vacation_balance';
    protected $fillable = ['employee_id','vacation_id','vacation_year_id','total_days','from_date','token_days','remain_days'];


    public function vacation()
    {
        return $this->belongsTo(Vacation::class,'vacation_id');
    }
}
