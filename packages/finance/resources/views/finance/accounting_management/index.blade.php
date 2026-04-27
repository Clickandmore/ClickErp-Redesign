@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .account-guide-panel {
            border: 1px solid #e4e9f2;
            border-radius: 18px;
            background: linear-gradient(180deg, #fbfdff 0%, #f4f7fb 100%);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        }

        .account-guide-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .account-guide-panel__title {
            margin-bottom: 6px;
            color: #1f2a37;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .account-guide-panel__subtitle {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 0.92rem;
        }

        .account-guide-panel__actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .account-guide-counter {
            min-width: 104px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid #d7e1ee;
            text-align: center;
            line-height: 1.1;
        }

        .account-guide-counter small {
            display: block;
            color: #6b7280;
            margin-bottom: 6px;
            font-size: 0.75rem;
        }

        .account-guide-counter strong {
            color: #0f766e;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .account-guide-label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 0.88rem;
        }

        .account-guide-panel .form-control,
        .account-guide-panel .input-group-text,
        .account-guide-panel .btn {
            min-height: 44px;
            border-radius: 12px;
        }

        .account-guide-panel .input-group-text {
            background: #ffffff;
            border-color: #d7e1ee;
            color: #6b7280;
        }

        .account-guide-panel .form-control {
            border-color: #d7e1ee;
            background: #ffffff;
        }

        .account-guide-panel .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
        }

        .account-guide-panel .select2-container {
            width: 100% !important;
        }

        .account-guide-panel .select2-container--default .select2-selection--single {
            min-height: 44px;
            border-radius: 12px;
            border-color: #d7e1ee;
            display: flex;
            align-items: center;
            padding: 6px 12px;
        }

        .account-guide-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            padding-left: 0;
            padding-right: 0;
        }

        .account-guide-panel .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }

        #dataTableObject_wrapper .dataTables_filter {
            display: none;
        }

        #dataTableObject tbody td {
            vertical-align: middle;
        }

        .account-guide-table-wrap {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 16px;
        }

        @media (max-width: 767.98px) {
            .account-guide-panel__actions {
                width: 100%;
            }

            .account-guide-panel__actions .btn,
            .account-guide-counter {
                width: 100%;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    <div class="account-guide-panel p-4 mb-4">
        <div class="account-guide-panel__header">
            <div>
                <h5 class="account-guide-panel__title">{{ $bladeTitle }}</h5>
                <p class="account-guide-panel__subtitle">{{ __('finance::finance.Account guide filters hint') }}</p>
            </div>

            <div class="account-guide-panel__actions">
                <div class="account-guide-counter">
                    <small>{{ __('finance::finance.Results count') }}</small>
                    <strong id="accountGuideTotal">0</strong>
                </div>

                <button type="button" class="btn btn-success addButton" data-bs-toggle="modal"
                    data-bs-target="#createModal" model-title="{{ __('buttons.add button') }} {{ $addButtonText }}"
                    model-route="{{ $createRoute }}">
                    <i class="fa fa-plus-circle"></i>
                    {{ __('buttons.add button') }} {{ $addButtonText }}
                </button>

                <button type="button" class="btn btn-outline-success" id="exportExcelBtn">
                    <i class="fa fa-file-excel-o"></i>
                    {{ trans('dataTable.ex_excel') }}
                </button>

                <button type="button" class="btn btn-outline-secondary" id="exportCsvBtn">
                    <i class="fa fa-download"></i>
                    {{ trans('dataTable.ex_csv') }}
                </button>

                <button type="button" class="btn btn-light border" id="resetFilters">
                    <i class="fa fa-refresh"></i>
                    {{ __('finance::finance.Reset filters') }}
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <label class="account-guide-label" for="keyword">{{ trans('dataTable.sSearch') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="text" id="keyword" class="form-control"
                        placeholder="{{ __('finance::finance.Search by account name or code') }}">
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="account-guide-label" for="main_account">{{ __('finance::finance.main account') }}</label>
                <select id="main_account" name="main_account" class="form-control select2 account-guide-filter">
                    <option value="">{{ __('finance::finance.All main accounts') }}</option>
                    @foreach ($main_accounts ?? [] as $main_account)
                        <option value="{{ $main_account->id }}">
                            {{ $showCodeColumn ? $main_account->code . ' - ' : '' }}{{ $main_account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="account-guide-label" for="type_filter">{{ __('finance::finance.type') }}</label>
                <select id="type_filter" name="type" class="form-control select2 account-guide-filter">
                    <option value="">{{ __('finance::finance.All account types') }}</option>
                    @foreach ($account_types ?? [] as $account_type)
                        <option value="{{ $account_type->value }}">{{ $account_type->lang() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="account-guide-label" for="status_filter">{{ __('finance::finance.status') }}</label>
                <select id="status_filter" name="status" class="form-control select2 account-guide-filter">
                    <option value="">{{ __('finance::finance.All statuses') }}</option>
                    @foreach ($account_statuses ?? [] as $account_status)
                        <option value="{{ $account_status->id }}">{{ $account_status->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-2">
                <label class="account-guide-label" for="level_filter">{{ __('finance::finance.level') }}</label>
                <select id="level_filter" name="level" class="form-control select2 account-guide-filter">
                    <option value="">{{ __('finance::finance.All levels') }}</option>
                    @foreach ($levels ?? [] as $level)
                        <option value="{{ $level }}">{{ $level }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <label for="account_name">{{ __('finance::finance.account_name') }}</label>
            <input type="text" name="account_name" id="account_name" class="form-control">
        </div>
    </div>

    <div class="account-guide-table-wrap">
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('finance::finance.SL') }}</th>
                    <th>{{ __('finance::finance.name') }}</th>
                    <th>{{ __('finance::finance.code') }}</th>
                    <th>{{ __('finance::finance.type') }}</th>
                    <th>{{ __('finance::finance.status') }}</th>
                    <th>{{ __('finance::finance.level') }}</th>
                    <th>{{ __('finance::finance.main account') }}</th>
                    <th>{{ __('finance::finance.actions') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            const exportRouteTemplate = @json($exportRouteTemplate);
            const showCodeColumn = @json($showCodeColumn);

            $('.account-guide-filter').select2({
                width: '100%'
            });

            const columns = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'code',
                    name: 'code',
                    orderable: true,
                    searchable: false,
                    visible: showCodeColumn
                },
                {
                    data: 'type',
                    name: 'type',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'level',
                    name: 'level',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'main_account',
                    name: 'parent_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ];

            const table = $('#dataTableObject').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                searching: false,
                ordering: true,
                iDisplayLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: {
                    url: "{{ $dataTableRoute }}",
                    data: function(response) {
                        const filters = getFilters();
                        response.keyword = filters.keyword;
                        response.main_account = filters.main_account;
                        response.type = filters.type;
                        response.status = filters.status;
                        response.level = filters.level;
                    }
                },
                columns: columns,
                language: {
                    sProcessing: "{{ trans('dataTable.sProcessing') }}",
                    sLengthMenu: "{{ trans('dataTable.sLengthMenu') }}",
                    sZeroRecords: "{{ trans('dataTable.sZeroRecords') }}",
                    sInfo: "{{ trans('dataTable.sInfo') }}",
                    sInfoEmpty: "{{ trans('dataTable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ trans('dataTable.sInfoFiltered') }}",
                    sSearch: "{{ trans('dataTable.sSearch') }}:",
                    sProcessing: "{{ trans('dataTable.sProcessing') }}",
                    sLengthMenu: "{{ trans('dataTable.sLengthMenu') }}",
                    sZeroRecords: "{{ trans('dataTable.sZeroRecords') }}",
                    sInfo: "{{ trans('dataTable.sInfo') }}",
                    sInfoEmpty: "{{ trans('dataTable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ trans('dataTable.sInfoFiltered') }}",
                    sSearch: "{{ trans('dataTable.sSearch') }}:",
                    oPaginate: {
                        sFirst: "{{ trans('dataTable.sFirst') }}",
                        sPrevious: "{{ trans('dataTable.sPrevious') }}",
                        sNext: "{{ trans('dataTable.sNext') }}",
                        sLast: "{{ trans('dataTable.sLast') }}"
                        sFirst: "{{ trans('dataTable.sFirst') }}",
                        sPrevious: "{{ trans('dataTable.sPrevious') }}",
                        sNext: "{{ trans('dataTable.sNext') }}",
                        sLast: "{{ trans('dataTable.sLast') }}"
                    }
                },
                dom: 'rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3"lip>',
                order: [
                    [showCodeColumn ? 2 : 1, "asc"]
                ],
                drawCallback: function() {
                    const info = this.api().page.info();
                    $('#accountGuideTotal').text(info.recordsDisplay);
                }
            });

            function getFilters() {
                return {
                    keyword: $('#keyword').val().trim(),
                    main_account: $('#main_account').val(),
                    type: $('#type_filter').val(),
                    status: $('#status_filter').val(),
                    level: $('#level_filter').val()
                };
            }

            function buildExportUrl(format) {
                const url = new URL(exportRouteTemplate.replace('__FORMAT__', format));
                const filters = getFilters();

                Object.keys(filters).forEach(function(key) {
                    if (filters[key]) {
                        url.searchParams.set(key, filters[key]);
                    }
                });

                return url.toString();
            }

            let keywordTimer = null;
            $('#keyword').on('input', function() {
                clearTimeout(keywordTimer);
                keywordTimer = setTimeout(function() {
                    table.ajax.reload();
                }, 350);
            });

            $('#main_account, #type_filter, #status_filter, #level_filter').on('change', function() {
                table.ajax.reload();
            });

            $('#resetFilters').on('click', function() {
                $('#keyword').val('');
                $('#main_account, #type_filter, #status_filter, #level_filter').val(null).trigger(
                    'change.select2');
                table.ajax.reload();
            });

            $('#exportExcelBtn').on('click', function() {
                window.location.href = buildExportUrl('xlsx');
            });

            $('#exportCsvBtn').on('click', function() {
                window.location.href = buildExportUrl('csv');
            });

            window.accountingGuideReloadOnCreate = false;
        });
    </script>
@endsection
