<?php

namespace App\Http\Actions\auth;

use App\Models\auth\Admin;
use App\Enums\auth\AdminTypes;
use App\Http\Actions\MainAction;
use App\Support\SystemModuleManager;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;


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
        $role = $this->resolveAdminRole($data['role_id'] ?? null);
        $branchId = $this->resolveBranchId($data['branch_id'] ?? null);

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'whatsapp' => !empty($data['whatsapp']) ? $data['whatsapp'] : $data['phone'],
            'phone' => $data['phone'],
            'verification_phone' => !empty($data['verification_phone']) ? $data['verification_phone'] : $data['phone'],
            'identity_number' => $data['identity_number'] ?? null,
            'identity_expiry_date' => $data['identity_expiry_date'] ?? null,
            'admin_type' => $data['admin_type'],
            'sex_type' => $data['sex_type'],
            'role_id' => $role?->id,
            'sort' => $data['sort'] ?? 999,
            'status' => $data['status'] ?? 'active',
            'note' => $data['note'] ?? null,
            'password' => bcrypt($data['password'])
        ];

        if ($this->branchesFeatureAvailable()) {
            $payload['branch_id'] = $branchId;
        }

        $admin = Admin::create($payload);

        if ($admin && $role) {
            $admin->syncRoles([$role]);
        }

        return $admin;
    }

    public function updateAdmin($id, $data, $request)
    {
        $admin = $this->find($id);
        $role = $this->resolveAdminRole($data['role_id'] ?? null);
        $data['whatsapp'] = !empty($data['whatsapp']) ? $data['whatsapp'] : $data['phone'];
        $data['verification_phone'] = !empty($data['verification_phone']) ? $data['verification_phone'] : $data['phone'];
        $data['role_id'] = $role?->id;
        if ($this->branchesFeatureAvailable()) {
            $data['branch_id'] = $this->resolveBranchId($data['branch_id'] ?? null);
        } else {
            unset($data['branch_id']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
            $data['remember_token'] = null;
            $data['current_session_id'] = 'terminated:' . time() . ':' . $admin->id;
        }
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

    private function resolveBranchId($branchId): ?int
    {
        if (!$this->branchesFeatureAvailable() || empty($branchId)) {
            return null;
        }

        return (int) $branchId;
    }

    private function branchesFeatureAvailable(): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasColumn('admins', 'branch_id');
    }

}
