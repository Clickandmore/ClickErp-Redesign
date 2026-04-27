<div class="row mx-2">
    <div class="col-12">
        <ul class="nav nav-tabs tab-card mb-4 border-top-0 border-start-0 border-end-0 border border-primary rounded-4 bg-white" role="tablist">
{{--            @if (in_array('departments-read', $user_permission_list))--}}
{{--                @if (in_array('departments-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'departments.index' ? 'active' : '' }}" href="{{ route('departments.index') }}">
                            {{ __('hr::hr.departments') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('jobs-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'jobs.index' ? 'active' : '' }}" href="{{ route('jobs.index') }}">
                            {{ __('hr::hr.jobs') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('allowances-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'allowances.index' ? 'active' : '' }}" href="{{ route('allowances.index') }}">
                            {{ __('hr::hr.allowances') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('sanctions-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'sanctions.index' ? 'active' : '' }}" href="{{ route('sanctions.index') }}">
                            {{ __('hr::hr.sanctions') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('vacation-year-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'vacation-year.index' ? 'active' : '' }}" href="{{ route('vacation-year.index') }}">
                            {{ __('hr::hr.year_id_vacation') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('vacations-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'vacations.index' ? 'active' : '' }}" href="{{ route('vacations.index') }}">
                            {{ __('hr::hr.vacations') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('permissionsSettings-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'permissionsSettings.index' ? 'active' : '' }}" href="{{ route('permissionsSettings.index') }}">
                            {{ __('hr::hr.permissionsSettings') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('permissions-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'permissions.index' ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                            {{ __('hr::hr.permissions') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('insurances-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'insurances.index' ? 'active' : '' }}" href="{{ route('insurances.index') }}">
                            {{ __('hr::hr.insurances') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('tax-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'tax.index' ? 'active' : '' }}" href="{{ route('tax.index') }}">
                            {{ __('hr::hr.tax') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('rewards-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'rewards.index' ? 'active' : '' }}" href="{{ route('rewards.index') }}">
                            {{ __('hr::hr.rewards') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('shifts-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'shifts.index' ? 'active' : '' }}" href="{{ route('shifts.index') }}">
                            {{ __('hr::hr.shifts') }}
                        </a>
                    </li>
{{--                @endif--}}

{{--                @if (in_array('officialVacations-read', $user_permission_list))--}}
                    <li class="nav-item">
                        <a class="py-3 mx-1 nav-link {{ Route::currentRouteName() == 'officialVacations.index' ? 'active' : '' }}" href="{{ route('officialVacations.index') }}">
                            {{ __('hr::hr.officialVacations') }}
                        </a>
                    </li>
{{--                @endif--}}
{{--            @endif--}}
        </ul>
    </div>
</div>


