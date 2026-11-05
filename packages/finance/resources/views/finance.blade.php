<?php
$permissions = ['printSettings.create', 'letters.index', 'officialVacations.index', 'departments.index', 'rewards.index', 'permissionsSettings.index', 'tax.index', 'jobs.index', 'allowances.index', 'permissions.index', 'sanctions.index', 'vacation-year.index', 'vacations.index', 'insurances.index', 'shifts.index'];
?>

{{-- @if (in_array('dashboard-read', $user_permission_list)) --}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'finance.home.index' ? 'active' : '' }}"
        href="{{ route('finance.home.index') }}">
        <i class="fa fa-home"></i>
        <span class="ms-2"> {{ __('finance::finance.dashboard') }}</span>
    </a>
</li>
{{-- @endif --}}

{{-- تقارير مدفوعات ميسر: صلاحية مستقلة، ويُتحكم بظهورها من إعدادات ميسر في لوحة Super Admin. --}}
@if (\Illuminate\Support\Facades\Route::has('moyasar-payments.index')
    && \App\Support\MoyasarSettings::reportsEnabled()
    && @AdminGuardHasPerm(\App\PermKeys::PAYMENTS_MOYASAR_VIEW))
    <li>
        <a class="m-link {{ request()->routeIs('moyasar-payments.*') ? 'active' : '' }}"
            href="{{ route('moyasar-payments.index') }}">
            <i class="fa fa-credit-card"></i>
            <span class="ms-2">{{ __('payment_gateways.moyasar.menu') }}</span>
        </a>
    </li>
@endif


