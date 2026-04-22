@php
    $isDeveloper = (int) (auth('admin')->user()->admin_type ?? 0) === \App\Enums\AdminTypeisEnum::Developer->value;
    $canViewAccessControlUsers = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::USERS_VIEW) || @AdminGuardHasPerm('admins-read');
    $canViewAccessControlRoles = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW) || @AdminGuardHasPerm('role-permissions-read');
    $canViewAccessControlPermissionKeys = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::DEVELOPERS_PERMS);
    $isAdminsRoute = request()->routeIs('admins.*') || request()->routeIs('packages.access-control.admins.*');
    $isRolesRoute =
        request()->routeIs('packages.access-control.roles.*') ||
        request()->routeIs('dash.roles.*') ||
        request()->routeIs('dash.perms.*');
    $isPermissionKeysRoute =
        request()->routeIs('dash.developers.perms.*') ||
        request()->routeIs('packages.access-control.role-permissions.*');
@endphp

@if ($canViewAccessControlUsers || $isAdminsRoute)
    <li>
        <a class="m-link {{ $isAdminsRoute ? 'active' : '' }}"
           href="{{ route('admins.index') }}">
            <i class="fas fa-users-cog"></i>
            <span class="ms-2">{{ __('access-control::sidebar.package_users') }}</span>
        </a>
    </li>
@endif

@if ($canViewAccessControlRoles || $isRolesRoute)
    <li>
        <a class="m-link {{ $isRolesRoute ? 'active' : '' }}"
           href="{{ route('packages.access-control.roles.index') }}">
            <i class="fas fa-user-shield"></i>
            <span class="ms-2">{{ __('access-control::sidebar.package_roles') }}</span>
        </a>
    </li>
@endif

@if ($canViewAccessControlPermissionKeys || $isPermissionKeysRoute)
    <li>
        <a class="m-link {{ $isPermissionKeysRoute ? 'active' : '' }}"
           href="{{ route('packages.access-control.role-permissions.index') }}">
            <i class="fas fa-key"></i>
            <span class="ms-2">{{ __('access-control::sidebar.package_permissions') }}</span>
        </a>
    </li>
@endif
