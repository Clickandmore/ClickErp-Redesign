@php
    $isCommissionOperations =
        request()->routeIs('commissions.marketers.index') ||
        request()->routeIs('commissions.marketers.show') ||
        request()->routeIs('commissions.marketers.preview') ||
        request()->routeIs('commissions.marketers.approve') ||
        request()->routeIs('commissions.settings.*');
@endphp

@if (
    @SystemModuleEnabled('commissions') &&
        @AdminGuardHasAnyPerm([
            \App\PermKeys::COMMISSIONS_VIEW,
            \App\PermKeys::COMMISSIONS_CALCULATE,
            \App\PermKeys::COMMISSIONS_SETTINGS,
        ]))
    <li class="{{ $isCommissionOperations ? '' : 'collapsed' }}">
        <a class="m-link" data-bs-toggle="collapse" data-bs-target="#commission_operations_menu" href="#">
            <i class="fa fa-percent"></i>
            <span class="ms-2">عمولات المسوقين</span>
            <span class="arrow fa fa-angle-left ms-auto text-end"></span>
        </a>
        <ul class="sub-menu collapse {{ $isCommissionOperations ? 'show' : '' }}" id="commission_operations_menu">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::COMMISSIONS_VIEW, \App\PermKeys::COMMISSIONS_CALCULATE]))
                <li>
                    <a class="m-link {{ request()->routeIs('commissions.marketers.index') || request()->routeIs('commissions.marketers.show') || request()->routeIs('commissions.marketers.preview') || request()->routeIs('commissions.marketers.approve') ? 'active' : '' }}"
                        href="{{ route('commissions.marketers.index') }}">
                        <i class="fa fa-clipboard-check"></i>
                        <span class="ms-2">مراجعة واعتماد العمولات</span>
                    </a>
                </li>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_SETTINGS))
                <li>
                    <a class="m-link {{ request()->routeIs('commissions.settings.*') ? 'active' : '' }}"
                        href="{{ route('commissions.settings.index') }}">
                        <i class="fa fa-sliders-h"></i>
                        <span class="ms-2">قواعد ونسب العمولات</span>
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif
