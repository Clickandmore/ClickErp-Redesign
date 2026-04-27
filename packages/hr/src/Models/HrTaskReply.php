<?php

namespace Nami\Hr\Models;


use Illuminate\Database\Eloquent\Model;

class HrTaskReply extends Model
{
    protected $table = 'hr_task_replies';
    protected $fillable = ['title', 'description', 'task_id','employee_id','file'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function task()
    {
        return $this->belongsTo(HrTask::class,'task_id','id');
    }

}
