<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends HrModel
{
    use HasFactory;
    protected $table = 'hr_permissions';
    protected $fillable = ['title'];
}
