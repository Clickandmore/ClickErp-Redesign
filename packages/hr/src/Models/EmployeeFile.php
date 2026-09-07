<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeFile extends HrModel
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hr_employee_files';

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
