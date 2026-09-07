<?php

namespace Nami\AccessControl\Models;

use Laratrust\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    use \App\Models\Concerns\AuditsChanges;

    public $guarded = [];
}
