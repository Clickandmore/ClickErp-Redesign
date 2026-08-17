<li class="collapsed">
    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#zatca" href="#">
        <i class="fa fa-plug me-2"></i>
        <span class="ms-2">{{ __('finance::zatca.zatca api setting') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
            'zatca.zatca-settings.index','finance.bill-sales.index'
            ]) ? 'show' : '' }}"
        id="zatca">
        <!-- Submenu items go here -->
{{--        @if (in_array('zatca.zatca-settings-read', $user_permission_list))--}}
                <li>
                    <a class="m-link {{ (Route::currentRouteName() == 'finance.zatca-settings.index') ? 'active' : '' }}"
                       href="{{ route('zatca.zatca-settings.index') }}">
                        <i class="fa fa-cogs me-2"></i>
                        <span class="ms-2">{{ __("finance::zatca.Zatca setting") }}</span>
                    </a>
                </li>

                <li>
                    <a class="m-link {{ (Route::currentRouteName() == 'finance.bill-sales.index' && request()->query('shared') === '1') ? 'active' : '' }}"
                       href="{{ route('finance.bill-sales.index', ['shared' => true]) }}">
                        <i class="fa fa-check-circle me-2 text-success"></i>
                        <span class="ms-2">{{ __("finance::zatca.Shared Invoices") }}</span>
                    </a>
                </li>

                <li>
                    <a class="m-link {{ (Route::currentRouteName() == 'finance.bill-sales.index' && request()->query('shared') === '0') ? 'active' : '' }}"
                       href="{{ route('finance.bill-sales.index', ['shared' => false]) }}">
                        <i class="fa fa-exclamation-triangle me-2 text-warning"></i>
                        <span class="ms-2">{{ __("finance::zatca.Pending Invoices") }}</span>
                    </a>
                </li>

        {{--        @endif--}}

        {{--        <li>--}}
        {{--            <a class="m-link {{ (Route::currentRouteName() == 'finance.zatca-settings.index') ? 'active' : '' }}"--}}
        {{--               href="{{ route('zatca.zatca-settings.index') }}">--}}
        {{--                <i class="far fa-share"></i>--}}
        {{--                <span class="ms-2">{{ __("zatca.share invoice with zatca") }}</span>--}}
        {{--            </a>--}}
        {{--        </li>--}}
    </ul>
</li>
