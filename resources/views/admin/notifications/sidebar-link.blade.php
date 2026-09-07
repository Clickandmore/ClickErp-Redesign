@if (\App\Support\AdminDispatchPermissions::allows(auth('admin')->user(), \App\Support\AdminDispatchPermissions::VIEW))
    <li>
        <a class="m-link {{ request()->routeIs('admin.dispatch.*') ? 'active' : '' }}" href="{{ route('admin.dispatch.index') }}">
            <i class="fa fa-bell"></i><span class="ms-2">إشعارات الأدمنز</span>
        </a>
    </li>
@endif
