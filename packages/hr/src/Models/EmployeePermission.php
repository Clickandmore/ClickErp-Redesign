<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeePermission extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = 'hr_employee_permissions';
    protected $fillable = ['employee_id','permission_id','date','from_time','to_time','time','reason','status','file'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
