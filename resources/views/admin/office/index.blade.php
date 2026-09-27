@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .office-index-page {
            padding: 1rem 0 2rem;
        }

        .office-index-hero,
        .office-stat-card,
        .office-filter-card,
        .office-table-card {
            border: 1px solid #edf2f7;
            border-radius: 1.15rem;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .office-index-hero {
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.10), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
            border: 0;
        }

        .office-stat-card {
            background: #fff;
            height: 100%;
        }

        .office-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            background: rgba(13, 110, 253, 0.10);
            color: #0d6efd;
        }

        .office-stat-label {
            color: #6b7280;
            font-size: .88rem;
            font-weight: 700;
            margin-bottom: .45rem;
        }

        .office-stat-value {
            color: #111827;
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .office-card-head {
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
        }

        .office-card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin: 0;
        }

        .office-filter-body,
        .office-table-body {
            padding: 1.35rem;
        }

        .office-filter-card.is-collapsed .office-filter-body {
            display: none;
        }

        .office-filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid #d7dee8;
            background: #fff;
            color: #1f2937;
            border-radius: .85rem;
            padding: .55rem .9rem;
            font-weight: 700;
            transition: .2s ease;
        }

        .office-filter-toggle:hover {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .office-filter-toggle i {
            transition: transform .2s ease;
        }

        .office-filter-card.is-collapsed .office-filter-toggle i {
            transform: rotate(-180deg);
        }

        .office-filter-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
        }

        .office-filter-field label {
            display: block;
            margin-bottom: .45rem;
            font-size: .9rem;
            font-weight: 700;
            color: #374151;
        }

        .office-filter-field .form-control,
        .office-filter-field .form-select {
            min-height: 44px;
            border-radius: .85rem;
            border-color: #d7dee8;
            box-shadow: none;
        }

        .office-filter-field .form-control:focus,
        .office-filter-field .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, 0.12);
        }

        .office-filter-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .office-filter-buttons {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .office-filter-buttons .btn {
            min-height: 44px;
            border-radius: .85rem;
            font-weight: 700;
        }

        .office-table-body .dataTables_filter {
            display: none;
        }

        .office-table-body .dataTables_length {
            margin-bottom: 1rem;
        }

        .office-table-body .dataTables_info {
            padding-top: 1rem !important;
        }

        .office-table-body table.dataTable thead th {
            white-space: nowrap;
            font-size: .9rem;
            font-weight: 800;
            color: #374151;
        }

        .office-table-body table.dataTable tbody td {
            vertical-align: middle;
        }

        .office-index-page .office-table-card,
        .office-index-page .office-table-body,
        .office-index-page .dataTables_wrapper,
        .office-index-page .table-responsive {
            overflow: visible;
        }

        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            min-width: 160px;
            text-align: center;
            white-space: nowrap;
        }

        #dataTableObject .actions-dropdown {
            position: relative;
            display: inline-flex;
            justify-content: center;
        }

        #dataTableObject .actions-dropdown .actions-btn {
            min-width: 128px;
            border-radius: .65rem;
            font-weight: 700;
        }

        #dataTableObject .office-actions-template {
            display: none !important;
        }

        .office-actions-menu {
            position: fixed;
            width: 240px;
            max-width: calc(100vw - 24px);
            max-height: min(430px, calc(100vh - 24px));
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .18);
            padding: .45rem;
            margin: 0;
            list-style: none;
            z-index: 12000;
        }

        .office-actions-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: .55rem;
            width: 100%;
            padding: .65rem .8rem;
            border: 0;
            border-radius: .55rem;
            background: transparent;
            color: #1f2937;
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .office-actions-menu .dropdown-item:hover,
        .office-actions-menu .dropdown-item:focus {
            background: #f3f6fb;
            color: #0d6efd;
        }

        .office-name-cell {
            font-weight: 700;
            color: #111827;
        }
        .manager-badge-wrapper {
            display: inline-block;
            margin-top: 6px;
            vertical-align: middle;
        }

        .office-number-cell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            padding: .3rem .65rem;
            border-radius: 999px;
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
        }

        .related-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            padding: .35rem .7rem;
            border-radius: 999px;
            font-weight: 800;
            text-decoration: none;
            transition: .2s ease;
        }

        .linked-cvs-link {
            background: rgba(13, 110, 253, 0.10);
            color: #0d6efd;
        }

        .linked-contracts-link {
            background: rgba(25, 135, 84, 0.10);
            color: #198754;
        }

        .cvs-suspended-wrapper {
            margin-top: .35rem;
        }

        .cvs-suspended-wrapper .badge {
            font-size: .72rem;
            font-weight: 700;
        }

        .related-count-badge.is-empty {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: default;
        }

        .linked-cvs-link:hover {
            background: #0d6efd;
            color: #fff;
            text-decoration: none;
        }

        .linked-contracts-link:hover {
            background: #198754;
            color: #fff;
            text-decoration: none;
        }

        .dropdown-menu.dropdown-menu-end.office-actions-floating-menu {
            position: fixed !important;
            top: var(--office-actions-top) !important;
            left: var(--office-actions-left) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 2147483647 !important;
            right: auto !important;
            transform: none !important;
            min-width: 220px;
            max-height: min(420px, calc(100vh - 24px));
            overflow-y: auto;
            text-align: right;
        }

        @media (max-width: 1199px) {
            .office-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .office-filter-grid {
                grid-template-columns: 1fr;
            }

            .office-filter-actions {
                align-items: stretch;
            }

            .office-filter-buttons,
            .office-filter-buttons .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.offices') }}
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::REC_OFFICES_VIEW, \App\PermKeys::OFFICE_VIEW, \App\PermKeys::JOB_TITLES_VIEW]))
        <div class="container-fluid office-index-page">
            <div class="office-index-hero card mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <span class="badge bg-light text-primary border border-primary mb-3">{{ helperTrans('ingaz.offices') }}</span>
                            <h3 class="mb-2">{{ helperTrans('ingaz.offices') }}</h3>
                            <p class="text-muted mb-0">{{ helperTrans('ingaz.filters') }} ، {{ helperTrans('ingaz.status') }} ، {{ helperTrans('ingaz.linked_cvs') }} ، {{ helperTrans('ingaz.linked_contracts') }}</p>
                        </div>

                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::REC_OFFICES_CONTROL, \App\PermKeys::JOB_TITLES_CONTROL]))
                            <div>
                                <a href="{{ route('offices.create') }}" class="btn btn-primary btn-lg rounded-pill px-4">
                                    <i class="fa fa-plus-circle ms-1"></i>
                                    {{ helperTrans('ingaz.add_office') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="office-stat-card card">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="office-stat-label">{{ helperTrans('ingaz.total_offices') }}</div>
                                <div class="office-stat-value">{{ $stats['total'] ?? 0 }}</div>
                            </div>
                            <span class="office-stat-icon"><i class="fa fa-building"></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="office-stat-card card">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="office-stat-label">{{ helperTrans('ingaz.active_offices') }}</div>
                                <div class="office-stat-value">{{ $stats['active'] ?? 0 }}</div>
                            </div>
                            <span class="office-stat-icon"><i class="fa fa-check-circle"></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="office-stat-card card">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="office-stat-label">{{ helperTrans('ingaz.total_linked_cvs') }}</div>
                                <div class="office-stat-value">{{ $stats['linked_cvs'] ?? 0 }}</div>
                            </div>
                            <span class="office-stat-icon"><i class="fa fa-id-card"></i></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="office-stat-card card">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="office-stat-label">{{ helperTrans('ingaz.offices_with_linked_cvs') }}</div>
                                <div class="office-stat-value">{{ $stats['offices_with_linked_cvs'] ?? 0 }}</div>
                            </div>
                            <span class="office-stat-icon"><i class="fa fa-link"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="office-filter-card card mb-4 is-collapsed" id="officeFilterCard">
                <div class="office-card-head d-flex align-items-center justify-content-between gap-3">
                    <h5 class="office-card-title">{{ helperTrans('ingaz.filters') }}</h5>
                    <button type="button" class="office-filter-toggle" id="toggleOfficeFilters" aria-expanded="false">
                        <span id="toggleOfficeFiltersText">{{ helperTrans('ingaz.expand_filters') }}</span>
                        <i class="fa fa-angle-up"></i>
                    </button>
                </div>
                <div class="office-filter-body">
                    <div class="office-filter-grid">
                        <div class="office-filter-field">
                            <label for="officeNameFilter">{{ helperTrans('ingaz.office_name') }}</label>
                            <input type="text" id="officeNameFilter" class="form-control" placeholder="{{ helperTrans('ingaz.search') }}">
                        </div>

                        <div class="office-filter-field">
                            <label for="nationalityFilter">{{ helperTrans('ingaz.nationality') }}</label>
                            <select id="nationalityFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all_nationalities') }}</option>
                                @foreach ($nationalities as $nationality)
                                    @php($nationalityName = $nationality->sub_title ?: ($nationality->title ?: helperTrans('ingaz.not_specified')))
                                    <option value="{{ mb_strtolower($nationalityName) }}">{{ $nationalityName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="office-filter-field">
                            <label for="statusFilter">{{ helperTrans('ingaz.status') }}</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all_statuses') }}</option>
                                <option value="active">{{ helperTrans('ingaz.active') }}</option>
                                <option value="dis_active">{{ helperTrans('ingaz.dis_active') }}</option>
                            </select>
                        </div>

                        <div class="office-filter-field">
                            <label for="linkedFilter">{{ helperTrans('ingaz.linked_cvs_state') }}</label>
                            <select id="linkedFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all_linked_states') }}</option>
                                <option value="with_links">{{ helperTrans('ingaz.has_linked_cvs') }}</option>
                                <option value="without_links">{{ helperTrans('ingaz.without_linked_cvs') }}</option>
                            </select>
                        </div>

                        <div class="office-filter-field">
                            <label for="costFilter">{{ helperTrans('ingaz.cost_state') }}</label>
                            <select id="costFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all_cost_states') }}</option>
                                <option value="with_cost">{{ helperTrans('ingaz.has_cost_of_dollar') }}</option>
                                <option value="without_cost">{{ helperTrans('ingaz.without_cost_of_dollar') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="office-filter-actions">
                        <div class="text-muted small">
                            {{ helperTrans('ingaz.search') }} / {{ helperTrans('ingaz.clear_filters') }}
                        </div>

                        <div class="office-filter-buttons">
                            <button type="button" id="applyOfficeFilters" class="btn btn-primary">
                                <i class="fa fa-filter ms-1"></i>
                                {{ helperTrans('ingaz.search') }}
                            </button>
                            <button type="button" id="resetOfficeFilters" class="btn btn-outline-secondary">
                                <i class="fa fa-rotate-right ms-1"></i>
                                {{ helperTrans('ingaz.clear_filters') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="office-table-card card">
                <div class="office-card-head">
                    <h5 class="office-card-title">{{ helperTrans('ingaz.offices') }}</h5>
                </div>
                <div class="office-table-body">
                    <div class="table-responsive">
                        <table id="dataTableObject" class="table table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ helperTrans('ingaz.title') }}</th>
                                    <th>{{ helperTrans('ingaz.nationality') }}</th>
                                    <th>{{ __('finance::finance.account_code') }}</th>
                                    <th>{{ helperTrans('ingaz.linked_cvs') }}</th>
                                    <th>{{ helperTrans('ingaz.linked_contracts') }}</th>
                                    <th>{{ helperTrans('ingaz.cost_of_dollar') }}</th>
                                    <th>{{ helperTrans('ingaz.status') }}</th>
                                    <th>{{ helperTrans('ingaz.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <select id="officeDeleteReplacementOptions" class="d-none" aria-hidden="true">
                <option value="">اختر المكتب البديل</option>
                @foreach ($deleteReplacementOffices as $deleteReplacementOffice)
                    <option value="{{ $deleteReplacementOffice->id }}" data-nationality-id="{{ (int) ($deleteReplacementOffice->nationality_id ?? 0) }}">{{ $deleteReplacementOffice->title ?? ('مكتب #' . $deleteReplacementOffice->id) }}</option>
                @endforeach
            </select>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        let officeTable;

        function stripHtml(value) {
            return $('<div>').html(value || '').text().trim().toLowerCase();
        }

        function closeOfficeActionsMenu() {
            $('#officeActionsMenu').remove();
            $('#dataTableObject .office-actions-btn').attr('aria-expanded', 'false');
        }

        $(document).ready(function() {
            function closeOfficeActionsDropdown() {
                $('#officeActionsFloatingMenu').remove();

                $('#dataTableObject .actions-dropdown').each(function() {
                    $(this).removeClass('show');
                    $(this).find('.actions-btn').attr('aria-expanded', 'false').removeClass('show');
                    $(this).find('.dropdown-menu').removeClass('show').removeAttr('style');
                });
            }

            function placeOfficeActionsMenu($button, $menu) {
                const rect = $button[0].getBoundingClientRect();
                const menuWidth = Math.max($menu.outerWidth() || 220, 220);
                const menuHeight = Math.max($menu.outerHeight() || 240, 160);
                const viewportPadding = 12;
                const topSpace = rect.top - viewportPadding;
                const bottomSpace = window.innerHeight - rect.bottom - viewportPadding;
                const openUp = bottomSpace < menuHeight && topSpace > bottomSpace;
                const top = openUp
                    ? Math.max(viewportPadding, rect.top - menuHeight - 6)
                    : Math.max(
                        viewportPadding,
                        Math.min(window.innerHeight - viewportPadding - menuHeight, rect.bottom + 6)
                    );
                const left = Math.max(
                    viewportPadding,
                    Math.min(window.innerWidth - viewportPadding - menuWidth, rect.right - menuWidth)
                );

                $menu.css({
                    '--office-actions-top': top + 'px',
                    '--office-actions-left': left + 'px',
                    right: 'auto',
                    transform: 'none',
                    zIndex: 2147483647,
                    display: 'block',
                    minWidth: menuWidth + 'px'
                });
            }

            $.fn.dataTable.ext.search.push(function(settings, data) {
                if (settings.nTable.id !== 'dataTableObject') {
                    return true;
                }

                const titleFilter = ($('#officeNameFilter').val() || '').trim().toLowerCase();
                const nationalityFilter = ($('#nationalityFilter').val() || '').trim().toLowerCase();
                const statusFilter = $('#statusFilter').val() || '';
                const linkedFilter = $('#linkedFilter').val() || '';
                const costFilter = $('#costFilter').val() || '';

                const officeName = stripHtml(data[1]);
                const nationalityName = stripHtml(data[2]);
                const linkedCount = parseInt(stripHtml(data[4]) || '0', 10);
                const costValue = stripHtml(data[6]);
                const statusRaw = data[7] || '';
                const statusValue = statusRaw.includes('data-action="dis_active"') ? 'dis_active' : (statusRaw.includes('data-action="active"') ? 'active' : '');

                if (titleFilter && !officeName.includes(titleFilter)) {
                    return false;
                }

                if (nationalityFilter && nationalityName !== nationalityFilter) {
                    return false;
                }

                if (statusFilter && statusValue !== statusFilter) {
                    return false;
                }

                if (linkedFilter === 'with_links' && linkedCount <= 0) {
                    return false;
                }

                if (linkedFilter === 'without_links' && linkedCount > 0) {
                    return false;
                }

                if (costFilter === 'with_cost' && (!costValue || costValue === '-')) {
                    return false;
                }

                if (costFilter === 'without_cost' && costValue && costValue !== '-') {
                    return false;
                }

                return true;
            });

            officeTable = $('#dataTableObject').DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                iDisplayLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "الكل"]
                ],
                processing: true,
                serverSide: false,
                ajax: "{{ route('offices.index') }}",
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return '<span class="office-number-cell">' + data + '</span>';
                        }
                    },
                    {
                        data: 'title',
                        orderable: true,
                        searchable: true,
                        render: function(data, type, row) {
                            var titleHtml = '<div class="office-name-cell">' + (data || '') + '</div>';
                            var managerHtml = row && row.manager_status ? '<div class="manager-badge-wrapper">' + row.manager_status + '</div>' : '';

                            return titleHtml + managerHtml;
                        }
                    },
                    {
                        data: 'nationality_id',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'account_code',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'linked_cvs',
                        orderable: true,
                        searchable: false,
                        render: function(data, type) {
                            if (type === 'sort' || type === 'type') {
                                return parseInt(stripHtml(data) || '0', 10);
                            }

                            return data;
                        }
                    },
                    {
                        data: 'linked_contracts',
                        orderable: true,
                        searchable: false,
                        render: function(data, type) {
                            if (type === 'sort' || type === 'type') {
                                return parseInt(stripHtml(data) || '0', 10);
                            }

                            return data;
                        }
                    },
                    {
                        data: 'cost_of_dollar',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'status',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'actions',
                        className: 'all text-nowrap',
                        responsivePriority: 1,
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    sProcessing: "{{ trans('dataTable.sProcessing') }}",
                    sLengthMenu: "{{ trans('dataTable.sLengthMenu') }}",
                    sZeroRecords: "{{ trans('dataTable.sZeroRecords') }}",
                    sInfo: "{{ trans('dataTable.sInfo') }}",
                    sInfoEmpty: "{{ trans('dataTable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ trans('dataTable.sInfoFiltered') }}",
                    sInfoPostFix: "",
                    sSearch: "{{ trans('dataTable.sSearch') }}:",
                    sUrl: "",
                    oPaginate: {
                        sFirst: "{{ trans('dataTable.sFirst') }}",
                        sPrevious: "{{ trans('dataTable.sPrevious') }}",
                        sNext: "{{ trans('dataTable.sNext') }}",
                        sLast: "{{ trans('dataTable.sLast') }}"
                    }
                },
                drawCallback: function() {
                    closeOfficeActionsMenu();
                },
                order: [
                    [1, 'asc']
                ],
                drawCallback: function() {
                    $('#dataTableObject .actions-btn').removeAttr('data-bs-toggle data-bs-auto-close data-bs-display');
                    closeOfficeActionsDropdown();
                }
            });

            $(document).on('click', '#dataTableObject .actions-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $button = $(this);
                $button.removeAttr('data-bs-toggle data-bs-auto-close data-bs-display');
                const $dropdown = $button.closest('.actions-dropdown');
                const $sourceMenu = $dropdown.find('.dropdown-menu');
                const isOpen = $button.attr('aria-expanded') === 'true';

                closeOfficeActionsDropdown();

                if (isOpen) {
                    return;
                }

                const $menu = $('<ul id="officeActionsFloatingMenu" class="dropdown-menu dropdown-menu-end shadow-lg show office-actions-floating-menu"></ul>');
                $menu.html($sourceMenu.html());
                $('body').append($menu);

                $dropdown.addClass('show');
                $button.attr('aria-expanded', 'true').addClass('show');
                placeOfficeActionsMenu($button, $menu);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dataTableObject .actions-dropdown, #officeActionsFloatingMenu').length) {
                    closeOfficeActionsDropdown();
                }
            });

            $(document).on('click', '#officeActionsFloatingMenu a.dropdown-item, #officeActionsFloatingMenu button.dropdown-item:not([type="submit"])', function() {
                setTimeout(closeOfficeActionsDropdown, 0);
            });

            $('#applyOfficeFilters').on('click', function() {
                officeTable.draw();
            });

            $('#resetOfficeFilters').on('click', function() {
                $('#officeNameFilter').val('');
                $('#nationalityFilter').val('');
                $('#statusFilter').val('');
                $('#linkedFilter').val('');
                $('#costFilter').val('');
                officeTable.draw();
            });

            $('#officeNameFilter').on('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    officeTable.draw();
                }
            });

            $('#nationalityFilter, #statusFilter, #linkedFilter, #costFilter').on('change', function() {
                officeTable.draw();
            });

            $('#toggleOfficeFilters').on('click', function() {
                const $card = $('#officeFilterCard');
                const isCollapsed = $card.toggleClass('is-collapsed').hasClass('is-collapsed');
                $(this).attr('aria-expanded', String(!isCollapsed));
                $('#toggleOfficeFiltersText').text(isCollapsed ? "{{ helperTrans('ingaz.expand_filters') }}" : "{{ helperTrans('ingaz.collapse_filters') }}");
            });
        });

        $(document).on('click', '#dataTableObject .office-actions-btn', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const $button = $(this);
            const $dropdown = $button.closest('.office-actions-dropdown');
            const $sourceMenu = $dropdown.find('.office-actions-template').first();
            const sourceId = $button.closest('tr').index();
            const existingMenu = $('#officeActionsMenu');

            if (existingMenu.length && Number(existingMenu.data('source-id')) === Number(sourceId)) {
                closeOfficeActionsMenu();
                return;
            }

            closeOfficeActionsMenu();

            const menu = $('<ul id="officeActionsMenu" class="office-actions-menu"></ul>').html($sourceMenu.html());
            menu.data('source-id', sourceId);
            $('body').append(menu);

            const rect = $button[0].getBoundingClientRect();
            const menuWidth = menu.outerWidth();
            const menuHeight = menu.outerHeight();
            const margin = 12;
            const isRtl = $('html').attr('dir') === 'rtl' || $('body').css('direction') === 'rtl';
            let top = rect.bottom + 8;
            let left = isRtl ? rect.left : rect.right - menuWidth;

            if (top + menuHeight > window.innerHeight - margin) {
                top = rect.top - menuHeight - 8;
            }

            if (top < margin) {
                top = margin;
            }

            if (left + menuWidth > window.innerWidth - margin) {
                left = window.innerWidth - menuWidth - margin;
            }

            if (left < margin) {
                left = margin;
            }

            menu.css({
                top: top + 'px',
                left: left + 'px'
            });
            $button.attr('aria-expanded', 'true');
        });

        $(document).on('click', function(event) {
            if (!$(event.target).closest('#officeActionsMenu, #dataTableObject .office-actions-dropdown').length) {
                closeOfficeActionsMenu();
            }
        });

        $(document).on('keydown', function(event) {
            if (event.key === 'Escape') {
                closeOfficeActionsMenu();
            }
        });

        $(window).on('resize scroll', closeOfficeActionsMenu);

        $(document).on('click', '#officeActionsMenu .dropdown-item', function() {
            setTimeout(closeOfficeActionsMenu, 120);
        });

        $(document).on('click', '.toggle-status', function() {
            const id = $(this).data('id');
            var url = "{{ route('offices-status.update', 'officeId') }}".replace('officeId', id);
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "PUT",
                },
                success: function() {
                    officeTable.ajax.reload(null, false);
                },
            });
        });

        $(document).on('click', '.toggleOfficeCvsSuspension', function(event) {
            event.preventDefault();

            const $button = $(this);
            const officeId = $button.data('id');
            const isSuspended = String($button.data('suspended')) === '1';
            const officeTitle = $button.data('office-title') || '{{ __('ingaz.office') }}';
            const linkedCvs = parseInt($button.data('linked-cvs') || '0', 10);
            const url = "{{ route('offices.cvs-suspension.update', 'officeId') }}".replace('officeId',
                officeId);

            Swal.fire({
                icon: 'question',
                title: isSuspended ?
                    '{{ __('ingaz.office_cvs_resume_confirm_title') }}' :
                    '{{ __('ingaz.office_cvs_suspend_confirm_title') }}',
                html: (isSuspended ?
                        '{{ __('ingaz.office_cvs_resume_confirm_text') }}' :
                        '{{ __('ingaz.office_cvs_suspend_confirm_text') }}')
                    .replace(':office', officeTitle)
                    .replace(':count', linkedCvs),
                showCancelButton: true,
                confirmButtonText: '{{ __('ingaz.confirm') }}',
                cancelButtonText: '{{ __('ingaz.cancel') }}',
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $button.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        officeTable.ajax.reload(null, false);
                        successToster('{{ __('ingaz.success') }}', response.message ||
                            '{{ __('ingaz.updated_successfully') }}');
                    },
                    error: function(xhr) {
                        validToster('{{ __('auth.an error occurred') }}', xhr.responseJSON
                            ?.message || '{{ __('ingaz.unexpected_error') }}');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
        });

        $(document).on('click', '.sendEmailResetPassword', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            var email = $btn.data('email');
            var originalHtml = $btn.html();
            var url = "{{ route('send-email-reset-password.store') }}";
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> {{ __('ingaz.sending') }}');
            $.ajax({
                url: url,
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    office_id: id,
                    email: email,
                },
                success: function(response) {
                    if (response.code && response.code === 422) {
                        validToster('{{ __('auth.an error occurred') }}', response.message);
                    } else {
                        successToster('{{ __('ingaz.success') }}', '{{ __('ingaz.mail_sent_successfully') }}');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        var json = xhr.responseJSON;
                        var allErrors = [];
                        if (json.errors) {
                            Object.values(json.errors).forEach(function(messages) {
                                allErrors = allErrors.concat(messages);
                            });
                        }
                        validToster('{{ __('auth.an error occurred') }}', allErrors.join('<br>'));
                    } else {
                        validToster('{{ __('auth.an error occurred') }}', '{{ __('ingaz.this_email_is_invalid') }}');
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $(document).on('click', '.officeDeleteButton', function(event) {
            event.preventDefault();

            const $button = $(this);
            const deleteRoute = $button.attr('delete-route');
            const officeId = String($button.data('office-id'));
            const officeTitle = $button.data('office-title') || 'هذا المكتب';
            const nationalityId = String($button.data('nationality-id') || '0');
            const linkedCvs = parseInt($button.data('linked-cvs') || '0', 10);
            const linkedContracts = parseInt($button.data('linked-contracts') || '0', 10);
            const linkedOperations = parseInt($button.data('linked-operations') || '0', 10);
            const hasLinks = linkedOperations > 0 || linkedCvs > 0 || linkedContracts > 0;
            const currentPage = $('#dataTableObject').DataTable().page();
            let replacementOfficeId = null;

            const confirmDelete = function() {
                $.ajax({
                    url: deleteRoute,
                    type: 'DELETE',
                    data: replacementOfficeId ? {
                        replacement_office_id: replacementOfficeId
                    } : {},
                    beforeSend: function() {
                        if (window.cmShowLoader) {
                            window.cmShowLoader();
                        } else {
                            $('#loading-wrapper').show();
                        }
                    },
                    complete: function() {
                        if (window.cmHideLoader) {
                            window.cmHideLoader();
                        } else {
                            $('#loading-wrapper').hide();
                        }
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{ __('buttons.Deleted successfully') }}",
                            text: response.message || 'تم حذف المكتب بنجاح',
                            confirmButtonText: "{{ __('buttons.close') }}",
                        });
                        officeTable.ajax.reload(function() {
                            $('#dataTableObject').DataTable().page(currentPage).draw('page');
                        }, false);
                    },
                    error: function(errorObj) {
                        handleErrorResponce(errorObj);
                    }
                });
            };

            if (!hasLinks) {
                Swal.fire({
                    title: "{{ __('buttons.Are You Sure') }}",
                    text: "{{ __('buttons.You Can not to rollback') }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#DD6B55',
                    confirmButtonText: "{{ __('buttons.accept') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                }).then(function(result) {
                    if (result.isConfirmed) {
                        confirmDelete();
                    }
                });

                return;
            }

            const optionsHtml = $('#officeDeleteReplacementOptions option').filter(function() {
                const optionValue = String($(this).val());
                const optionNationalityId = String($(this).data('nationality-id') || '0');

                return optionValue === '' || (optionValue !== officeId && optionNationalityId === nationalityId);
            }).map(function() {
                return '<option value="' + $('<div>').text($(this).val()).html() + '">' + $('<div>').text($(this).text()).html() + '</option>';
            }).get().join('');

            const hasReplacementOffice = $('#officeDeleteReplacementOptions option').filter(function() {
                const optionValue = String($(this).val());
                const optionNationalityId = String($(this).data('nationality-id') || '0');

                return optionValue !== '' && optionValue !== officeId && optionNationalityId === nationalityId;
            }).length > 0;

            if (!hasReplacementOffice) {
                Swal.fire({
                    icon: 'warning',
                    title: 'لا يوجد مكتب بديل مناسب',
                    text: 'لا يوجد مكتب آخر من نفس الجنسية لدمج العمليات عليه.',
                    confirmButtonText: "{{ __('buttons.close') }}",
                });

                return;
            }

            Swal.fire({
                title: 'دمج العمليات قبل الحذف',
                html: '<div class="text-start" dir="rtl">' +
                    '<p class="mb-2">المكتب <strong>' + $('<div>').text(officeTitle).html() + '</strong> عليه عمليات مرتبطة.</p>' +
                    '<p class="text-muted small mb-3">سيتم نقل السير والعقود والإرساليات وكشوفات الحساب إلى المكتب البديل قبل الحذف.</p>' +
                    '<select id="replacementOfficeId" class="form-select">' + optionsHtml + '</select>' +
                    '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DD6B55',
                confirmButtonText: 'دمج وحذف',
                cancelButtonText: "{{ __('buttons.cancel') }}",
                preConfirm: function() {
                    const value = $('#replacementOfficeId').val();

                    if (!value) {
                        Swal.showValidationMessage('يرجى اختيار المكتب البديل');
                        return false;
                    }

                    replacementOfficeId = value;
                    return true;
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    confirmDelete();
                }
            });
        });
    </script>
@endsection
