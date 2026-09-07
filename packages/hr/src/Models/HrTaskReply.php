<?php

namespace Nami\Hr\Models;



class HrTaskReply extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $table = 'hr_task_replies';
    protected $fillable = ['title', 'description', 'task_id','employee_id','file','branch_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function task()
    {
        return $this->belongsTo(HrTask::class,'task_id','id');
    }

}
