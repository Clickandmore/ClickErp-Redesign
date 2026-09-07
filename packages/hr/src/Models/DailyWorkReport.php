<?php

namespace Nami\Hr\Models;

class DailyWorkReport extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    // use HasFactory;
    protected $table = 'hr_daily_work_reports';
    protected $fillable = ['employee_id','title','description','date','file'];

    public function employee(){
        return $this->belongsTo(Employee::class,'employee_id','id');
    }

}
