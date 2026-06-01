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
<li class="collapsed">
    <a class="m-link
            {{ in_array(Route::currentRouteName(), [
                'finance.confirm-settings.index',
                'finance.tax-setting.index',
                'finance.years.index',
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
        {{--        @if (in_array('dashboard-read', $user_permission_list)) --}}
        <li>
            <a class="m-link" href="#!">
                <span class="ms-2"> {{ __('finance::finance.Closing the fiscal year') }}</span>
            </a>
        </li>
        {{--        @endif --}}
    </ul>
</li>


{{-- @if (in_array('finance.opening-balance-read', $user_permission_list)) --}}
<li>
    <a class="m-link {{ Route::currentRouteName() == 'finance.opening-balance.index' ? 'active' : '' }}"
        href="{{ route('finance.opening-balance.index') }}">
        <i class="fa fa-balance-scale me-2"></i>
        <span class="ms-2"> {{ trans('finance::finance.opening-balance') }} </span>
    </a>
</li>
{{-- @endif --}}


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
                    'finance.bank_accounts.index',
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
                    'finance.bank_accounts.index',
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
        {{--        @if (in_array('finance.bank_accounts-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.bank_accounts.index' ? 'active' : '' }}"
                href="{{ route('finance.bank_accounts.index') }}">
                <span class="ms-2"> {{ __('finance::finance.banksAccounts') }}</span>
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
                {{ $isSuppliersMenuOpen ? 'active' : '' }}"
        data-bs-toggle="collapse" data-bs-target="#suppliers" href="#">
        <i class="fa fa-truck"></i>
        <span class="ms-2">{{ __('finance::finance.suppliers') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ $isSuppliersMenuOpen ? 'show' : '' }}"
        id="suppliers">

        <li>
            <a class="m-link {{ $isSuppliersPage ? 'active' : '' }}"
                href="{{ route('finance.suppliers.index') }}">
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
    {{ in_array(Route::currentRouteName(), ['finance.add-constraint.create', 'finance.constraints.index', 'finance.constraints.pending']) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#constraint_section" href="#">
        <i class="fa fa-file-invoice-dollar"></i>
        <span class="ms-2">{{ __('finance::finance.constraint_section') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), ['finance.add-constraint.create', 'finance.constraints.index', 'finance.constraints.pending']) &&
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

<li class="collapsed">
    <a class="m-link
                                {{ in_array(Route::currentRouteName(), [
                                    'finance.bonds-sarf.index',
                                    'finance.bonds-qabd.index',
                                    'finance.account-transfer.index',
                                    'finance.general-bonds-sarf.index',
                                    'finance.general-bonds-qabd.index',
                                    'invoices.index',
                                    'invoices.create',
                                    'invoices.show',
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
                                         'invoices.index',
                                         'invoices.create',
                                         'invoices.show',
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
        'finance.exchange-checks.index',
        'finance.catch-checks.index',
        'finance.exchange-checks-data.index',
        'finance.catch-checks-data.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#check_section" href="#">
        <i class="fa fa-money-check"></i>
        <span class="ms-2">{{ __('finance::finance.checks') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.exchange-checks.index',
                                         'finance.catch-checks.index',
                                         'finance.exchange-checks-data.index',
                                         'finance.catch-checks-data.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="check_section">
        {{--        @if (in_array('finance.exchange-checks-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.exchange-checks.index' ? 'active' : '' }}"
                href="{{ route('finance.exchange-checks.index') }}">
                <span class="ms-2">{{ trans('finance::finance.exchange-checks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.catch-checks-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.catch-checks.index' ? 'active' : '' }}"
                href="{{ route('finance.catch-checks.index') }}">
                <span class="ms-2">{{ trans('finance::finance.catch-checks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.exchange-checks-data-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.exchange-checks-data.index' && request()->query('status') == 'collected' ? 'active' : '' }}"
                href="{{ route('finance.exchange-checks-data.index', ['status' => 'collected']) }}">
                <span class="ms-2">{{ trans('finance::finance.collected_exchange_checks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.exchange-checks-data-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.exchange-checks-data.index' && request()->query('status') == 'refused' ? 'active' : '' }}"
                href="{{ route('finance.exchange-checks-data.index', ['status' => 'refused']) }}">
                <span class="ms-2">{{ trans('finance::finance.refused_exchange_checks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.catch-checks-data-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.catch-checks-data.index' && request()->query('status') == 'collected' ? 'active' : '' }}"
                href="{{ route('finance.catch-checks-data.index', ['status' => 'collected']) }}">
                <span class="ms-2">{{ trans('finance::finance.collected_catch_checks') }}</span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.catch-checks-data-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.catch-checks-data.index' && request()->query('status') == 'refused' ? 'active' : '' }}"
                href="{{ route('finance.catch-checks-data.index', ['status' => 'refused']) }}">
                <span class="ms-2">{{ trans('finance::finance.refused_catch_checks') }}</span>
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
        'finance.cash-movements.index',
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
                                         'finance.cash-movements.index',
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
                <span class="ms-2"> {{ trans('finance::finance.cash-movement') }} </span>
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
        'finance.profit-loss.index',
        'finance.audit-budget-supplier.index',
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
                                         'finance.profit-loss.index',
                                         'finance.audit-budget-supplier.index',
                                         'finance.trail-balance.index',
                                         'finance.audit-budget-monthly.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="Financial-Lists">

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
<li class="collapsed">
    <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'finance.double-account.index',
        'finance.double-account-statement.index',
    ]) &&
    (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
        ? 'active'
        : '' }}"
        data-bs-toggle="collapse" data-bs-target="#double-account-statement" href="#">
        <i class="fa fa-link"></i>
        <span class="ms-2">{{ __('finance::finance.double_accounts') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>

    <ul class="sub-menu collapse
                                     {{ in_array(Route::currentRouteName(), [
                                         'finance.double-account.index',
                                         'finance.double-account-statement.index',
                                     ]) &&
                                     (request()->query('status') == null || in_array(request()->query('status'), ['collected', 'refused']))
                                         ? 'show'
                                         : '' }}"
        id="double-account-statement">
        {{--        @if (in_array('finance.double-account-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.double-account.index' ? 'active' : '' }}"
                href="{{ route('finance.double-account.index') }}">
                <span class="ms-2"> {{ __('finance::finance.double_accounts') }} </span>
            </a>
        </li>
        {{--        @endif --}}
        {{--        @if (in_array('finance.double-account-statement-read', $user_permission_list)) --}}
        <li>
            <a class="m-link {{ Route::currentRouteName() == 'finance.double-account-statement.index' ? 'active' : '' }}"
                href="{{ route('finance.double-account-statement.index') }}">
                <span class="ms-2"> {{ __('finance::finance.double-account-statement') }} </span>
            </a>
        </li>
        {{--        @endif --}}

    </ul>
</li>
