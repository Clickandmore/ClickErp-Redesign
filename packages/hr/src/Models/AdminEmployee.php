<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminEmployee extends HrModel
{
    use HasFactory;
    protected $table = 'admins_employees';
    protected $fillable = ['admin_id','employee_id','department_id'];
}
