@php
    $isExternalAgentsOpen =
        request()->routeIs('offices.*') ||
        request()->routeIs('offices-file.*') ||
        request()->routeIs('offices.users.*') ||
        request()->routeIs('offices.files.*') ||
        request()->routeIs('admin.agent-imports.*');
@endphp

@if (@AdminGuardHasPerm(\App\PermKeys::OFFICE_VIEW))
    <li class="{{ $isExternalAgentsOpen ? '' : 'collapsed' }}">
        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#externalAgentsMenu" href="#">
            <i class="fa fa-building"></i>
            <span class="ms-2">إدارة الوكلاء</span>
            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
        </a>
        <ul class="sub-menu collapse {{ $isExternalAgentsOpen ? 'show' : '' }}" id="externalAgentsMenu">
            <li>
                <a class="m-link {{ request()->routeIs('offices.index', 'offices.create', 'offices.edit', 'offices.show') ? 'active' : '' }}"
                    href="{{ route('offices.index') }}">
                    <i class="fa fa-list"></i>
                    <span class="ms-2">{{ helperTrans('ingaz.offices') }}</span>
                </a>
            </li>
            @if (Route::has('offices.users.index'))
                <li>
                    <a class="m-link {{ request()->routeIs('offices.users.*') ? 'active' : '' }}"
                        href="{{ route('offices.users.index') }}">
                        <i class="fa fa-users"></i>
                        <span class="ms-2">مستخدمي الوكلاء</span>
                    </a>
                </li>
            @endif
            @if (Route::has('offices.files.index'))
                <li>
                    <a class="m-link {{ request()->routeIs('offices.files.*', 'offices-file.*') ? 'active' : '' }}"
                        href="{{ route('offices.files.index') }}">
                        <i class="fa fa-id-card"></i>
                        <span class="ms-2">ملفات المكاتب</span>
                    </a>
                </li>
            @endif
            @if (Route::has('admin.agent-imports.index'))
                <li>
                    <a class="m-link {{ request()->routeIs('admin.agent-imports.*') ? 'active' : '' }}"
                        href="{{ route('admin.agent-imports.index') }}">
                        <i class="fa fa-file-import"></i>
                        <span class="ms-2">ملفات السير المرفوعة</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif
