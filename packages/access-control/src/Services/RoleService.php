<?php

namespace Nami\AccessControl\Services;

use App\Enums\AdminTypeisEnum;
use App\Enums\IgnoreSystemPermission;
use App\Enums\ResourceFunctionEnum;
use App\Services\MainService;
use Illuminate\Support\Facades\Auth;
use Nami\AccessControl\Models\Permission;
use Nami\AccessControl\Models\Role;

class RoleService extends MainService
{
    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    public function storePermission(mixed $dataInsert)
    {
        $module = $dataInsert['permissions'];
        $permissions = Permission::whereIn('name', $module)->pluck('id')->toArray();

        $role = $this->model->create($dataInsert);
        $role->permissions()->sync($permissions);

        return $role;
    }

    public function updatePermission($id, $dataInsert): void
    {
        $role = $this->model->with('permissions')->find($id);
        $values = array_column(IgnoreSystemPermission::cases(), 'value');
        $module = $dataInsert['permissions'];

        if ($role->name == 'developer') {
            foreach ($values as $value) {
                foreach (array_column(ResourceFunctionEnum::cases(), 'value') as $perm) {
                    $module[] = $value . '.' . $perm;
                }
            }
        }

        $permissions = Permission::whereIn('name', $module)->pluck('id')->toArray();
        $role->update($dataInsert);
        $role->permissions()->sync($permissions);
    }

    public function getModels($adminService): array
    {
        if (Auth::guard('admin')->user()->admin_type == AdminTypeisEnum::Developer->value) {
            return \Config::get('laratrust_seeder.roles_structure.superadministrator');
        }

        $settingPlans = $adminService->getSettingPlans();
        $allPermissions = \Config::get('laratrust_seeder.roles_structure.superadministrator');
        $models = [];

        foreach ($allPermissions as $plan => $permissions) {
            if (in_array($plan, $settingPlans)) {
                $models[$plan] = $permissions;
            }
        }

        return $models;
    }

    public function getDataTable()
    {
        return $this->model->query()->where('name', '!=', 'developer')->latest();
    }
}
