<?php

namespace Nami\AccessControl\Services;

use App\Enums\AdminTypeisEnum;
use App\Models\Nami\Admin;
use App\Services\MainService;
use App\Services\Nami\SettingService;
use Illuminate\Support\Facades\Auth;
use Nami\AccessControl\Models\Permission;
use Nami\AccessControl\Models\Role;

class AdminService extends MainService
{
    public function __construct(Admin $model, protected SettingService $settingService)
    {
        $this->model = $model;
        $this->fileFolder = 'images/admin/';
        $this->files = ['image'];
    }

    public function getSettingPlans()
    {
        $setting = $this->settingService->getSetting();

        return $setting?->settingPlans?->pluck('module_name')->toArray() ?? [];
    }

    public function getAuthUser()
    {
        return Auth::guard('admin')->user();
    }

    public function getAdminsDataTable()
    {
        return $this->model->query()
            ->with('roles')
            ->where('admin_type', '!=', AdminTypeisEnum::Developer->value)
            ->latest();
    }

    public function storeAdmin($data, $permissions)
    {
        $data['admin_type'] = AdminTypeisEnum::GlobalManger->value;
        $admin = $this->store($data);

        [$role, $permissionIds] = $this->getRoleAndPermissions($data);

        $admin->addRole($role);
        $role->permissions()->sync($permissionIds);
        $admin->syncPermissions($permissionIds);
        $module = [
            'admin_id' => $admin->id,
            'module' => $data['module'],
        ];

        $admin->modules()->create($module);

        return $admin;
    }

    protected function getRoleAndPermissions($data)
    {
        if (isset($data['role_id']) && $data['role_id'] != 'new') {
            $role = Role::findOrFail($data['role_id']);
            $permissionIds = $role->permissions->pluck('id')->toArray();
        } else {
            $role = $this->createRole($data['name']);
            $permissionIds = $this->getPermissionIds($data['permissions']);
        }

        return [$role, $permissionIds];
    }

    protected function createRole($name)
    {
        return Role::create([
            'name' => $name,
            'display_name' => $name,
        ]);
    }

    protected function getPermissionIds($permissions)
    {
        return Permission::whereIn('name', $permissions)->pluck('id')->toArray();
    }

    public function updateAdmin($id, $data, $permissions)
    {
        $admin = $this->find($id);
        [$role, $permissionIds] = $this->getRoleAndPermissions($data);
        $this->removeCurrentRoles($admin);

        $admin->addRole($role);
        $admin->syncPermissions($permissionIds);
        $role->permissions()->sync($permissionIds);
        $module = [
            'admin_id' => $id,
            'module' => $data['module'],
        ];

        $admin->modules()->updateOrCreate([
            'admin_id' => $id,
        ], $module);
        $admin->modules()->update($module);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $this->update($id, $data);
    }

    protected function removeCurrentRoles($admin)
    {
        $currentRoles = $admin->roles->pluck('id')->toArray();
        $admin->removeRoles($currentRoles);
    }

    public function getRoles()
    {
        return Role::all();
    }
}
