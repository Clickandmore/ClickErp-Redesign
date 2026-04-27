<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFile extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hr_employee_files';

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
