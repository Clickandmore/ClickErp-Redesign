@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/daterangepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* تحسينات للـ responsive */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .form-control {
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.8rem;
            }

            .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .form-control {
                font-size: 0.8rem;
            }
        }

        .badge {
            font-size: 0.75rem;
        }

        /* تحسين شكل الأزرار */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-search {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
        }

        .btn-clear {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
        }

        .btn-clear:hover {
            background: #6c757d;
            color: white;
            border-color: #6c757d;
        }

        .btn-add {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border: none;
            color: white;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            color: white;
        }

        .btn-export {
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-export.btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
        }

        .btn-export.btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
        }

        .btn-export.btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border: none;
        }

        .btn-export.btn-success:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
        }

        /* تحسين أزرار الجدول */
        .table .btn-sm {
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem 0.1rem;
            transition: all 0.2s ease;
        }

        .table .btn-sm:hover {
            transform: scale(1.05);
        }

        /* أزرار العمليات المخصصة */
        .btn-view {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            color: white;
        }

        .toggle-pin {
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-pin.btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border: none;
            color: #212529;
        }

        .toggle-pin.btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        .toggle-pin.btn-secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
            border: none;
            color: white;
        }

        .toggle-pin.btn-secondary:hover {
            background: linear-gradient(135deg, #545b62, #4e555b);
        }

        /* تحسين الأزرار في الشاشات الصغيرة */
        @media (max-width: 768px) {
            .btn {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
            }

            .btn-sm {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }

            .toggle-pin {
                width: 28px;
                height: 28px;
            }
        }

        @media (max-width: 576px) {
            .btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }

            .btn-sm {
                font-size: 0.65rem;
                padding: 0.15rem 0.3rem;
            }

            .toggle-pin {
                width: 24px;
                height: 24px;
            }
        }

        /* تخصيص لصفحة العاملات باك اوت */
        .page-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .page-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .reason-column {
            max-width: 200px;
            word-wrap: break-word;
        }

        .reason-badge {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .maids-non-table th {
            font-size: 1rem !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #2794eb;
            color: white !important;
            border: 0;
            padding: 0.9rem 1rem !important;
            position: relative;
            box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.08);
        }

        .maids-non-table thead {
            position: relative;
        }

        .maids-non-table thead::after {
            content: "";
            position: absolute;
            left: 16px;
            right: 16px;
            bottom: -4px;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.18), rgba(99, 102, 241, 0.35), rgba(37, 99, 235, 0.18));
        }

        .dataTables_wrapper .table-responsive,
        .table-responsive {
            overflow: visible !important;
        }

        .dataTables_wrapper {
            overflow: visible;
        }

        .table tr td {
            border-color: var(--border-color);
            color: var(--color-500);
            padding: .8rem .6rem !important;
        }

        #dataTableObject_wrapper {
            overflow-x: auto;
        }
    </style>
@endsection
@section('page-title')
    {{ helperTrans('ingaz.deleted_cvs') }}
