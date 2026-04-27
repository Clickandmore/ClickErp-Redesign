@if (@AdminGuardHasPerm(\App\PermKeys::HR_MODULE))
<?php
//$permissions = ['printSettings.create', 'letters.index', 'officialVacations.index', 'departments.index', 'rewards.index', 'permissionsSettings.index', 'tax.index', 'jobs.index', 'allowances.index', 'permissions.index', 'sanctions.index', 'vacation-year.index', 'vacations.index', 'insurances.index', 'shifts.index'];
//
//$employeesPermissions = ['mluti-employee.index', 'employeeExecelFile.index', 'salaries.index', 'daily-work-reports.index', 'employeeAttendances.index', 'employees.index', 'employees.create', 'employees.edit', 'employeeVacations.index', 'employeeSanctions.index', 'employeePermissions.index', 'employee-rewards.index', 'employee-advances.index'];
//?><!---->
{{--@if (in_array('dashboard-read', $user_permission_list))--}}

{{--@endif--}}
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
//    $readPermissions = array_filter($user_permission_list, function ($permission) use ($hrSettingsRoutes) {
//        return in_array(
//            $permission,
//            array_map(function ($route) {
//                return str_replace('.index', '-read', $route);
//            }, $hrSettingsRoutes),
//        );
//    });
@endphp
{{--<li>--}}
{{--    <a class="m-link {{ in_array(Route::currentRouteName(), $hrSettingsRoutes) ? 'active' : '' }}"--}}
{{--       href="{{ route('hr.home.index') }}">--}}
{{--        <i class="far fa-home"></i>--}}
{{--        <span class="ms-2">{{ __('hr::hr.home') }}</span>--}}
{{--    </a>--}}
{{--</li>--}}
{{--@if (!empty($readPermissions))--}}
<li>
    <a class="m-link {{ in_array(Route::currentRouteName(), $hrSettingsRoutes) ? 'active' : '' }}"
       href="{{ route('departments.index') }}">
        <i class="fa  fa-file-text-o"></i>
        <span class="ms-2"> {{ __('hr::hr.Settings') }}</span>
    </a>
</li>
{{--@endif--}}

{{--@if (in_array('employees-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employees.index' || Route::currentRouteName() == 'employees.create' || Route::currentRouteName() == 'employees.edit' ? 'active' : '' }}"
       href="{{ route('employees.index') }}">
        <i class="fas fa-user"></i>
        <span class="ms-2"> {{ __('hr::hr.employees') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employeeVacations-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employeeVacations.index' ? 'active' : '' }}"
       href="{{ route('employeeVacations.index') }}">
        <i class="fas fa-plane"></i>
        <span class="ms-2"> {{ __('hr::hr.employeeVacations') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employee-advances-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employee-advances.index' ? 'active' : '' }}"
       href="{{ route('employee-advances.index') }}">
        <i class="fas fa-money-bill"></i>
        <span class="ms-2"> {{ __('hr::hr.employee_advances') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employeeSanctions-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employeeSanctions.index' ? 'active' : '' }}"
       href="{{ route('employeeSanctions.index') }}">
        <i class="fas fa-exclamation-triangle"></i>
        <span class="ms-2"> {{ __('hr::hr.employeeSanctions') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employeePermissions-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employeePermissions.index' ? 'active' : '' }}"
       href="{{ route('employeePermissions.index') }}">
        <i class="fas fa-key"></i>
        <span class="ms-2"> {{ __('hr::hr.employeePermissions') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employeeAttendances-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employeeAttendances.index' ? 'active' : '' }}"
       href="{{ route('employeeAttendances.index') }}">
        <i class="fas fa-calendar-check"></i>
        <span class="ms-2"> {{ __('hr::hr.employeeAttendances') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employeeExecelFile-create', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employeeExecelFile.index' ? 'active' : '' }}"
       href="{{ route('employeeExecelFile.index') }}">
        <i class="fas fa-file-excel"></i>
        <span class="ms-2"> {{ __('hr::hr.employeeExecelFile') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('daily-work-reports-create', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'daily-work-reports.index' ? 'active' : '' }}"
       href="{{ route('daily-work-reports.index') }}">
        <i class="fas fa-file-alt"></i>
        <span class="ms-2"> {{ __('hr::hr.daily-work-reports') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('mluti-employee-create', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'mluti-employee.index' ? 'active' : '' }}"
       href="{{ route('mluti-employee.index') }}">
        <i class="fas fa-users"></i>
        <span class="ms-2"> {{ __('hr::hr.mluti-employee') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('salaries-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'salaries.index' ? 'active' : '' }}"
       href="{{ route('salaries.index') }}">
        <i class="fas fa-dollar-sign"></i>
        <span class="ms-2"> {{ __('hr::hr.salaries') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employee-rewards-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::is('payrolls.*') ? 'active' : '' }}"
       href="{{ route('payrolls.index') }}">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="ms-2"> {{ __('hr::hr.payrolls') }}</span>
    </a>
</li>
{{--@endif--}}
{{--@if (in_array('employee-rewards-read', $user_permission_list))--}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'employee-rewards.index' ? 'active' : '' }}"
       href="{{ route('employee-rewards.index') }}">
        <i class="fas fa-award"></i>
        <span class="ms-2"> {{ __('hr::hr.employeeRewards') }}</span>
    </a>
</li>
{{--@endif--}}
@endif
