<?php
$permissions = [
    'finance.stock-settings.index',
    'finance.stock-movement.index',
    'finance.stock-movement.create',
    'finance.sarf-movement.index',
    'finance.sarf-movement.create',
    'finance.bill-sales.index',
    'finance.bill-sales-report.index',
    'finance.bill-purchases.index',
    'finance.bill-purchases.create',
    'finance.bill-purchases.show',
    'finance.back-purchases.index',
    'finance.back-purchases.show',
    'finance.back-purchases.create',
    'finance.bill-sales.create',
    'finance.units.index',
    'finance.categories.index',
    'finance.product-num-settings.index',
    'finance.stocks.index',
    'finance.first-stocks.index',
    'finance.categories.index',
    'finance.transfer-sales-order.edit',
    'finance.transfer-purchase-order.edit',
    'finance.product-num-settings.index',
    'finance.items.index',
    'finance.items.create',
    'finance.inventories.index',
    'finance.sales-order.index',
    'finance.store-balances.index',
    'finance.purchase-orders.index',
    'finance.item-purchase-movement.index',
    'finance.item-sale-movement.index',
    'finance.item-collects.index',
    'finance.missing-items.index',
    'finance.total-item-purchases.index',
    'finance.item-total-sale.index',
    'finance.add-conversion-orders.index',
    'finance.items-import.create',
];
?>
<li class="collapsed">
    <a class="m-link" data-bs-toggle="collapse" data-bs-target="#stock" href="#">
        <i class="fa fa-boxes me-2"></i>
        <span class="ms-2">{{ __('finance::finance.stock-side') }}</span>
        <span class="arrow fa fa-angle-left ms-auto text-end"></span>
    </a>
    <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), $permissions) ? 'show' : '' }}"
        id="stock">

        <li class="collapsed">
            <a class="m-link
    {{ in_array(Route::currentRouteName(), [
        'finance.bill-purchases.index',
        'finance.bill-purchases.create',
        'finance.bill-purchases.show',
        'finance.back-purchases.index',
        'finance.back-purchases.create',
        'finance.back-purchases.show',
        'finance.transfer-purchase-order.edit',
        'finance.purchase-orders.index',
        'finance.missing-items.index',
        'finance.item-purchase-movement.index',
        'finance.total-item-purchases.index',
    ])
        ? 'active'
        : '' }}"
                data-bs-toggle="collapse" data-bs-target="#stock-purchases" href="#">
                <i class="fa fa-shopping-cart me-2"></i>
                <span class="ms-2">{{ __('finance::finance.purchases') }}</span>
                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
            </a>
            <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.bill-purchases.index',
                'finance.bill-purchases.create',
                'finance.bill-purchases.show',
                'finance.back-purchases.index',
                'finance.back-purchases.create',
                'finance.back-purchases.show',
                'finance.transfer-purchase-order.edit',
                'finance.purchase-orders.index',
                'finance.missing-items.index',
                'finance.item-purchase-movement.index',
                'finance.total-item-purchases.index',
            ])
                ? 'show'
                : '' }}"
                id="stock-purchases">
                {{--                @if (in_array('finance.bill-purchases-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['finance.bill-purchases.index', 'finance.bill-purchases.create', 'finance.bill-purchases.show']) ? 'active' : '' }}"
                        href="{{ route('finance.bill-purchases.index') }}">
                        <span class="ms-2"> {{ __('finance::finance.bill_purchases') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.back-purchases-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['finance.back-purchases.index', 'finance.back-purchases.create', 'finance.back-purchases.show']) ? 'active' : '' }}"
                        href="{{ route('finance.back-purchases.index') }}">
                        <span class="ms-2"> {{ __('finance::finance.back_purchases') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.purchase-orders-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link  {{ Route::currentRouteName() == 'finance.purchase-orders.index' || Route::currentRouteName() == 'finance.transfer-purchase-order.edit' ? 'active' : '' }}"
                        href="{{ route('finance.purchase-orders.index') }}">

                        <span class="ms-2">{{ __('finance::finance.purchase_orders') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.missing-items-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.missing-items.index' ? 'active' : '' }}"
                        href="{{ route('finance.missing-items.index') }}">

                        <span class="ms-2">{{ __('finance::finance.missing-items') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.item-purchase-movement-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.item-purchase-movement.index' ? 'active' : '' }}"
                        href="{{ route('finance.item-purchase-movement.index') }}">

                        <span class="ms-2">{{ __('finance::finance.item-purchase-movement') }}</span>

                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.total-item-purchases-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.total-item-purchases.index' ? 'active' : '' }}"
                        href="{{ route('finance.total-item-purchases.index') }}">

                        <span class="ms-2">{{ __('finance::finance.total-item-purchases') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
            </ul>
        </li>

        <li class="collapsed">
            <a class="m-link
                        {{ in_array(Route::currentRouteName(), [
                            'finance.sales-order.index',
                            'finance.bill-sales.index',
                            'finance.bill-sales-report.index',
                            'finance.bill-sales.create',
                            'finance.transfer-sales-order.edit',
                            'finance.item-sale-movement.index',
                            'finance.item-total-sale.index',
                        ])
                            ? 'active'
                            : '' }}"
                data-bs-toggle="collapse" data-bs-target="#stock-sales" href="#">
                <i class="fa fa-shopping-basket me-2"></i>
                <span class="ms-2">{{ __('finance::finance.sales') }}</span>
                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
            </a>

            <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.sales-order.index',
                'finance.bill-sales.index',
                'finance.bill-sales-report.index',
                'finance.bill-sales.create',
                'finance.transfer-sales-order.edit',
                'finance.item-sale-movement.index',
                'finance.item-total-sale.index',
            ])
                ? 'show'
                : '' }}"
                id="stock-sales">

                {{--                @if (in_array('finance.bill-sales-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['finance.bill-sales.index', 'finance.bill-sales.create']) ? 'active' : '' }}"
                        href="{{ route('finance.bill-sales.index') }}">

                        <span class="ms-2">{{ __('finance::finance.bill-sales') }}</span>
                    </a>
                </li>
                {{--                @endif --}}

                {{--                @if (in_array('finance.sales-order-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.sales-order.index' || Route::currentRouteName() == 'finance.transfer-sales-order.edit' ? 'active' : '' }}"
                        href="{{ route('finance.sales-order.index') }}">

                        <span class="ms-2">{{ __('finance::finance.sales-order') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.item-sale-movement-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.item-sale-movement.index' ? 'active' : '' }}"
                        href="{{ route('finance.item-sale-movement.index') }}">

                        <span class="ms-2">{{ __('finance::finance.item-sale-movement') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.item-total-sale-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.item-total-sale.index' ? 'active' : '' }}"
                        href="{{ route('finance.item-total-sale.index') }}">

                        <span class="ms-2">{{ __('finance::finance.item-total-sale') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.bill-sales-report-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['finance.bill-sales-report.index']) ? 'active' : '' }}"
                        href="{{ route('finance.bill-sales-report.index') }}">

                        <span class="ms-2">{{ __('finance::finance.bill-sales-report') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
            </ul>
        </li>

        <li class="collapsed">
            <a class="m-link
                    {{ in_array(Route::currentRouteName(), [
                        'finance.product-num-settings.index',
                        'finance.units.index',
                        'finance.categories.index',
                        'finance.items.index',
                        'finance.items-import.create',
                    ])
                        ? 'active'
                        : '' }}"
                data-bs-toggle="collapse" data-bs-target="#stock-items" href="#">
                <i class="fa fa-shopping-bag me-2"></i>
                <span class="ms-2">{{ __('finance::finance.items') }}</span>
                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
            </a>

            <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.product-num-settings.index',
                'finance.units.index',
                'finance.categories.index',
                'finance.items.index',
                'finance.items-import.create',
            ])
                ? 'show'
                : '' }}"
                id="stock-items">
                {{--                @if (in_array('finance.units-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.units.index' ? 'active' : '' }}"
                        href="{{ route('finance.units.index') }}">
                        <span class="ms-2">{{ __('finance::finance.units') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.categories-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.categories.index' ? 'active' : '' }}"
                        href="{{ route('finance.categories.index') }}">
                        <span class="ms-2">{{ __('finance::finance.categories') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.product-num-settings-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.product-num-settings.index' ? 'active' : '' }}"
                        href="{{ route('finance.product-num-settings.index') }}">
                        <span class="ms-2">{{ __('finance::finance.product-num-settings') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.items-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ in_array(Route::currentRouteName(), ['finance.items.index', 'finance.items.create', 'finance.items-import.create']) ? 'active' : '' }}"
                        href="{{ route('finance.items.index') }}">
                        <span class="ms-2">{{ __('finance::finance.items') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
            </ul>
        </li>

        <li class="collapsed">
            <a class="m-link
                {{ in_array(Route::currentRouteName(), [
                    'finance.stock-settings.index',
                    'finance.stock-movement.index',
                    'finance.sarf-movement.index',
                    'finance.stocks.index',
                    'finance.inventories.index',
                    'finance.conversions.index',
                    'finance.store-balances.index',
                    'finance.first-stocks.index',
                    'finance.item-collects.index',
                    'finance.add-conversion-orders.index',
                ])
                    ? 'active'
                    : '' }}"
                data-bs-toggle="collapse" data-bs-target="#stock-storages" href="#">
                <i class="fa fa-archive me-2"></i>
                <span class="ms-2">{{ __('finance::finance.storages') }}</span>
                <span class="arrow fa fa-angle-left ms-auto text-end"></span>
            </a>

            <ul class="sub-menu collapse
            {{ in_array(Route::currentRouteName(), [
                'finance.stock-settings.index',
                'finance.stock-movement.index',
                'finance.sarf-movement.index',
                'finance.stocks.index',
                'finance.inventories.index',
                'finance.conversions.index',
                'finance.store-balances.index',
                'finance.first-stocks.index',
                'finance.item-collects.index',
                'finance.add-conversion-orders.index',
            ])
                ? 'show'
                : '' }}"
                id="stock-storages">

                {{--                @if (auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.stock-settings.index' ? 'active' : '' }}"
                        href="{{ route('finance.stock-settings.index') }}">
                        <span class="ms-2">{{ __('finance::finance.Stock settings') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.stock-movement-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.stock-movement.index' ? 'active' : '' }}"
                        href="{{ route('finance.stock-movement.index') }}">
                        <span class="ms-2">{{ __('finance::finance.Additional Permits') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.sarf-movement-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.sarf-movement.index' ? 'active' : '' }}"
                        href="{{ route('finance.sarf-movement.index') }}">
                        <span class="ms-2">{{ __('finance::finance.Sarf Permits') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.stocks-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.stocks.index' ? 'active' : '' }}"
                        href="{{ route('finance.stocks.index') }}">
                        <span class="ms-2">{{ __('finance::finance.stocks') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.inventories-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.inventories.index' ? 'active' : '' }}"
                        href="{{ route('finance.inventories.index') }}">
                        <span class="ms-2">{{ __('finance::finance.inventories') }}</span>

                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.conversions-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.conversions.index' ? 'active' : '' }}"
                        href="{{ route('finance.conversions.index') }}">
                        <span class="ms-2">{{ __('finance::finance.Transfer between stores') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.store-balances-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.store-balances.index' ? 'active' : '' }}"
                        href="{{ route('finance.store-balances.index') }}">
                        <span class="ms-2">{{ __('finance::finance.store-balances') }}</span>


                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.first-stocks-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.first-stocks.index' ? 'active' : '' }}"
                        href="{{ route('finance.first-stocks.index') }}">
                        <span class="ms-2">{{ __('finance::finance.first-stocks') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.add-conversion-orders-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.add-conversion-orders.index' ? 'active' : '' }}"
                        href="{{ route('finance.add-conversion-orders.index') }}">
                        <span class="ms-2">{{ __('finance::finance.add-conversion-orders') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
                {{--                @if (in_array('finance.item-collects-read', $user_permission_list)) --}}
                <li>
                    <a class="m-link {{ Route::currentRouteName() == 'finance.item-collects.index' ? 'active' : '' }}"
                        href="{{ route('finance.item-collects.index') }}">
                        {{-- <i class="fa fa-tape"></i> --}}
                        <span class="ms-2">{{ __('finance::finance.item-collects') }}</span>
                    </a>
                </li>
                {{--                @endif --}}
            </ul>
        </li>


        {{--        <li> --}}
        {{--            <a class="m-link {{ (Route::currentRouteName() == 'finance.item-total-sale.index') ? 'active' : '' }}" --}}
        {{--               href="{{ route('finance.item-total-sale.index') }}"> --}}
        {{--                <i class="fa fa-shopping-cart"></i> --}}
        {{--                <span class="ms-2">{{ __("finance.item-total-sale") }}</span> --}}
        {{--            </a> --}}
        {{--        </li> --}}

    </ul>
</li>
{{-- @include('click.layout.inc.sidebar.zatca') --}}
