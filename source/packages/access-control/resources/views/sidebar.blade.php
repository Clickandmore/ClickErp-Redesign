@php
    $isDeveloper = (int) (auth('admin')->user()->admin_type ?? 0) === \App\Enums\AdminTypeisEnum::Developer->value;
    $canViewAccessControlRoles = $isDeveloper || @AdminGuardHasPerm(\App\PermKeys::ROLES_VIEW) || @AdminGuardHasPerm('role-permissions-read');
@endphp

@if ($canViewAccessControlRoles || request()->routeIs('role-permissions.*'))
    <li>
        <a class="m-link {{ request()->routeIs('role-permissions.*') ? 'active' : '' }}"
           href="{{ route('role-permissions.index') }}">
            <i class="fas fa-user-shield"></i>
            <span class="ms-2">{{ __('charities.role-permissions') }}</span>
        </a>
    </li>
@endif
