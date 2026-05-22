@extends('admin.layout.indexs.index')
@section("style")
    <style>
        #dataTableObject .actions-dropdown {
            position: static !important;
        }

        .packages-actions-floating-menu {
            position: fixed !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 1021 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
            min-width: 230px;
            padding: 0.45rem !important;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            background: #fff;
            direction: rtl;
        }

        .packages-actions-floating-menu .dropdown-item {
            width: 100% !important;
            height: auto !important;
            min-height: 38px;
            display: flex !important;
            align-items: center;
            gap: 0.55rem;
            justify-content: flex-start;
            padding: 0.55rem 0.75rem !important;
            margin: 0 !important;
            border: 0 !important;
            border-radius: 6px;
            background: transparent !important;
            color: #1f2937 !important;
            font-size: 0.88rem;
            font-weight: 600;
            line-height: 1.35;
            text-align: right !important;
            white-space: nowrap;
            box-shadow: none !important;
        }

        .packages-actions-floating-menu .dropdown-item:hover,
        .packages-actions-floating-menu .dropdown-item:focus {
            background: #f3f6fb !important;
            color: #0d6efd !important;
        }

        .packages-actions-floating-menu .dropdown-item i {
            width: 20px;
            min-width: 20px;
            text-align: center;
            margin: 0 !important;
            color: inherit;
        }

        .packages-actions-floating-menu .dropdown-item.text-danger,
        .packages-actions-floating-menu .dropdown-item.text-danger i {
            color: #dc3545 !important;
        }

        .packages-actions-floating-menu .dropdown-item.text-danger:hover,
        .packages-actions-floating-menu .dropdown-item.text-danger:focus {
            background: #fff1f2 !important;
            color: #b42318 !important;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.packages")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
        {!! addButton(route("packages.create") ,helperTrans("ingaz.packages")) !!}
   @endif
{{--    <p>{{helperTrans('ingaz.click in the show button to change appearance status')}}</p>--}}
    <table id="dataTableObject" class="table display dataTable table-hover table-style" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.nationality")}}</th>
            <th>الترتيب</th>
            <th>نوع الإيجار</th>
            <th>المدة</th>
            <th>السعر بدون الضريبة</th>
            <th>الضريبة</th>
            <th>الإجمالي بعد الضريبة</th>
            <th>العرض على الباقة</th>
            <th>{{helperTrans("ingaz.days_count")}}</th>
            <th>الظهور</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

@else
    @include('components.no_permissions')
@endif


@endsection
@section('js')
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "nationality", orderable: false, searchable: true},
                {"data": "sort_order", orderable: true, searchable: true},
                {"data": "rental_type", orderable: true, searchable: true},
                {"data": "duration", orderable: true, searchable: true},
                {"data": "original_price", orderable: true, searchable: true},
                {"data": "vat_amount", orderable: true, searchable: true},
                {"data": "price_with_tax", orderable: true, searchable: true},
                {"data": "offer", orderable: false, searchable: false},
                {"data": "days_count", orderable: true, searchable: true},
                {"data": "is_show", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("packages.index")}}", columns);
        });

        function closePackagesActionsDropdown() {
            $('#packagesActionsFloatingMenu').remove();

            $('#dataTableObject .actions-dropdown').each(function () {
                $(this).removeClass('show');
                $(this).find('.actions-btn').attr('aria-expanded', 'false').removeClass('show');
                $(this).find('.dropdown-menu').removeClass('show').removeAttr('style');
            });
        }

        function placePackagesActionsMenu($button, $menu) {
            const rect = $button[0].getBoundingClientRect();
            const menuWidth = Math.max($menu.outerWidth() || 220, 220);
            const viewportPadding = 12;
            const fixedHeaderBottom = Array.from(document.querySelectorAll('header, .header, .topbar, .navbar, .main-header, .page-header'))
                .map((element) => {
                    const style = window.getComputedStyle(element);
                    const box = element.getBoundingClientRect();

                    if (!['fixed', 'sticky'].includes(style.position) || box.height < 20 || box.top > 10) {
                        return 0;
                    }

                    return box.bottom;
                })
                .reduce((max, bottom) => Math.max(max, bottom), 0);
            const minTop = fixedHeaderBottom ? fixedHeaderBottom + 8 : viewportPadding;
            let top = Math.max(minTop, rect.bottom + 6);
            let maxHeight = window.innerHeight - top - viewportPadding;

            if (maxHeight < 140) {
                maxHeight = 140;
                top = Math.max(minTop, window.innerHeight - maxHeight - viewportPadding);
            }

            const left = Math.max(
                viewportPadding,
                Math.min(window.innerWidth - viewportPadding - menuWidth, rect.right - menuWidth)
            );

            $menu.css({
                top: top + 'px',
                left: left + 'px',
                maxHeight: maxHeight + 'px',
                minWidth: menuWidth + 'px'
            });
        }

        $(document).on('click', '#dataTableObject .actions-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const $button = $(this);
            const $dropdown = $button.closest('.actions-dropdown');
            const $sourceMenu = $dropdown.find('.dropdown-menu').first();
            const isOpen = $button.attr('aria-expanded') === 'true';

            closePackagesActionsDropdown();

            if (isOpen) {
                return;
            }

            const $menu = $('<ul id="packagesActionsFloatingMenu" class="dropdown-menu shadow-lg show packages-actions-floating-menu"></ul>');
            $menu.html($sourceMenu.html());
            $('body').append($menu);

            $dropdown.addClass('show');
            $button.attr('aria-expanded', 'true').addClass('show');
            placePackagesActionsMenu($button, $menu);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#dataTableObject .actions-dropdown, #packagesActionsFloatingMenu').length) {
                closePackagesActionsDropdown();
            }
        });

        $(window).on('scroll resize', function () {
            closePackagesActionsDropdown();
        });

        $(document).on('scroll', '#dataTableObject_wrapper', function () {
            closePackagesActionsDropdown();
        });

        $(document).on('click', '#packagesActionsFloatingMenu .dropdown-item', function () {
            setTimeout(closePackagesActionsDropdown, 120);
        });

        $(document).on('click', '.package-toggle-status', function () {
            $.ajax({
                type: 'GET',
                url: $(this).data('url'),
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تحديث حالة الباقة بنجاح',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    }
                }
            });
        });

    </script>
@endsection
