<?php

namespace App\Services\Nami;
use App\Enums\auth\AdminTypeis;
use App\Enums\auth\AdminTypes;
use App\Models\Nami\Admin;
use App\Services\MainService;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class AdminService extends MainService
{
    public function __construct()
    {
        $this->model = new \App\Models\auth\Admin();
    }

    public function getAdminTypies()
    {
        return AdminTypes::cases();
    }

    public function storeAdmin($data, $request)
    {
        $role = $this->resolveAdminRole($data['role_id'] ?? null);

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'phone' => $data['phone'],
            'admin_type' => $data['admin_type'],
            'sex_type' => $data['sex_type'],
            'role_id' => $role?->id,
            'sort' => $data['sort'] ?? 999,
            'password' => bcrypt($data['password'])
        ]);

        if ($admin && $role) {
            $admin->syncRoles([$role]);
        }

        return $admin;
    }

    public function updateAdmin($id, $data, $request)
    {
        $admin = $this->find($id);
        $role = $this->resolveAdminRole($data['role_id'] ?? null);
        $data['role_id'] = $role?->id;
        $admin->update($data);
        $admin->syncRoles($role ? [$role] : []);
        return $admin;
    }

    private function resolveAdminRole($roleId): ?Role
    {
        if (empty($roleId)) {
            return null;
        }

        return Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'admin')
            ->first();
    }

}
