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

        .office-name-cell {
            font-weight: 700;
            color: #111827;
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
                            <p class="text-muted mb-0">{{ helperTrans('ingaz.filters') }} ، {{ helperTrans('ingaz.status') }} ، {{ helperTrans('ingaz.linked_cvs') }} ، {{ helperTrans('ingaz.cost_of_dollar') }}</p>
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
                                <div class="office-stat-label">{{ helperTrans('ingaz.offices_with_cost_of_dollar') }}</div>
                                <div class="office-stat-value">{{ $stats['with_cost_of_dollar'] ?? 0 }}</div>
                            </div>
                            <span class="office-stat-icon"><i class="fa fa-dollar-sign"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="office-filter-card card mb-4">
                <div class="office-card-head">
                    <h5 class="office-card-title">{{ helperTrans('ingaz.filters') }}</h5>
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
                                    <option value="{{ mb_strtolower($nationality->title) }}">{{ $nationality->title }}</option>
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
                                    <th>{{ helperTrans('ingaz.linked_cvs') }}</th>
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

        $(document).ready(function() {
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
                const linkedCount = parseInt(stripHtml(data[3]) || '0', 10);
                const costValue = stripHtml(data[4]);
                const statusRaw = data[5] || '';
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
                searching: false,
                iDisplayLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "الكل"]
                ],
                processing: true,
                serverSide: false,
                ajax: "{{ route('offices.index') }}",
                columns: [{
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
                        render: function(data) {
                            return '<span class="office-name-cell">' + (data || '') + '</span>';
                        }
                    },
                    {
                        data: 'nationality_id',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'linked_cvs',
                        orderable: true,
                        searchable: false
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
                order: [
                    [1, 'asc']
                ]
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
    </script>
@endsection
