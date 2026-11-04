@php
    $isHrSettings = request()->routeIs('hr.home.*') ||
        request()->routeIs('departments.*') ||
        request()->routeIs('jobs.*') ||
        request()->routeIs('allowances.*') ||
        request()->routeIs('sanctions.*') ||
        request()->routeIs('vacation-year.*') ||
        request()->routeIs('year-status.*') ||
        request()->routeIs('vacations.*') ||
        request()->routeIs('permissions.*') ||
        request()->routeIs('permissionsSettings.*') ||
        request()->routeIs('insurances.*') ||
        request()->routeIs('tax.*') ||
        request()->routeIs('rewards.*') ||
        request()->routeIs('shifts.*') ||
        request()->routeIs('officialVacations.*');
    $hasHrMaidsRoute = Route::has('hr.maids.index');
@endphp

<li>
    <a class="m-link {{ $isHrSettings ? 'active' : '' }}"
       href="{{ route('departments.index') }}">
        <i class="fa fa-file-text-o"></i>
        <span class="ms-2">{{ __('hr::hr.Settings') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
       href="{{ route('employees.index') }}">
        <i class="fas fa-user"></i>
        <span class="ms-2">{{ __('hr::hr.employees') }}</span>
    </a>
</li>
@if ($hasHrMaidsRoute)
    <li>
        <a class="m-link {{ request()->routeIs('hr.maids.index') ? 'active' : '' }}"
           href="{{ route('hr.maids.index') }}">
            <i class="fas fa-user-nurse"></i>
            <span class="ms-2">{{ __('hr::hr.maids') }}</span>
        </a>
    </li>
@endif
@if (Route::has('hr.maids.employees'))
    <li>
        <a class="m-link {{ request()->routeIs('hr.maids.employees') ? 'active' : '' }}"
           href="{{ route('hr.maids.employees') }}">
            <i class="fas fa-user-tie"></i>
            <span class="ms-2">{{ __('hr::hr.employed_maids') }}</span>
        </a>
    </li>
@endif
<li>
    <a class="m-link {{ request()->routeIs('employeeVacations.*') ? 'active' : '' }}"
       href="{{ route('employeeVacations.index') }}">
        <i class="fas fa-plane"></i>
        <span class="ms-2">{{ __('hr::hr.employeeVacations') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employee-advances.*') ? 'active' : '' }}"
       href="{{ route('employee-advances.index') }}">
        <i class="fas fa-money-bill"></i>
        <span class="ms-2">{{ __('hr::hr.employee_advances') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employee-custodies.*') ? 'active' : '' }}"
       href="{{ route('employee-custodies.index') }}">
        <i class="fas fa-briefcase"></i>
        <span class="ms-2">{{ __('hr::hr.employee_custodies') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employee-custody-locations.*') ? 'active' : '' }}"
       href="{{ route('employee-custody-locations.index') }}">
        <i class="fas fa-map-marker-alt"></i>
        <span class="ms-2">{{ __('hr::hr.custody_locations') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employeeSanctions.*') ? 'active' : '' }}"
       href="{{ route('employeeSanctions.index') }}">
        <i class="fas fa-exclamation-triangle"></i>
        <span class="ms-2">{{ __('hr::hr.employeeSanctions') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employeePermissions.*') ? 'active' : '' }}"
       href="{{ route('employeePermissions.index') }}">
        <i class="fas fa-key"></i>
        <span class="ms-2">{{ __('hr::hr.employeePermissions') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employeeAttendances.*') ? 'active' : '' }}"
       href="{{ route('employeeAttendances.index') }}">
        <i class="fas fa-calendar-check"></i>
        <span class="ms-2">{{ __('hr::hr.employeeAttendances') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employeeExecelFile.*') ? 'active' : '' }}"
       href="{{ route('employeeExecelFile.index') }}">
        <i class="fas fa-file-excel"></i>
        <span class="ms-2">{{ __('hr::hr.employeeExecelFile') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('daily-work-reports.*') ? 'active' : '' }}"
       href="{{ route('daily-work-reports.index') }}">
        <i class="fas fa-file-alt"></i>
        <span class="ms-2">{{ __('hr::hr.daily-work-reports') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('mluti-employee.*') ? 'active' : '' }}"
       href="{{ route('mluti-employee.index') }}">
        <i class="fas fa-users"></i>
        <span class="ms-2">{{ __('hr::hr.mluti-employee') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}"
       href="{{ route('salaries.index') }}">
        <i class="fas fa-dollar-sign"></i>
        <span class="ms-2">{{ __('hr::hr.salaries') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('payrolls.*') ? 'active' : '' }}"
       href="{{ route('payrolls.index') }}">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="ms-2">{{ __('hr::hr.payrolls') }}</span>
    </a>
</li>
<li>
    <a class="m-link {{ request()->routeIs('employee-rewards.*') ? 'active' : '' }}"
       href="{{ route('employee-rewards.index') }}">
        <i class="fas fa-award"></i>
        <span class="ms-2">{{ __('hr::hr.employeeRewards') }}</span>
    </a>
</li>
