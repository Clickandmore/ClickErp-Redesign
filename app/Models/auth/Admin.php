<?php

namespace App\Models\auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles;
    protected $table ='admins';
    // protected $guard = 'admin';
    protected $guarded = [];


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

}
