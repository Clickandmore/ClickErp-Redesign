<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public $guarded = [];
    protected $guard_name = 'admin';
}
