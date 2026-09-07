<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use \App\Models\Concerns\AuditsChanges;

    public function syncPermissions(...$permissions)
    {
        return \App\Support\Audit\PermissionSync::run($this, fn () => parent::syncPermissions(...$permissions));
    }

    public $guarded = [];
    protected $guard_name = 'admin';
}
