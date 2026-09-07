<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Role;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles_view,admin', ['only' => ['index']]);
        $this->middleware('permission:roles_delete,admin', ['only' => ['delete']]);
    }

    public function index($id = null){
        $roles = $this->adminRolesQuery()
            ->withCount(['permissions', 'users'])
            ->orderByRaw("CASE WHEN name IN ('Administrator', 'super_admin') THEN 0 ELSE 1 END")
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->get();

        $adminsByRole = $this->getAdminsCountByRole();
        $roles->each(function (Role $role) use ($adminsByRole) {
            $role->setAttribute('linked_admins_count', max(
                (int) ($role->users_count ?? 0),
                (int) ($adminsByRole[$role->id] ?? 0)
            ));
            $role->setAttribute('is_protected', $this->isProtectedRole($role));
        });

        $editRole = null;
        if($id != null){
            $editRole = $this->adminRolesQuery()->find($id);
            if (!$editRole) {
                return redirect(route('dash.roles.index'))->with('error', 'لا يمكن تعديل دور غير تابع لحارس الإدارة.');
            }
        }

        return view('admin.permissions.roles.index',compact('roles','editRole'));
    }

    public function store(Request $request)
    {
        $id = $request->edit_id;
        $rules = [
            'name' => [
                'required',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'admin'))
                    ->ignore($id),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->with('error', __('admin.roles_name_validation'));
        }

        if ($id && !$this->adminRolesQuery()->whereKey($id)->exists()) {
            return redirect(route('dash.roles.index'))->with('error', 'لا يمكن تعديل دور غير تابع لحارس الإدارة.');
        }

        $role = Role::updateOrCreate(['id' => $id], [
            'name' => $request->name,
            'guard_name' => 'admin',
        ]);

        return redirect(route('dash.roles.index'))->with('success', __('admin.success'));
    }


    public function delete(Request $request){
        $role = $this->adminRolesQuery()
            ->withCount('users')
            ->find($request->id);

        if(!$role){
            return redirect()->back()->with('error', 'لا يمكن حذف دور غير تابع لحارس الإدارة.');
        }

        $linkedAdminsCount = max(
            (int) ($role->users_count ?? 0),
            (int) ($this->getAdminsCountByRole()[$role->id] ?? 0)
        );

        if ($this->isProtectedRole($role)) {
            return redirect()->back()->with('error', 'هذا دور أساسي ولا يمكن حذفه.');
        }

        if ($linkedAdminsCount > 0) {
            return redirect()->back()->with('error', 'لا يمكن حذف دور مرتبط بمشرفين حالياً.');
        }

        $role->delete();

        return redirect()->back()->with('msg', __('admin.success'));
    }

    private function adminRolesQuery()
    {
        return Role::query()->where('guard_name', 'admin');
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, ['Administrator', 'super_admin'], true);
    }

    private function getAdminsCountByRole(): Collection
    {
        return collect(DB::table('admins')
            ->select('role_id', DB::raw('count(*) as total'))
            ->whereNotNull('role_id')
            ->groupBy('role_id')
            ->pluck('total', 'role_id'));
    }

}
