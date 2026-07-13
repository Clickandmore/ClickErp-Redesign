@if(@AdminGuardHasPerm(\App\PermKeys::USERS_EDIT))
    @include('admin.auth.admin._form', [
        'action' => route('admins.update', $obj->id),
        'method' => 'put',
        'obj' => $obj,
        'roles' => $roles ?? [],
        'branches' => $branches ?? collect(),
        'branchesEnabled' => $branchesEnabled ?? false,
        'admin_typeis' => $admin_typeis ?? [],
        'hasInvalidCurrentRole' => $hasInvalidCurrentRole ?? false,
        'standalone' => $standalone ?? false,
    ])
@else
    @include('components.no_permissions')
@endif
