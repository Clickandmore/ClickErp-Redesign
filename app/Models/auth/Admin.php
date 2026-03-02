<?php

namespace App\Models\auth;

use App\Models\ingaz\ComplaintComment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\ingaz\Maid;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes;
    protected $table ='admins';
    // protected $guard = 'admin';
    protected $guarded = [];


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function maids()
    {
        return $this->hasMany(Maid::class);
    }
    public function complaintComments()
    {
        return $this->hasMany(ComplaintComment::class);
    }

}
