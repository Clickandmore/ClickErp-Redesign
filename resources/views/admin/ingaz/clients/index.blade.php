@extends('admin.layout.indexs.index')
@section("style")
<style>
    .stats-card {
        border-radius: 16px;
        border: 1px solid #eef2f6;
        box-shadow: 0 4px 12px rgba(15, 34, 58, 0.04);
    }
    .stats-card .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .filter-card {
        border-radius: 18px;
        border: 1px solid #eef2f6;
    }
    .filter-card label {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .actions-toolbar .btn {
        border-radius: 12px;
    }
    .clients-table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background-color: #f8f9fc;
    }
    .badge {
        border-radius: 999px;
    }
    .dataTables_wrapper .table-responsive,
    .table-responsive {
        overflow: visible !important;
    }
    .dataTables_wrapper {
        overflow: visible;
    }
    @media (max-width: 575.98px) {
        .stats-card {
            border-radius: 12px;
        }
        .filter-card {
            border-radius: 14px;
        }
    }
</style>
@endsection
@section('page-title')
{{helperTrans("ingaz.clients")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
    @php
        $stats = $stats ?? [
            'total' => 0,
            'active' => 0,
            'blocked' => 0,
            'new_this_month' => 0,
            'with_orders' => 0,
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 actions-toolbar">
        <div class="btn-toolbar gap-2">
            @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE))
                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i>{{helperTrans("ingaz.add_client")}}
                </a>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF))
                <a href="{{ route('clients.export.pdf') }}" class="btn btn-outline-danger" target="_blank">
                    <i class="fa fa-file-pdf me-1"></i>{{helperTrans("ingaz.export_pdf")}}
                </a>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL))
                <a href="{{ route('clients.export.excel') }}" class="btn btn-outline-success">
                    <i class="fa fa-file-excel me-1"></i>{{helperTrans("ingaz.export_excel")}}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_clients') }}</span>
                            <h3 class="mb-0 mt-1">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <span class="stats-icon bg-primary-subtle text-primary">
                            <i class="fas fa-users"></i>
                        </span>
                    </div>
                    <div class="small text-muted">
                        <i class="fas fa-chart-line text-success me-1"></i>{{ helperTrans('ingaz.new_clients_this_month') }}:
                        <span class="fw-semibold text-success">{{ number_format($stats['new_this_month']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">{{ helperTrans('ingaz.active_clients') }}</span>
                            <h3 class="mb-0 mt-1 text-success">{{ number_format($stats['active']) }}</h3>
                        </div>
                        <span class="stats-icon bg-success-subtle text-success">
                            <i class="fas fa-user-check"></i>
                        </span>
                    </div>
                    <div class="small text-muted">
                        {{ helperTrans('ingaz.clients_with_orders') }}:
                        <span class="fw-semibold">{{ number_format($stats['with_orders']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">{{ helperTrans('ingaz.blocked_clients') }}</span>
                            <h3 class="mb-0 mt-1 text-danger">{{ number_format($stats['blocked']) }}</h3>
                        </div>
                        <span class="stats-icon bg-danger-subtle text-danger">
                            <i class="fas fa-user-slash"></i>
                        </span>
                    </div>
                    <div class="small text-muted">
                        {{ helperTrans('ingaz.status_filter') }}: {{ helperTrans('ingaz.blocked') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex flex-column gap-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">{{ helperTrans('ingaz.registration_date') }}</span>
                            <h3 class="mb-0 mt-1 text-info">
                                {{ $stats['total'] ? round(($stats['new_this_month'] / max($stats['total'], 1)) * 100, 1) : 0 }}%
                            </h3>
                        </div>
                        <span class="stats-icon bg-info-subtle text-info">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                    <div class="small text-muted">
                        {{ helperTrans('ingaz.new_clients_this_month') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form id="clientsFilters" class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">{{ helperTrans('ingaz.search_keyword') }}</label>
                    <input type="text" class="form-control" id="filter-keyword" placeholder="{{ helperTrans('ingaz.search_keyword_placeholder') }}">
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.status_filter') }}</label>
                    <select id="filter-status" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        <option value="active">{{ helperTrans('ingaz.active') }}</option>
                        <option value="blocked">{{ helperTrans('ingaz.blocked') }}</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.nationality_filter') }}</label>
                    <select id="filter-nationality" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}">{{ app()->getLocale() === 'ar' ? $nationality->name_ar : $nationality->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.city_filter') }}</label>
                    <select id="filter-city" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->name_ar }}">{{ app()->getLocale() === 'ar' ? $city->name_ar : $city->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.has_orders_filter') }}</label>
                    <select id="filter-orders" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        <option value="with">{{ helperTrans('ingaz.with_orders') }}</option>
                        <option value="without">{{ helperTrans('ingaz.without_orders') }}</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.from_date') }}</label>
                    <input type="date" id="filter-date-from" class="form-control">
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.to_date') }}</label>
                    <input type="date" id="filter-date-to" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>{{ helperTrans('ingaz.filter') }}
                    </button>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">
                        <i class="fas fa-undo me-1"></i>{{ helperTrans('ingaz.reset') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableObject" class="table table-hover align-middle clients-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{helperTrans("ingaz.name")}}</th>
                            <th>{{helperTrans("ingaz.phone")}}</th>
                            <th>{{helperTrans("ingaz.client_activity")}}</th>
                            <th>{{helperTrans("ingaz.status")}}</th>
                            <th>{{helperTrans("ingaz.created_at")}}</th>
                            <th>{{helperTrans("ingaz.actions")}}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')
<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script>
    $(function () {
        const table = $('#dataTableObject').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('clients.index') }}",
                data: function (d) {
                    d.status = $('#filter-status').val();
                    d.nationality_id = $('#filter-nationality').val();
                    d.city = $('#filter-city').val();
                    d.has_orders = $('#filter-orders').val();
                    d.date_from = $('#filter-date-from').val();
                    d.date_to = $('#filter-date-to').val();
                    d.keyword = $('#filter-keyword').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name', orderable: false},
                {data: 'phone', name: 'phone', orderable: false, searchable: false},
                {data: 'metrics', name: 'metrics', orderable: false, searchable: false},
                {data: 'is_blocked', name: 'is_blocked', orderable: false, searchable: false},
                {data: 'created_at', name: 'created_at'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[5, 'desc']],
            language: {
                url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
            },
            drawCallback: function () {
                if (typeof bootstrap !== 'undefined') {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            }
        });

        $('#clientsFilters').on('submit', function (e) {
            e.preventDefault();
            table.draw();
        });

        $('#resetFilters').on('click', function () {
            $('#clientsFilters')[0].reset();
            table.draw();
        });

        $('.filter-trigger').on('change', function () {
            table.draw();
        });
    });
</script>
@endsection
