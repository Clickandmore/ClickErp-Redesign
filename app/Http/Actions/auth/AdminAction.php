<?php

namespace App\Http\Actions\auth;

use App\Models\auth\Admin;
use App\Enums\auth\AdminTypes;
use App\Http\Actions\MainAction;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;


class AdminAction extends MainAction
{

    public function __construct()
    {
        $this->model = new Admin();
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
            'status' => $data['status'] ?? 'active',
            'note' => $data['note'] ?? null,
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
