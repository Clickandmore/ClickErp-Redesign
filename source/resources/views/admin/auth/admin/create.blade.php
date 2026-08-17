@if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))
    @include('admin.auth.admin._form', [
        'action' => route('admins.store'),
        'method' => 'post',
        'obj' => null,
        'roles' => $roles ?? [],
        'branches' => $branches ?? collect(),
        'branchesEnabled' => $branchesEnabled ?? false,
        'admin_typeis' => $admin_typeis ?? [],
        'hasInvalidCurrentRole' => false,
    ])
@else
    @include('components.no_permissions')
@endif