<li class="collapsed">
    <a class="m-link
            {{ in_array(Route::currentRouteName(), [
                'finance.confirm-settings.index',
                'finance.tax-setting.index',
                'finance.years.index',
                'finance.opening-balance.index',
                'finance.fiscal-year-closing.index',
            ]) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'active'
                : '' }}"
        data-bs-toggle="collapse" data-bs-target="#settings1" href="#">
        <i class="fa fa-gears"></i>
        <span class="ms-2">{{ __('finance::finance.settings') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.confirm-settings.index',
                'finance.tax-setting.index',
                'finance.years.index',
                'finance.opening-balance.index',
                'finance.fiscal-year-closing.index',
            ]) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'show'
                : '' }}"
        id="settings1">
        {{--        @if (in_array('finance.confirm-settings-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.confirm-settings.index' ? 'active' : '' }}"
                href="{{ route('finance.confirm-settings.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.Account settings') }} </span>

            </a>
        </li>
        {{--        @endif --}}
        @if (auth('admin')->user()?->can('finance_chart_settings_manage'))
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.chart-settings.index' ? 'active' : '' }}"
                href="{{ route('finance.chart-settings.index') }}">
                <span class="ms-2"> إعدادات الدليل المحاسبي </span>
            </a>
        </li>
        @endif
        {{--        @if (in_array('finance.tax-setting-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.tax-setting.index' ? 'active' : '' }}"
                href="{{ route('finance.tax-setting.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.taxSettings') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.years-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.years.index' ? 'active' : '' }}"
                href="{{ route('finance.years.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.fn-years') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.opening-balance-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.opening-balance.index' ? 'active' : '' }}"
                href="{{ route('finance.opening-balance.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.opening-balance') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('dashboard-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.fiscal-year-closing.index' ? 'active' : '' }}"
                href="{{ route('finance.fiscal-year-closing.index') }}">
                <span class="ms-2"> {{ __('finance::finance.Closing the fiscal year') }}</span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>

{{-- <li class="collapsed"> --}}
{{--    <a class="m-link --}}
{{--            {{ in_array(Route::currentRouteName(), [ --}}
{{--                'finance.confirm-settings.index', --}}
{{--                'finance.tax-setting.index', --}}
{{--                'finance.years.index', --}}
{{--            ]) && --}}
{{--            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused'])) --}}
{{--                ? 'active' --}}
{{--                : '' }}" --}}
{{--       data-bs-toggle="collapse" data-bs-target="#settings1" href="#"> --}}
{{--        <i class="fa fa-gears"></i> --}}
{{--        <span class="ms-2">{{ __('finance::finance.settings') }}</span> --}}
{{--        <span class="arrow fa fa-angle-left ms-auto text-end"></span> --}}
{{--    </a> --}}
{{--    <ul class="sub-menu collapse --}}
{{--            {{ in_array(Route::currentRouteName(), [ --}}
{{--                'finance.confirm-settings.index', --}}
{{--                'finance.tax-setting.index', --}}
{{--                'finance.years.index', --}}
{{--            ]) && --}}
{{--            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused'])) --}}
{{--                ? 'show' --}}
{{--                : '' }}" --}}
{{--        id="settings1"> --}}
{{--        --}}{{--        @if (in_array('finance.confirm-settings-read', $user_permission_list)) --}}
{{--        <li> --}}
{{--            <a class="m-link {{ Route::currentRouteName() == 'finance.confirm-settings.index' ? 'active' : '' }}" --}}
{{--               href="{{ route('finance.confirm-settings.index') }}"> --}}
{{--                <span class="ms-2"> {{ trans('finance::finance.Account settings') }} </span> --}}

{{--            </a> --}}
{{--        </li> --}}
{{--        --}}{{--        @endif --}}
{{--        --}}{{--        @if (in_array('finance.tax-setting-read', $user_permission_list)) --}}
{{--        <li> --}}
{{--            <a class="m-link {{ Route::currentRouteName() == 'finance.tax-setting.index' ? 'active' : '' }}" --}}
{{--               href="{{ route('finance.tax-setting.index') }}"> --}}
{{--                <span class="ms-2"> {{ trans('finance::finance.taxSettings') }} </span> --}}
{{--            </a> --}}
{{--        </li> --}}
{{--        --}}{{--        @endif --}}
{{--        --}}{{--        @if (in_array('finance.years-read', $user_permission_list)) --}}
{{--        <li> --}}
{{--            <a class="m-link {{ Route::currentRouteName() == 'finance.years.index' ? 'active' : '' }}" --}}
{{--               href="{{ route('finance.years.index') }}"> --}}
{{--                <span class="ms-2"> {{ trans('finance::finance.fn-years') }} </span> --}}
{{--            </a> --}}
{{--        </li> --}}
{{--        --}}{{--        @endif --}}
{{--        --}}{{--        @if (in_array('dashboard-read', $user_permission_list)) --}}
{{--        <li> --}}
{{--            <a class="m-link" href="#!"> --}}
{{--                <span class="ms-2"> {{ __('finance::finance.Closing the fiscal year') }}</span> --}}
{{--            </a> --}}
{{--        </li> --}}
{{--        --}}{{--        @endif --}}
{{--    </ul> --}}
{{-- </li> --}}


<li class="collapsed">
    <a class="m-link
                {{ in_array(Route::currentRouteName(), [
                    'finance.boxes.index',
                    'finance.accounting-guide-management.index',
                    'finance.accounts.index',
                    // 'finance.cost-center-management.index',
                    // 'finance.cost-center-notification.index',
                    // 'finance.cost-centers.index',
                    'finance.banks.index',
                    //                    'finance.suppliers.index',
                ]) &&
                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                    ? 'active'
                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#accounts_section" href="#">
        <i class="fa fa-fingerprint"></i>
        <span class="ms-2">{{ __('finance::finance.accounts_section') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
                {{ in_array(Route::currentRouteName(), [
                    'finance.boxes.index',
                    'finance.accounting-guide-management.index',
                    'finance.accounts.index',
                    // 'finance.cost-center-management.index',
                    // 'finance.cost-center-notification.index',
                    // 'finance.cost-centers.index',
                    'finance.banks.index',
                    //                    'finance.suppliers.index',
                ]) &&
                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                    ? 'show'
                    : '' }}"
        id="accounts_section">

        {{--        @if (in_array('finance.accounting-guide-management-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.accounting-guide-management.index' ? 'active' : '' }}"
                href="{{ route('finance.accounting-guide-management.index') }}">
                <span class="ms-2"> {{ __('finance::finance.Accounting guide management') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.accounts-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.accounts.index' ? 'active' : '' }}"
                href="{{ route('finance.accounts.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.accounts') }} </span>
            </a>
        </li>
        {{--        @endif --}}



        {{--        @if (in_array('finance.banks-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.banks.index' ? 'active' : '' }}"
                href="{{ route('finance.banks.index') }}">
                <span class="ms-2"> {{ __('finance::finance.banks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.boxes-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.boxes.index' ? 'active' : '' }}"
                href="{{ route('finance.boxes.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.boxs_sidebar') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.suppliers-read', $user_permission_list)) --}}
        {{--            <li> --}}
        {{--                <a class="m-link {{ Route::currentRouteName() == 'finance.suppliers.index' ? 'active' : '' }}" --}}
        {{--                   href="{{ route('finance.suppliers.index') }}"> --}}
        {{--                    <span class="ms-2"> {{ trans('finance.suppliers') }} </span> --}}
        {{--                </a> --}}
        {{--            </li> --}}
        {{--        @endif --}}
    </ul>
</li>

@php
    $isSuppliersPage = in_array(Route::currentRouteName(), [
        'finance.suppliers.index',
        'finance.suppliers.create',
        'finance.suppliers.edit',
    ]);
    $isSupplierTypesPage = in_array(Route::currentRouteName(), [
        'finance.suppliers_types.index',
        'finance.suppliers_types.create',
        'finance.suppliers_types.edit',
    ]);
    $isExternalAgentsPage = Route::currentRouteName() === 'finance.external-agents.index';
    $isSuppliersMenuOpen = $isSuppliersPage || $isSupplierTypesPage || $isExternalAgentsPage;
@endphp
<li class="collapsed">
    <a class="m-link
                {{ $isSuppliersMenuOpen ? 'active' : '' }}" data-bs-toggle="collapse"
        data-bs-target="#suppliers" href="#">
        <i class="fa fa-truck"></i>
        <span class="ms-2">{{ __('finance::finance.suppliers') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ $isSuppliersMenuOpen ? 'show' : '' }}" id="suppliers">

        <li>
            <a class="m-link {{ $isSuppliersPage ? 'active' : '' }}" href="{{ route('finance.suppliers.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.suppliers') }} </span>
            </a>
        </li>
        <li>
            <a class="m-link {{ $isSupplierTypesPage ? 'active' : '' }}"
                href="{{ route('finance.suppliers_types.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.suppliers_types') }} </span>
            </a>
        </li>
        <li>
            <a class="m-link {{ $isExternalAgentsPage ? 'active' : '' }}"
                href="{{ route('finance.external-agents.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.external_agents') }} </span>
            </a>
        </li>

    </ul>
</li>

<li class="collapsed">
    <a class="m-link
                {{ in_array(Route::currentRouteName(), ['finance.client-categories.index', 'finance.clients.index']) &&
                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                    ? 'active'
                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#clients" href="#">
        <i class="fa fa-users"></i>
        <span class="ms-2">{{ __('finance::finance.clients') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), ['finance.client-categories.index', 'finance.clients.index']) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'show'
                : '' }}"
        id="clients">

        {{--        @if (in_array('finance.client-categories-read', $user_permission_list)) --}}
        <li>
            {{--            <a class="m-link {{ Route::currentRouteName() == 'finance.cost-center-notification.index' ? 'active' : '' }}" --}}
            {{--               href="{{ route('finance.cost-center-notification.index') }}"> --}}
            {{--                <span class="ms-2"> {{ __('finance::finance.cost_centers') }}</span> --}}
            <a class="m-link {{ Route::currentRouteName() == 'finance.client-categories.index' ? 'active' : '' }}"
                href="{{ route('finance.client-categories.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.client-categories') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.clients-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.clients.index' ? 'active' : '' }}"
                href="{{ route('finance.clients.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.clients') }} </span>
            </a>
        </li>
        {{--        @endif --}}

    </ul>
</li>


<li class="collapsed">
    <a class="m-link
                {{ in_array(Route::currentRouteName(), [
                    'finance.principles.index',
                    'finance.principles.show',
                    'finance.transfer-principle.index',
                    'finance.manual-depreciations.index',
                    'finance.group-depreciation.index',
                    'finance.principle-sales.index',
                    'finance.principle-report.index',
                    'finance.principle-websites.index',
                ])
                    ? 'active'
                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#principles" href="#">
        <i class="fa fa-users"></i>
        <span class="ms-2">{{ __('finance::finance.principles') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.principles.index',
                'finance.principles.show',
                'finance.transfer-principle.index',
                'finance.manual-depreciations.index',
                'finance.group-depreciation.index',
                'finance.principle-sales.index',
                'finance.principle-report.index',
                'finance.principle-websites.index',
            ])
                ? 'show'
                : '' }}"
        id="principles">
        <!-- رابط لقائمة principle websites -->
        {{--        @if (in_array('finance.principle-websites-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.principle-websites.index' ? 'active' : '' }}"
                href="{{ route('finance.principle-websites.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.principle-websites') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.principles-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.principles.index' ? 'active' : '' }}"
                href="{{ route('finance.principles.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.principles') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.transfer-principle-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.transfer-principle.index' ? 'active' : '' }}"
                href="{{ route('finance.transfer-principle.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.transfer-principle') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.manual-depreciations-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.manual-depreciations.index' ? 'active' : '' }}"
                href="{{ route('finance.manual-depreciations.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.manual-depreciations') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.principle-report-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.principle-report.index' ? 'active' : '' }}"
                href="{{ route('finance.principle-report.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.principle-report') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.group-depreciation-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.group-depreciation.index' ? 'active' : '' }}"
                href="{{ route('finance.group-depreciation.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.group-depreciation') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.principle-sales-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.principle-sales.index' ? 'active' : '' }}"
                href="{{ route('finance.principle-sales.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.principle-sales') }} </span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>

{{-- cost center --}}
<li class="collapsed">
    <a class="m-link
            {{ in_array(Route::currentRouteName(), [
                'finance.cost-center-management.index',
                'finance.cost-center-notification.index',
                'finance.cost-centers.index',
            ]) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'active'
                : '' }}"
        data-bs-toggle="collapse" data-bs-target="#cost_center" href="#">
        <i class="fa fa-money-bill"></i>
        <span class="ms-2">{{ __('finance::finance.cost_center') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.cost-center-management.index',
                'finance.cost-center-notification.index',
                'finance.cost-centers.index',
            ]) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'show'
                : '' }}"
        id="cost_center">
        {{--        @if (in_array('finance.cost-center-management-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.cost-center-management.index' ? 'active' : '' }}"
                href="{{ route('finance.cost-center-management.index') }}">
                <span class="ms-2"> {{ __('finance::finance.Cost center management') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.cost-center-notification-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.cost-center-notification.index' ? 'active' : '' }}"
                href="{{ route('finance.cost-center-notification.index') }}">
                <span class="ms-2"> {{ __('finance::finance.cost_centers_notifications') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.cost-centers-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.cost-centers.index' ? 'active' : '' }}"
                href="{{ route('finance.cost-centers.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.Cost Center Operations') }} </span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>


<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'finance.add-constraint.create',
        'finance.constraints.index',
        'finance.constraints.pending',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#constraint_section" href="#">
        <i class="fa fa-file-invoice-dollar"></i>
        <span class="ms-2">{{ __('finance::finance.constraint_section') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.add-constraint.create',
                'finance.constraints.index',
                'finance.constraints.pending',
            ]) &&
            (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                ? 'show'
                : '' }}"
        id="constraint_section">

        {{--        @if (in_array('finance.opening-balance-read', $user_permission_list)) --}}
        {{--            <li> --}}
        {{--                <a class="m-link {{ Route::currentRouteName() == 'finance.opening-balance.index' ? 'active' : '' }}" --}}
        {{--                   href="{{ route('finance.opening-balance.index') }}"> --}}
        {{--                    <span class="ms-2"> {{ trans('finance.opening-balance') }} </span> --}}
        {{--                </a> --}}
        {{--            </li> --}}
        {{--        @endif --}}
        {{--        @if (in_array('finance.constraints-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.constraints.index' ? 'active' : '' }}"
                href="{{ route('finance.constraints.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.Managing daily constraints') }} </span>
            </a>
        </li>
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.constraints.pending' ? 'active' : '' }}"
                href="{{ route('finance.constraints.pending') }}">
                <span class="ms-2"> {{ trans('finance::finance.pending_constraints_approval') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.add-constraint-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.add-constraint.create' ? 'active' : '' }}"
                href="{{ route('finance.add-constraint.create') }}">
                <span class="ms-2"> {{ __('finance::finance.Add daily constraint') }}</span>
            </a>
        </li>
        {{--        @endif --}}

    </ul>
</li>

<li class="{{ request()->routeIs('finance.procurement.*') ? '' : 'collapsed' }}">
    <a class="m-link {{ request()->routeIs('finance.procurement.*') ? 'active' : '' }}"
        data-bs-toggle="collapse" data-bs-target="#procurement_section" href="#procurement_section"
        aria-controls="procurement_section" aria-expanded="{{ request()->routeIs('finance.procurement.*') ? 'true' : 'false' }}">
        <i class="fa fa-shopping-cart"></i><span class="ms-2">المشتريات</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse {{ request()->routeIs('finance.procurement.*') ? 'show' : '' }}" id="procurement_section">
        <li>
            <a class="m-link {{ request()->routeIs('finance.procurement.index') && !request()->filled('kind') ? 'active' : '' }}"
                href="{{ route('finance.procurement.index') }}"><span class="ms-2">جميع المشتريات</span></a>
        </li>
        <li>
            <a class="m-link {{ request()->routeIs('finance.procurement.index') && request('kind') === 'request' ? 'active' : '' }}"
                href="{{ route('finance.procurement.index', ['kind' => 'request']) }}"><span class="ms-2">طلبات الشراء</span></a>
        </li>
        <li>
            <a class="m-link {{ request()->routeIs('finance.procurement.create') && request('kind') === 'request' ? 'active' : '' }}"
                href="{{ route('finance.procurement.create', ['kind' => 'request']) }}"><span class="ms-2">إضافة طلب شراء</span></a>
        </li>
        <li>
            <a class="m-link {{ request()->routeIs('finance.procurement.index') && request('kind') === 'invoice' ? 'active' : '' }}"
                href="{{ route('finance.procurement.index', ['kind' => 'invoice']) }}"><span class="ms-2">فواتير الشراء</span></a>
        </li>
        <li>
            <a class="m-link {{ request()->routeIs('finance.procurement.create') && request('kind') !== 'request' ? 'active' : '' }}"
                href="{{ route('finance.procurement.create', ['kind' => 'invoice']) }}"><span class="ms-2">إضافة فاتورة شراء</span></a>
        </li>
    </ul>
</li>

<li class="collapsed">
    <a class="m-link
                                {{ in_array(Route::currentRouteName(), [
                                    'finance.bonds-sarf.index',
                                    'finance.bonds-qabd.index',
                                    'finance.account-transfer.index',
                                    'finance.general-bonds-sarf.index',
                                    'finance.general-bonds-qabd.index',
                                    'commissions.finance.index',
                                    'commissions.marketers.payments.index',
                                    'commissions.marketers.payments.print',
                                    'invoices.index',
                                    'invoices.create',
                                    'invoices.show',
                                    'invoice-drafts.index',
                                ]) &&
                                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                    ? 'active'
                                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#bonds" href="#">
        <i class="bi bi-files"></i>
        <span class="ms-2">{{ __('finance::finance.bonds') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.bonds-sarf.index',
                                         'finance.bonds-qabd.index',
                                         'finance.account-transfer.index',
                                         'finance.general-bonds-sarf.index',
                                         'finance.general-bonds-qabd.index',
                                         'commissions.finance.index',
                                         'commissions.marketers.payments.index',
                                         'commissions.marketers.payments.print',
                                         'invoices.index',
                                         'invoices.create',
                                         'invoices.show',
                                         'invoice-drafts.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="bonds">

        {{--        @if (in_array('finance.bonds-sarf-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.bonds-sarf.index' ? 'active' : '' }}"
                href="{{ route('finance.bonds-sarf.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.sarf_bonds') }} </span>
            </a>
        </li>
        @if (@SystemModuleEnabled('commissions') && @AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_PAY))
            <li>
                <a class="m-link {{ request()->routeIs('commissions.finance.*') || request()->routeIs('commissions.marketers.payments.*') ? 'active' : '' }}"
                    href="{{ route('commissions.finance.index') }}">
                    {{-- <i class="fa fa-percent me-1"></i> --}}
                    <span class="ms-2">صرف عمولات المسوقين</span>
                </a>
            </li>
        @endif
        {{--        @endif --}}
        {{--        @if (in_array('finance.general-bonds-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.general-bonds-sarf.index' ? 'active' : '' }}"
                href="{{ route('finance.general-bonds-sarf.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.General Bond Sarf') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.bonds-qabd-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.bonds-qabd.index' ? 'active' : '' }}"
                href="{{ route('finance.bonds-qabd.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.qabd_bonds') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.general-bonds-qabd-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.general-bonds-qabd.index' ? 'active' : '' }}"
                href="{{ route('finance.general-bonds-qabd.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.General Bond Qabd') }} </span>
            </a>
        </li>
        {{--        @endif --}}

        <li>
            <a class="m-link {{ in_array(Route::currentRouteName(), ['invoices.index', 'invoices.create', 'invoices.show']) ? 'active' : '' }}"
                href="{{ route('invoices.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.invoices') }} </span>
            </a>
        </li>

        @if (\App\Support\InvoiceAuthorization::canViewDrafts() || @AdminGuardHasPerm(\App\PermKeys::INVOICES_CREATE_DRAFT))
            <li>
                <a class="m-link {{ request()->routeIs('invoice-drafts.*') ? 'active' : '' }}"
                    href="{{ route('invoice-drafts.index') }}">
                    <span class="ms-2">طلبات اعتماد الفواتير</span>
                </a>
            </li>
        @endif

        {{--        @if (in_array('finance.account-transfer-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-transfer.index' ? 'active' : '' }}"
                href="{{ route('finance.account-transfer.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.account_transfers') }} </span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>

<li class="collapsed">
    <a class="m-link
                                {{ in_array(Route::currentRouteName(), [
                                    'finance.accounts-balances.index',
                                    'finance.clients-balances.index',
                                    'finance.suppliers-balances.index',
                                    'finance.treasury-and-banks-balances.index',
                                ]) &&
                                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                    ? 'active'
                                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#balances_section" href="#">
        <i class="fa fa-scale-balanced"></i>
        <span class="ms-2">{{ __('finance::finance.balances') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.accounts-balances.index',
                                         'finance.clients-balances.index',
                                         'finance.suppliers-balances.index',
                                         'finance.treasury-and-banks-balances.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="balances_section">

        {{--        @if (in_array('finance.accounts-balances-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.accounts-balances.index' ? 'active' : '' }}"
                href="{{ route('finance.accounts-balances.index') }}">
                <span class="ms-2">{{ trans('finance::finance.accounts_balances') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.clients-balances-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.clients-balances.index' ? 'active' : '' }}"
                href="{{ route('finance.clients-balances.index') }}">
                <span class="ms-2">{{ trans('finance::finance.clients_balances') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.suppliers-balances-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.suppliers-balances.index' ? 'active' : '' }}"
                href="{{ route('finance.suppliers-balances.index') }}">
                <span class="ms-2">{{ trans('finance::finance.suppliers_balances') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.treasury-and-banks-balances-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.treasury-and-banks-balances.index' ? 'active' : '' }}"
                href="{{ route('finance.treasury-and-banks-balances.index') }}">
                <span class="ms-2">{{ trans('finance::finance.treasury_and_banks_balances') }}</span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>

<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'finance.account-cost-centers.index',
        'finance.operational-summary.index',
        'finance.cash-movements.index',
        'finance.daily-cash-reference-lines.index',
        'finance.account-statement.index',
        'finance.debtor-accounts.index',
        'finance.creditor-accounts.index',
        'finance.account-statement-clients.index',
        'finance.account-statement-suppliers.index',
        'finance.bank-account-statement.index',
        'finance.arithmetic-transactions.index',
        'finance.account-balance-differed-normal.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#reports" href="#">
        <i class="fa fa-chart-bar"></i>
        <span class="ms-2">{{ __('finance::finance.reports') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.account-cost-centers.index',
                                         'finance.operational-summary.index',
                                         'finance.cash-movements.index',
                                         'finance.daily-cash-reference-lines.index',
                                         'finance.account-statement.index',
                                         'finance.debtor-accounts.index',
                                         'finance.creditor-accounts.index',
                                         'finance.account-statement-clients.index',
                                         'finance.account-statement-suppliers.index',
                                         'finance.bank-account-statement.index',
                                         'finance.arithmetic-transactions.index',
                                         //                                         'finance.accounts-debtor-report.index',
                                         //                                        'finance.accounts-creditor-report.index',
                                         'finance.account-balance-differed-normal.index',
                                         //                                        'finance.unusual-accounts.index'
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="reports">

        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.operational-summary.index' ? 'active' : '' }}"
                href="{{ route('finance.operational-summary.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.operational_summary') }} </span>
            </a>
        </li>
        {{--        @if (in_array('finance.account-statement-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-statement.index' ? 'active' : '' }}"
                href="{{ route('finance.account-statement.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.account-statment') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.account-statement-clients-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-statement-clients.index' ? 'active' : '' }}"
                href="{{ route('finance.account-statement-clients.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.account-statement-clients') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.account-statement-suppliers-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-statement-suppliers.index' ? 'active' : '' }}"
                href="{{ route('finance.account-statement-suppliers.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.account-statement-suppliers') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.account-cost-centers-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-cost-centers.index' ? 'active' : '' }}"
                href="{{ route('finance.account-cost-centers.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.accounts-cost-centers') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.cash-movements-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.cash-movements.index' ? 'active' : '' }}"
                href="{{ route('finance.cash-movements.index') }}">
                <span class="ms-2"> حركة الصندوق والبنوك اليومية </span>
            </a>
        </li>
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.daily-cash-reference-lines.index' ? 'active' : '' }}"
                href="{{ route('finance.daily-cash-reference-lines.index') }}">
                <span class="ms-2"> بنود تقرير صندوق اليومية </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.bank-account-statement-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.bank-account-statement.index' ? 'active' : '' }}"
                href="{{ route('finance.bank-account-statement.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.bank-account-statement') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.creditor-accounts-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.creditor-accounts.index' ? 'active' : '' }}"
                href="{{ route('finance.creditor-accounts.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.creditor-accounts') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.debtor-accounts-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.debtor-accounts.index' ? 'active' : '' }}"
                href="{{ route('finance.debtor-accounts.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.debtor-accounts') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.account-balance-differed-normal-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.account-balance-differed-normal.index' ? 'active' : '' }}"
                href="{{ route('finance.account-balance-differed-normal.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.account-balance-differed-normal') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{-- <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.arithmetic-transactions.index' ? 'active' : '' }}"
               href="{{ route('finance.arithmetic-transactions.index') }}">
                <span class="ms-2"> {{ trans('finance.arithmetic-transactions') }} </span>
            </a>
        </li> --}}

    </ul>
</li>
<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'finance.income-list.index',
        'finance.finance-center-list.index',
        'finance.profit-loss.index',
        'finance.audit-budget-supplier.index',
        'finance.client-trial-balance.index',
        'finance.client-receivables.index',
        'finance.trail-balance.index',
        'finance.audit-budget-monthly.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#Financial-Lists" href="#">
        <i class="fa fa-chart-line"></i>
        <span class="ms-2">{{ __('finance::finance.Financial Lists') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.income-list.index',
                                         'finance.finance-center-list.index',
                                         'finance.profit-loss.index',
                                         'finance.audit-budget-supplier.index',
                                         'finance.client-trial-balance.index',
                                         'finance.client-receivables.index',
        'finance.client-trial-balance.index',
                                         'finance.trail-balance.index',
                                         'finance.audit-budget-monthly.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="Financial-Lists">
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.finance-center-list.index' ? 'active' : '' }}" href="{{ route('finance.finance-center-list.index') }}">
                <span class="ms-2">قائمة المركز المالي (الميزانية العمومية)</span>
            </a>
        </li>


        {{--        @if (in_array('finance.income-list-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.income-list.index' ? 'active' : '' }}"
                href="{{ route('finance.income-list.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.incomeList') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.profit-loss.index' ? 'active' : '' }}"
                href="{{ route('finance.profit-loss.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.profit_loss_statement') }} </span>
            </a>
        </li>
        {{--        @if (in_array('finance.trail-balance-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.trail-balance.index' ? 'active' : '' }}"
                href="{{ route('finance.trail-balance.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.trail-balance') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.client-trial-balance.index' ? 'active' : '' }}"
                href="{{ route('finance.client-trial-balance.index') }}">
                <span class="ms-2"> ميزان مراجعة العملاء </span>
            </a>
        </li>
        @if(app(\App\Http\Middleware\EnsurePackageRoutePermission::class)->allows(auth('admin')->user(), 'finance.client-receivables.index', 'finance'))
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.client-receivables.index' ? 'active' : '' }}" href="{{ route('finance.client-receivables.index') }}">
                <span class="ms-2">مستحقات العملاء وعقود الإيجار</span>
            </a>
        </li>
        @endif
        {{--        @if (in_array('finance.audit-budget-supplier-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.audit-budget-supplier.index' ? 'active' : '' }}"
                href="{{ route('finance.audit-budget-supplier.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.audit-budget-supplier') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.audit-budget-monthly-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.audit-budget-monthly.index' ? 'active' : '' }}"
                href="{{ route('finance.audit-budget-monthly.index') }}">
                <span class="ms-2"> {{ trans('finance::finance.audit-budget-monthly') }} </span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>
<li class="collapsed">
    <a class="m-link
                                {{ in_array(Route::currentRouteName(), [
                                    'finance.sales-analysis.index',
                                    'finance.purchases-analysis.index',
                                    'finance.tax-declaration.index',
                                    'finance.tax-invoice-statement.index',
                                ]) &&
                                (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                    ? 'active'
                                    : '' }}"
        data-bs-toggle="collapse" data-bs-target="#tax_reports" href="#">
        <i class="fa fa-chart-bar"></i>
        <span class="ms-2">{{ __('finance::finance.tax_reports') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.sales-analysis.index',
                                         'finance.purchases-analysis.index',
                                         'finance.tax-declaration.index',
                                         'finance.tax-invoice-statement.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="tax_reports">

        {{--        @if (in_array('finance.sales-analysis-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.sales-analysis.index' ? 'active' : '' }}"
                href="{{ route('finance.sales-analysis.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.sales-analysis') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.purchases-analysis-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.purchases-analysis.index' ? 'active' : '' }}"
                href="{{ route('finance.purchases-analysis.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.purchases-analysis') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.tax-invoice-statement.index' ? 'active' : '' }}"
                href="{{ route('finance.tax-invoice-statement.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.tax-invoice-statement') }} </span>
            </a>
        </li>
        {{--        @if (in_array('finance.tax-declaration-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.tax-declaration.index' ? 'active' : '' }}"
                href="{{ route('finance.tax-declaration.index') }}">
                <span class="ms-2"> {{ helperTrans('finance::finance.tax-declaration') }} </span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>