@endsection
{{-- @section('page-links') --}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li> --}}
{{-- @endsection --}}
@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETED_VIEW))
        <!-- رأس الصفحة المخصص -->
        <div class="page-header">
            <h2 style="color: #007bff;"><i class="fas fa-user-times text-primary me-2"></i> {{ helperTrans('ingaz.deleted_cvs') }} </h2>
        </div>

        <div class="card">
            <div class="card-body show">
                <form id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                            <input type="text" class="form-control" id="passport_num" name="passport_num"
                                value="{{ $request->passport_num }}" placeholder="{{ helperTrans('ingaz.passport_num') }}">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                            <select class="form-control" name="nationality_id" id="nationality_id">
                                <option value="" selected>{{ helperTrans('ingaz.nationality') }}</option>
                                @foreach ($filter['nationalities'] as $nationality)
                                    <option value="{{ $nationality->id }}"
                                        {{ $request->nationality_id == $nationality->id ? 'selected' : '' }}>
                                        {{ $nationality->sub_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                            <select class="form-control" name="job_type" id="type">
                                <option value="" selected>{{ helperTrans('ingaz.job_type') }}</option>
                                @foreach ($filter['job_types'] as $jobtype)
                                    <option value="{{ $jobtype->id }}"
                                        {{ $request->job_type == $jobtype->id ? 'selected' : '' }}>{{ $jobtype->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                            <input type="text" name="experience_years" value="{{ $request->experience_years }}"
                                class="form-control" placeholder="{{ helperTrans('ingaz.experience_years') }}">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                            <input type="text" class="form-control" name="daterange" placeholder="{{ helperTrans('ingaz.date_range') }}"
                                data-separator=" - " autocomplete="off" data-advanced-range="true">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-12 mb-3 text-end">
                            <button id="btnSubmit" type="button" class="btn btn-primary btn-search me-2">
                                <i class="fa fa-search me-1"></i> {{ helperTrans('ingaz.search') }}
                            </button>
                            <a href="{{ route('maids.deleted_cvs') }}" class="btn btn-outline-secondary btn-clear">
                                <i class="fa fa-times me-1"></i> {{ helperTrans('ingaz.clear_filters') }}
                            </a>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 d-flex flex-wrap gap-2">
                            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_PDF))
                                <a href="{{ route('maids.export.pdf') }}?deleted=1"
                                    class="btn btn-danger btn-export" target="_blank">
                                    <i class="fa fa-file-pdf-o me-1"></i> {{ helperTrans('ingaz.export_pdf') }}
                                </a>
                            @endif
                            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_EXCEL))
                                <a href="{{ route('maids.export.excel') }}?deleted=1"
                                    class="btn btn-success btn-export">
                                    <i class="fa fa-file-excel-o me-1"></i> {{ helperTrans('ingaz.export_excel') }}
                                </a>
                            @endif
                            <a href="{{ route('maids.index') }}" class="btn btn-info">
                                <i class="fa fa-list me-1"></i> {{ helperTrans('ingaz.all_maids') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table id="dataTableObject" class="table display dataTable table-hover maids-non-table" style="width:100%">
            <thead>
                <tr>
                    <th class="d-none d-md-table-cell">ID</th>
                    <th>{{ helperTrans('ingaz.cv_file') }}</th>
                    <th>{{ helperTrans('ingaz.name') }}</th>
                    <th>{{ helperTrans('ingaz.passport_num') }}</th>
                    <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.nationality_id') }}</th>
                    <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.job_type') }}</th>
                    <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.price') }}</th>
                    <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.salary') }}</th>
                    <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.deleted_at') }}</th>
                    <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.deleted_by') }}</th>
                    <th>{{ helperTrans('ingaz.actions') }}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        @include('web.ingaz.maids.sub.maid_modal_view')
    @else
        @include('components.no_permissions')
    @endif


@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/daterangepicker.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const deletedCvsTranslations = {
                errorTitle: @json(helperTrans('ingaz.error')),
                dataLoadError: @json(helperTrans('ingaz.data_load_error')),
                loading: @json(helperTrans('ingaz.loading_text')),
            };

            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            @if ($request)
                setTimeout(function() {
                    $('#btnSubmit').trigger('click');
                }, 100);
            @endif

            $(function() {
                $('input[name="daterange"]').daterangepicker({
                    opens: 'left',
                });
                $('input[name="daterange"]').val('');
            });
            let columns = [{
                    "data": 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "cv_file",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "name",
                    name: "name",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "passport_num",
                    name: "passport_num",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "nationality_id",
                    name: "nationality_id",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-lg-table-cell'
                },
                {
                    "data": "job_type",
                    orderable: false,
                    searchable: false,
                    className: 'd-none d-lg-table-cell'
                },
                {
                    "data": "price",
                    name: "price",
                    orderable: true,
                    searchable: false,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "salary",
                    name: "salary",
                    orderable: true,
                    searchable: false,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "deleted_at",
                    name: "deleted_at",
                    orderable: true,
                    searchable: false,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "deleted_by",
                    orderable: false,
                    searchable: false,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];

            function updateDataTable(filters) {
                let url = "{{ route('maids.deleted_cvs') }}";
                let queryParams = buildQueryParams(filters);

                // Check if the URL already has parameters
                if (url.indexOf('?') === -1) {
                    url += '?' + queryParams;
                } else {
                    url += '&' + queryParams;
                }

                // Update the browser history with the new URL
                window.history.pushState({
                    path: url
                }, '', url);

                // Clear existing data and then reload DataTable with the updated URL
                $('#dataTableObject').DataTable().clear().destroy();
                showDataTable(url, columns);
            }

            function buildQueryParams(filters) {
                let queryParams = "";
                for (let key in filters) {
                    if (filters.hasOwnProperty(key) && filters[key]) {
                        if (queryParams !== "") {
                            queryParams += "&";
                        }
                        queryParams += `${key}=${encodeURIComponent(filters[key])}`;
                    }
                }
                return queryParams;
            }

            $('#btnSubmit').on('click', function() {
                let filters = {
                    passport_num: $('#passport_num').val(),
                    nationality_id: $('#nationality_id').val(),
                    job_type: $('#type').val(),
                    experience_years: $('input[name="experience_years"]').val(),
                    datefilter: $('input[name="daterange"]').val()
                };
                updateDataTable(filters);
            });

            function showDataTable(url, columns) {
                console.log('Initializing DataTable with URL:', url);
                $('#dataTableObject').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: url,
                        type: 'GET',
                        error: function(xhr, error, thrown) {
                            console.error('DataTables error:', error);
                            $.toast({
                                heading: deletedCvsTranslations.errorTitle,
                                text: deletedCvsTranslations.dataLoadError,
                                showHideTransition: 'slide',
                                icon: 'error',
                                hideAfter: 3000,
                                position: 'top-right',
                                stack: 1
                            });
                        }
                    },
                    columns: columns,
                    pageLength: 10,
                    order: [[8, 'desc']], // Order by deleted_at column by default in descending order
                    language: {
                        processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${deletedCvsTranslations.loading}</span></div>`
                    },
                    drawCallback: function() {
                        // Reinitialize any event handlers after table redraw
                        console.log('DataTable drawCallback - reinitializing event handlers');
                        initializeEventHandlers();
                    },
                    columnDefs: [{
                        targets: '_all',
                        className: 'align-middle'
                    }]
                });
            }

            showDataTable("{{ route('maids.deleted_cvs') }}", columns);

            function initializeEventHandlers() {
                $(document).off('click', '.restore-maid');

                const restoreRouteTemplate = "{{ route('maids.restore', ['id' => 'MAID_ID']) }}";

                $(document).on('click', '.restore-maid', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const id = $(this).data('id');
                    if (!id) {
                        return;
                    }

                    Swal.fire({
                        icon: 'question',
                        title: '{{ helperTrans('ingaz.restore') }}',
                        text: '{{ helperTrans('ingaz.restore_confirm') }}',
                        showCancelButton: true,
                        confirmButtonText: '{{ helperTrans('ingaz.confirm') }}',
                        cancelButtonText: '{{ helperTrans('ingaz.cancel') }}'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        const url = restoreRouteTemplate.replace('MAID_ID', id);

                        $.ajax({
                            url: url,
                            type: 'POST',
                            success: function(response) {
                                if (response && response.success) {
                                    $('#dataTableObject').DataTable().ajax.reload();
                                    Swal.fire({
                                        icon: 'success',
                                        title: response.message || '{{ helperTrans('ingaz.updated_successfully') }}',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '{{ helperTrans('ingaz.error') }}',
                                        text: response?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ helperTrans('ingaz.error') }}',
                                    text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                                });
                            }
                        });
                    });
                });
            }

            // Function to reload DataTable
            function reloadDataTable() {
                $('#dataTableObject').DataTable().ajax.reload();
            }

            $(document).on('submit', '.from-submit-global-maid', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                let submitButton = $(e.originalEvent.submitter);
                let buttonText = submitButton.text();
                let modalResult = $("#createModal");
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $('#loading-wrapper').fadeIn();
                        submitButton.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' +
                            buttonText);
                    },
                    complete: function() {
                        $('#loading-wrapper').fadeOut();
                    },
                    success: function(result) {

                        console.log(200)

                        reloadDataTable();
                        submitButton.html(buttonText)
                        if (modalResult.is(":visible")) {
                            modalResult.modal('toggle');
                        }
                        window.setTimeout(function() {

                            handleSuccessResponce(result);
                            // window.location.href = '{{ route('maids.index') }}';
                            var id = result.data.id;
                            // console.log(id);
                            window.location.href = "{{ route('convert_page', ':id') }}"
                                .replace(':id', id);
                            $('.loader-ajax').hide()
                        }, 20);
                        // modalResult.modal('toggle');
                        console.log(modalResult.is(':visible'));
                    },
                    error: function(errorObj, errorText, errorThrown) {
                        console.log(500)
                        console.log(errorObj)
                        handleErrorResponce(errorObj);
                    }
                });

            });

        });
    </script>
@endsection





