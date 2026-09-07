<?php

namespace App\Models\Nami;
use App\Enums\auth\AdminTypes;
use App\Models\Branch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;
    use HasRoles { syncPermissions as protected syncPermissionsWithoutAudit; }

    public function syncPermissions(...$permissions)
    {
        return \App\Support\Audit\PermissionSync::run($this, fn () => $this->syncPermissionsWithoutAudit(...$permissions));
    }

    protected $table ='admins';
    // protected $guard = 'admin';
    protected $guarded = [];


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

}
