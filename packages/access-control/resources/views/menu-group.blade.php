@php
    $isDeveloper = (int) (auth('admin')->user()->admin_type ?? 0) === \App\Enums\AdminTypeisEnum::Developer->value;
    $canViewAccessControlUsers = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::USERS_VIEW) || @AdminGuardHasPerm('admins-read');
    $canViewAccessControlRoles = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW) || @AdminGuardHasPerm('role-permissions-read');
    $canViewAccessControlActivity =
        $isDeveloper ||
        @AdminGuardHasAnyPerm([
            \App\NewPermKeys::ACTIVITY_LOG_VIEW,
            \App\NewPermKeys::ACTIVITY_LOG_EXPORT,
            \App\NewPermKeys::ACTIVITY_LOG_MANAGE,
            \App\PermKeys::SETTINGS_VIEW,
        ]);
    $canViewAccessControlPermissionKeys = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::DEVELOPERS_PERMS);
    $isAccessControlOpen =
        request()->routeIs('packages.access-control.*') ||
        request()->routeIs('admins.*') ||
        request()->routeIs('packages.access-control.roles.*') ||
        request()->routeIs('dash.roles.*') ||
        request()->routeIs('dash.perms.*') ||
        request()->routeIs('dash.developers.perms.*') ||
        request()->routeIs('dash.activity.log');
@endphp

@if (
    $canViewAccessControlUsers ||
    $canViewAccessControlRoles ||
    $canViewAccessControlActivity ||
    $canViewAccessControlPermissionKeys ||
    $isAccessControlOpen
)
    <li class="collapsed">
        <a class="m-link {{ $isAccessControlOpen ? 'active' : '' }}"
           data-bs-toggle="collapse" data-bs-target="#access_control_package_menu" href="#"
           aria-expanded="{{ $isAccessControlOpen ? 'true' : 'false' }}">
            <i class="fas fa-box-open"></i>
            <span class="ms-2">{{ __('access-control::sidebar.packages') }}</span>
            <span class="arrow far fa-angle-left ms-auto text-end"></span>
        </a>
        <ul class="sub-menu collapse {{ $isAccessControlOpen ? 'show' : '' }}"
            id="access_control_package_menu">
            @include('access-control::package-sidebar')
        </ul>
    </li>
@endif
