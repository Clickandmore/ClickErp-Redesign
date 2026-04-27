<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminEmployee extends Model
{
    use HasFactory;
    protected $table = 'admins_employees';
    protected $fillable = ['admin_id','employee_id','department_id'];
}
