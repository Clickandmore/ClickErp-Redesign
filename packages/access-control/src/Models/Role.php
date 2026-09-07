<?php

namespace Nami\AccessControl\Models;

use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    use \App\Models\Concerns\AuditsChanges;

    public $guarded = [];
}
