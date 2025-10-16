<?php

namespace App\Services\Nami;
use App\Enums\auth\AdminTypeis;
use App\Enums\auth\AdminTypes;
use App\Models\Nami\Admin;
use App\Services\MainService;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

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
        //dd($data);
        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'phone' => $data['phone'],
            'admin_type' => $data['admin_type'],
            'sex_type' => $data['sex_type'],
            'role_id' => $data['role_id'],
            'sort' => $data['sort'] ?? 999,
            'password' => bcrypt($data['password'])
        ]);

        //set role for permissions
        if ($admin) {
            $role = Role::find($admin->role_id);
            if ($role) {
                $admin->syncRoles([$role->name]);
            }
        }
        //end set role

        return $admin;
    }

    public function updateAdmin($id, $data, $request)
    {
        $admin = $this->find($id);
        $admin->update($data);
        //set role for permissions
        if ($admin) {
            $role = Role::find($admin->role_id);
            if ($role) {
                $admin->syncRoles([$role->name]);
            }
        }
        //end set role
        return $admin;
    }

}
