@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة تفاويض الإنجاز
@endsection

@section('css')
    <style>
        .ingaz-delegations-page .page-heading {
            gap: 12px;
        }

        .ingaz-delegations-page .page-heading h3 {
            font-size: 22px;
            font-weight: 700;
        }

        .ingaz-delegations-page .filter-card,
        .ingaz-delegations-page .table-card {
            border-radius: 8px;
        }

        .ingaz-delegations-page .form-label {
            font-weight: 600;
            color: #344054;
        }

        .ingaz-delegations-page .table-responsive {
            overflow-x: auto;
        }

        #ingaz-delegations-table {
            min-width: 1260px;
        }

        #ingaz-delegations-table thead th {
            vertical-align: middle;
        }

        .delegation-date-stack,
        .delegation-person-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 112px;
        }

        .delegation-date-main,
        .delegation-person-name {
            color: #1f2937;
            font-weight: 700;
            white-space: nowrap;
        }

        .delegation-inline-actions,
        .delegation-row-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .delegation-row-actions {
            min-width: 210px;
        }

        .delegation-row-actions form {
            display: inline-flex;
            margin: 0;
        }

        .delegation-inline-actions .btn,
        .delegation-row-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .delegation-status-select {
            min-width: 150px;
        }

        @media (max-width: 768px) {
            .ingaz-delegations-page .page-heading {
                align-items: flex-start !important;
            }

            .ingaz-delegations-page .page-heading > div:last-child,
            .ingaz-delegations-page .page-heading .btn {
                width: 100%;
            }

            .delegation-row-actions {
                min-width: 180px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid ingaz-delegations-page">
            <div class="page-heading d-flex align-items-center justify-content-between flex-wrap mb-4">
                <div>
                    <h3 class="mb-1">إدارة تفاويض الإنجاز</h3>
                    <p class="text-muted mb-0">متابعة تاريخ التفويض وتاريخ الإضافة وبيانات العميل والفاتورة وتحويل مساند.</p>
                </div>
                @if ($canCreateDelegation)
                    <a href="{{ route('reports.import.index', ['type' => 'ingaz_delegations']) }}" class="btn btn-outline-success">
                        <i class="fa fa-file-import me-1"></i>
                        استيراد من Excel
                    </a>
                    <a href="{{ route('ingaz-delegations.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i>
                        إضافة تفويض جديد
                    </a>
                @endif
            </div>

            <div class="card shadow-sm border-0 mb-4 filter-card">
                <div class="card-body">
                    <form method="GET" id="delegations-filter-form" class="row g-3">
                        @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-2'])
                        <div class="col-md-4">
                            <label class="form-label">بحث</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                   placeholder="إنجاز، مساند، التأشيرة، العميل، الجواز">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الدولة</label>
                            <select name="country_id" class="form-select select2" data-placeholder="الكل">
                                <option value="">الكل</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name_ar ?: $country->name_en }}{{ $country->code ? ' - ' . $country->code : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">نوع العميل</label>
                            <select name="client_source_type" class="form-select">
                                <option value="">الكل</option>
                                <option value="existing_in_program" {{ request('client_source_type') === 'existing_in_program' ? 'selected' : '' }}>موجود في البرنامج</option>
                                <option value="separate_client" {{ request('client_source_type') === 'separate_client' ? 'selected' : '' }}>عميل منفصل</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">من تاريخ التفويض</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">إلى تاريخ التفويض</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter me-1"></i>
                                تصفية
                            </button>
                            <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-light">
                                <i class="fa fa-rotate-right me-1"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 table-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ingaz-delegations-table" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>تاريخ التفويض</th>
                                    <th>تاريخ الإضافة</th>
                                    <th>عميل</th>
                                    <th>عقد مساند</th>
                                    <th>الدولة</th>
                                    <th>المهنة</th>
                                    <th>الفاتورة والتحويل</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            if ($.fn.select2) {
                $('select[name="country_id"]').select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $('select[name="country_id"]').data('placeholder') || 'الكل'
                });
            }

            const table = $('#ingaz-delegations-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                searching: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('ingaz-delegations.index') }}",
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('#branchFilter').val();
                        d.global_search = $('input[name="search"]').val();
                        d.country_id = $('select[name="country_id"]').val();
                        d.client_source_type = $('select[name="client_source_type"]').val();
                        d.from_date = $('input[name="from_date"]').val();
                        d.to_date = $('input[name="to_date"]').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id', className: 'text-nowrap fw-bold' },
                    { data: 'delegation_date_gregorian', name: 'delegation_date_gregorian', className: 'text-nowrap' },
                    { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
                    { data: 'client', name: 'client', orderable: false, searchable: false },
                    { data: 'msaned_transfer', name: 'msaned_contract_number' },
                    { data: 'country_name', name: 'country_name', orderable: false, searchable: false },
                    { data: 'profession_name', name: 'profession_name', orderable: false, searchable: false },
                    { data: 'invoice', name: 'invoice', orderable: false, searchable: false },
                    { data: 'status_control', name: 'status', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-nowrap' }
                ],
                columnDefs: [
                    { targets: 0, responsivePriority: 1, width: '60px' },
                    { targets: 3, responsivePriority: 2 },
                    { targets: -1, responsivePriority: 3 },
                    { targets: [1, 2, 8], responsivePriority: 4 }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
                }
            });

            $('#delegations-filter-form').on('submit', function (event) {
                event.preventDefault();
                table.ajax.reload();
            });

            $(document).on('focus', '.delegation-status-select', function () {
                $(this).data('previous', $(this).val());
            });

            $(document).on('change', '.delegation-status-select', function () {
                const $select = $(this);
                const previousValue = $select.data('previous') ?? $select.val();

                $.ajax({
                    url: $select.data('url'),
                    type: 'PATCH',
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: $select.val()
                    },
                    success: function (response) {
                        $select.data('previous', $select.val());

                        if (typeof successToster === 'function') {
                            successToster('تم بنجاح', response.message || 'تم تحديث الحالة بنجاح');
                        }

                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        $select.val(previousValue);

                        if (typeof errorToster === 'function') {
                            errorToster('حدث خطأ', xhr.responseJSON?.message || 'تعذر تحديث الحالة');
                        }
                    }
                });
            });
        });
    </script>
@endsection
