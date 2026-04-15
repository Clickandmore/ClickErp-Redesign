@php
    $hrSettingsRoutes = [
        'hr.home.index',
        'departments.index',
        'jobs.index',
        'allowances.index',
        'sanctions.index',
        'vacation-year.index',
        'vacations.index',
        'employeePermissions.index',
        'permissions.index',
        'insurances.index',
        'tax.index',
        'rewards.index',
        'shifts.index',
        'officialVacations.index',
    ];
@endphp

<li>
    <a class="ms-link {{ in_array(Route::currentRouteName(), $hrSettingsRoutes, true) ? 'active' : '' }}"
       href="{{ route('departments.index') }}">
        <span>{{ __('hr::hr.Settings') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
       href="{{ route('employees.index') }}">
        <span>{{ __('hr::hr.employees') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employeeVacations.*') ? 'active' : '' }}"
       href="{{ route('employeeVacations.index') }}">
        <span>{{ __('hr::hr.employeeVacations') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employee-advances.*') ? 'active' : '' }}"
       href="{{ route('employee-advances.index') }}">
        <span>{{ __('hr::hr.employee_advances') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employeeSanctions.*') ? 'active' : '' }}"
       href="{{ route('employeeSanctions.index') }}">
        <span>{{ __('hr::hr.employeeSanctions') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employeePermissions.*') ? 'active' : '' }}"
       href="{{ route('employeePermissions.index') }}">
        <span>{{ __('hr::hr.employeePermissions') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employeeAttendances.*') ? 'active' : '' }}"
       href="{{ route('employeeAttendances.index') }}">
        <span>{{ __('hr::hr.employeeAttendances') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employeeExecelFile.*') ? 'active' : '' }}"
       href="{{ route('employeeExecelFile.index') }}">
        <span>{{ __('hr::hr.employeeExecelFile') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('daily-work-reports.*') ? 'active' : '' }}"
       href="{{ route('daily-work-reports.index') }}">
        <span>{{ __('hr::hr.daily-work-reports') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('mluti-employee.*') ? 'active' : '' }}"
       href="{{ route('mluti-employee.index') }}">
        <span>{{ __('hr::hr.mluti-employee') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('salaries.*') ? 'active' : '' }}"
       href="{{ route('salaries.index') }}">
        <span>{{ __('hr::hr.salaries') }}</span>
    </a>
</li>
<li>
    <a class="ms-link {{ request()->routeIs('employee-rewards.*') ? 'active' : '' }}"
       href="{{ route('employee-rewards.index') }}">
        <span>{{ __('hr::hr.employeeRewards') }}</span>
    </a>
</li>
