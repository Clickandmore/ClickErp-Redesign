<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrTask extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_tasks';
    protected $fillable = ['title', 'description', 'end_date','end_time','file','status','branch_id'];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'hr_employee_tasks', 'task_id', 'employee_id');
    }


    public function replies(): HasMany
    {
        return $this->hasMany(HrTaskReply::class, 'task_id');
    }

}
