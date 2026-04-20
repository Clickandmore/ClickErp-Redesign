@extends('admin.layout.indexs.index')

@section("style")
    @php
        $filters = $filters ?? [];
        $stats = $stats ?? ['total' => 0, 'new' => 0, 'contacted' => 0, 'today' => 0, 'filtered' => 0];
        $hasFilters = collect($filters)->filter(function ($value) {
            return $value !== null && $value !== '';
        })->isNotEmpty();
    @endphp
    <style>
        .contact-page-shell {
            display: grid;
            gap: 1.5rem;
        }

        .contact-hero-card,
        .contact-filter-card,
        .contact-table-card,
        .contact-stat-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        }

        .contact-hero-card {
            background:
                radial-gradient(circle at top right, rgba(13, 110, 253, 0.16), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
        }

        .contact-hero-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #152033;
        }

        .contact-hero-note {
            color: #667085;
            max-width: 760px;
        }

        .contact-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }

        .contact-stat-card {
            background: #fff;
        }

        .contact-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .contact-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #101828;
            line-height: 1;
        }

        .contact-stat-label {
            color: #667085;
            font-size: 0.92rem;
        }

        .contact-filter-card .form-control,
        .contact-filter-card .form-select {
            min-height: 46px;
            border-radius: 14px;
        }

        .contact-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .contact-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .contact-chip {
            background: #eef4ff;
            color: #23406e;
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .contact-soft-primary {
            background: #eef4ff;
            color: #175cd3;
            border: 1px solid #cfe0ff;
        }

        .contact-table-card .table {
            margin-bottom: 0;
        }

        .contact-table-card .table thead th {
            white-space: nowrap;
            border-bottom: 0;
            background: #f8fafc;
            color: #344054;
        }

        .contact-avatar-sm {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .contact-message-cell {
            min-width: 240px;
            max-width: 360px;
            color: #475467;
            line-height: 1.8;
        }

        .status-badge {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.65rem 0.9rem;
        }

        .status-contacted {
            background: #ecfdf3;
            color: #027a48;
        }

        .btn-contact-action {
            border-radius: 12px;
            border: 1px solid #f0b674;
            background: #fff7ed;
            color: #b45309;
            font-weight: 700;
        }

        .btn-contact-action:hover {
            background: #ffedd5;
            color: #92400e;
        }

        .contact-table-card .dataTables_filter,
        .contact-table-card .dataTables_length {
            margin-bottom: 1rem;
        }

        .contact-table-card .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 10px !important;
        }

        @media (max-width: 767.98px) {
            .contact-hero-title {
                font-size: 1.45rem;
            }

            .contact-message-cell {
                min-width: 180px;
            }
        }
    </style>
@endsection

@section('page-title')
    طلبات التواصل
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_VIEW))
        <div class="page-body">
            <div class="container-fluid">
                <div class="contact-page-shell">
                    <div class="card contact-hero-card">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                <div>
                                    <div class="contact-hero-title mb-2">طلبات التواصل</div>
                                    <p class="contact-hero-note mb-0">
                                        متابعة رسائل نموذج التواصل مع عرض افتراضي من الأحدث إلى الأقدم، وإمكانية تصفية النتائج حسب الحالة، التاريخ، أو كلمات البحث.
                                    </p>
                                </div>
                                <div class="text-lg-end">
                                    <div class="badge rounded-pill contact-soft-primary px-3 py-2 fs-6">
                                        {{ number_format($stats['filtered'] ?? 0) }} طلب ظاهر
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="contact-stat-grid">
                        <div class="card contact-stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="contact-stat-icon" style="background:#eef4ff;color:#175cd3;">
                                    <i class="fa fa-inbox"></i>
                                </div>
                                <div>
                                    <div class="contact-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                                    <div class="contact-stat-label">إجمالي الطلبات</div>
                                </div>
                            </div>
                        </div>
                        <div class="card contact-stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="contact-stat-icon" style="background:#fff7ed;color:#c2410c;">
                                    <i class="fa fa-clock"></i>
                                </div>
                                <div>
                                    <div class="contact-stat-value">{{ number_format($stats['new'] ?? 0) }}</div>
                                    <div class="contact-stat-label">بانتظار التواصل</div>
                                </div>
                            </div>
                        </div>
                        <div class="card contact-stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="contact-stat-icon" style="background:#ecfdf3;color:#027a48;">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div>
                                    <div class="contact-stat-value">{{ number_format($stats['contacted'] ?? 0) }}</div>
                                    <div class="contact-stat-label">تم التواصل معها</div>
                                </div>
                            </div>
                        </div>
                        <div class="card contact-stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="contact-stat-icon" style="background:#f5f3ff;color:#6d28d9;">
                                    <i class="fa fa-calendar-day"></i>
                                </div>
                                <div>
                                    <div class="contact-stat-value">{{ number_format($stats['today'] ?? 0) }}</div>
                                    <div class="contact-stat-label">طلبات اليوم</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card contact-filter-card">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h5 class="mb-1">الفلاتر</h5>
                                    <p class="text-muted mb-0">ابحث باسم المرسل أو الجوال أو البريد أو الموضوع، ثم صفِّ النتائج حسب الحالة والتاريخ.</p>
                                </div>
                                <div class="text-muted small">الفرز الافتراضي: الأحدث أولًا</div>
                            </div>

                            <form id="contactFilterForm" class="row g-3" method="GET" action="{{ route('contact.index') }}">
                                <div class="col-lg-4">
                                    <label class="form-label">بحث سريع</label>
                                    <input type="text" class="form-control" name="keyword" id="keyword"
                                        value="{{ $filters['keyword'] ?? '' }}"
                                        placeholder="الاسم، الجوال، البريد، الموضوع، الرسالة">
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <label class="form-label">حالة الطلب</label>
                                    <select class="form-select" name="is_contacted" id="is_contacted">
                                        <option value="">كل الحالات</option>
                                        <option value="0" {{ (string) ($filters['is_contacted'] ?? '') === '0' ? 'selected' : '' }}>بانتظار التواصل</option>
                                        <option value="1" {{ (string) ($filters['is_contacted'] ?? '') === '1' ? 'selected' : '' }}>تم التواصل</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" name="date_from" id="date_from"
                                        value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" name="date_to" id="date_to"
                                        value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                                <div class="col-lg-1 col-md-12">
                                    <label class="form-label d-none d-lg-block">&nbsp;</label>
                                    <div class="contact-filter-actions">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fa fa-filter ms-1"></i>
                                            تصفية
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <a href="{{ route('contact.index') }}" class="btn btn-outline-secondary">
                                        <i class="fa fa-rotate-right ms-1"></i>
                                        إعادة تعيين
                                    </a>
                                </div>
                            </form>

                            @if ($hasFilters)
                                <div class="contact-chip-list mt-3">
                                    @if (!empty($filters['keyword']))
                                        <span class="contact-chip">بحث: {{ $filters['keyword'] }}</span>
                                    @endif
                                    @if (($filters['is_contacted'] ?? '') !== '')
                                        <span class="contact-chip">
                                            الحالة: {{ (string) $filters['is_contacted'] === '1' ? 'تم التواصل' : 'بانتظار التواصل' }}
                                        </span>
                                    @endif
                                    @if (!empty($filters['date_from']))
                                        <span class="contact-chip">من: {{ $filters['date_from'] }}</span>
                                    @endif
                                    @if (!empty($filters['date_to']))
                                        <span class="contact-chip">إلى: {{ $filters['date_to'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card contact-table-card">
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="dataTableObject" class="table table-hover align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>المرسل</th>
                                            <th>الجوال</th>
                                            <th>البريد</th>
                                            <th>نوع الطلب</th>
                                            <th>الموضوع</th>
                                            <th>الرسالة</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإرسال</th>
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
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                searching: false,
                order: [[8, 'desc']],
                ajax: {
                    url: "{{ route('contact.index') }}",
                    type: 'GET',
                    data: function (d) {
                        d.keyword = $('#keyword').val();
                        d.is_contacted = $('#is_contacted').val();
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'phone', name: 'phone', orderable: false},
                    {data: 'email', name: 'email', orderable: false},
                    {data: 'request_type', name: 'request_type', orderable: false, searchable: false},
                    {data: 'subject', name: 'subject'},
                    {data: 'message', name: 'message', orderable: false},
                    {data: 'is_contacted', name: 'is_contacted'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    "sProcessing": "{{ trans('dataTable.sProcessing') }}",
                    "sLengthMenu": "{{ trans('dataTable.sLengthMenu') }}",
                    "sZeroRecords": "لا توجد طلبات تواصل مطابقة",
                    "sInfo": "{{ trans('dataTable.sInfo') }}",
                    "sInfoEmpty": "{{ trans('dataTable.sInfoEmpty') }}",
                    "sInfoFiltered": "{{ trans('dataTable.sInfoFiltered') }}",
                    "sSearch": "{{ trans('dataTable.sSearch') }}:",
                    "oPaginate": {
                        "sFirst": "{{ trans('dataTable.sFirst') }}",
                        "sPrevious": "{{ trans('dataTable.sPrevious') }}",
                        "sNext": "{{ trans('dataTable.sNext') }}",
                        "sLast": "{{ trans('dataTable.sLast') }}"
                    }
                }
            });

        });

        $(document).on('click', '.confirm_contacted', function () {
            const rowId = $(this).data('id');
            const url = "{{ route('contact.update', ':id') }}".replace(':id', rowId);

            $.ajax({
                url: url,
                type: "PUT",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    if (typeof successToster === 'function') {
                        successToster('تم بنجاح', response.message || 'تم تحديث حالة الطلب');
                    }

                    window.location.reload();
                }
            });
        });
    </script>
@endsection
