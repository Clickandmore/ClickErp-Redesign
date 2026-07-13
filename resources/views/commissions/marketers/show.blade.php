@extends('admin.layout.indexs.index')

@section('page-title')
    ملف المسوّق — {{ $admin->name }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">{{ $admin->name }}</li>
@endsection

@section('content')

{{-- بطاقة معلومات الموظف والرصيد --}}
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;font-size:1.4rem;font-weight:900;">
                        {{ mb_substr($admin->name, 0, 1) }}
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $admin->name }}</h5>
                        <div class="text-muted small">
                            {{ $admin->email }} &nbsp;|&nbsp; {{ $admin->phone ?? 'لا يوجد هاتف' }}
                        </div>
                        <span class="badge bg-secondary mt-1">{{ optional($admin->role)->name ?? 'بدون دور' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="text-muted small mb-1">الرصيد المستحق الحالي</div>
                <div class="fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}"
                     style="font-size:1.8rem;">
                    {{ number_format($balance, 2) }} <small class="fs-6">ريال</small>
                </div>
                @if($balance > 0)
                <a href="{{ route('commissions.marketers.payments.index', $admin->id) }}"
                   class="btn btn-success btn-sm mt-2">
                    <i class="fa fa-money-bill-wave me-1"></i>إصدار سند صرف
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- فلاتر التاريخ --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('commissions.marketers.show', $admin->id) }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">نوع الفلتر</label>
                    <select class="form-select" id="dateFilterType" name="date_filter">
                        <option value="" {{ empty($filters['date_filter']) ? 'selected' : '' }}>كافة التواريخ</option>
                        <option value="month_year" {{ ($filters['date_filter'] ?? '') === 'month_year' ? 'selected' : '' }}>شهر / سنة</option>
                        <option value="range" {{ ($filters['date_filter'] ?? '') === 'range' ? 'selected' : '' }}>نطاق من-إلى</option>
                    </select>
                </div>

                {{-- شهر/سنة --}}
                <div class="col-md-2 filter-month-year" style="display:none;">
                    <label class="form-label fw-semibold">الشهر</label>
                    <select class="form-select" name="month">
                        <option value="">الكل</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ ($filters['month'] ?? '') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2 filter-month-year" style="display:none;">
                    <label class="form-label fw-semibold">السنة</label>
                    <select class="form-select" name="year">
                        <option value="">الكل</option>
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ ($filters['year'] ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- نطاق --}}
                <div class="col-md-2 filter-range" style="display:none;">
                    <label class="form-label fw-semibold">من تاريخ</label>
                    <input type="date" class="form-control" name="from_date"
                           value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-md-2 filter-range" style="display:none;">
                    <label class="form-label fw-semibold">إلى تاريخ</label>
                    <input type="date" class="form-control" name="to_date"
                           value="{{ $filters['to_date'] ?? '' }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-filter me-1"></i>تطبيق
                    </button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-outline-secondary w-100">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- جدول الطلبات --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="fa fa-list me-2"></i>
            طلبات المسوّق
            <span class="badge bg-primary ms-2" id="filteredCount">...</span>
            <span class="text-muted small">/ {{ $totalCount }}</span>
        </h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                تحديد الكل غير المحتسب
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="deselectAllBtn">
                إلغاء الكل
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <form id="commissionForm" method="POST"
              action="{{ route('commissions.marketers.preview', $admin->id) }}">
            @csrf
            <div class="table-responsive">
                <table id="delegationsTable" class="table table-hover mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;"></th>
                            <th>#</th>
                            <th>رقم التفويض</th>
                            <th>رقم التأشيرة</th>
                            <th>نوع الخدمة</th>
                            <th>الجنسية</th>
                            <th>المهنة</th>
                            <th>العميل</th>
                            <th>الحالة</th>
                            <th class="text-end">التكلفة (ريال)</th>
                            <th>حالة الوصل</th>
                            <th>احتساب العمولة</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            {{-- ملخص التحديد --}}
            <div id="selectionSummary" class="bg-light border-top p-3" style="display:none;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <span class="fw-bold">الطلبات المحددة:</span>
                        <span id="selectedCount" class="badge bg-primary ms-1">0</span>
                        &nbsp;&nbsp;
                        <span class="fw-bold">إجمالي التكلفة:</span>
                        <span id="selectedTotal" class="text-primary fw-bold ms-1">0.00</span> ريال
                    </div>
                    <button type="submit" class="btn btn-warning" id="previewBtn" disabled>
                        <i class="fa fa-calculator me-1"></i>احتساب العمولة للمحددين
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- سجل الحركات الأخيرة --}}
@if($balanceTransactions->count())
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0 fw-bold"><i class="fa fa-history me-2"></i>آخر حركات الرصيد</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>النوع</th>
                    <th class="text-end">المبلغ</th>
                    <th class="text-end">الرصيد بعد</th>
                    <th>البيان</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balanceTransactions as $tx)
                <tr>
                    <td>
                        @if($tx->type === 'credit')
                            <span class="badge bg-success">إضافة</span>
                        @else
                            <span class="badge bg-danger">خصم</span>
                        @endif
                    </td>
                    <td class="text-end {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                        {{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                    </td>
                    <td class="text-end fw-bold">{{ number_format($tx->balance_after, 2) }}</td>
                    <td class="text-muted small">{{ $tx->notes }}</td>
                    <td class="text-muted small">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>

    <script>
    $(document).ready(function () {
        // تبديل حقول الفلتر
        function toggleFilterFields() {
            const val = $('#dateFilterType').val();
            $('.filter-month-year').toggle(val === 'month_year');
            $('.filter-range').toggle(val === 'range');
        }
        toggleFilterFields();
        $('#dateFilterType').on('change', toggleFilterFields);

        // DataTable
        const table = $('#delegationsTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ordering: false,
            searching: false,
            language: { url: '{{ asset("admin/assets/js/bundle/ar.json") }}' },
            ajax: {
                url: '{{ route("commissions.marketers.show", $admin->id) }}',
                data: function (d) {
                    d.date_filter = '{{ $filters["date_filter"] ?? "" }}';
                    d.month       = '{{ $filters["month"] ?? "" }}';
                    d.year        = '{{ $filters["year"] ?? "" }}';
                    d.from_date   = '{{ $filters["from_date"] ?? "" }}';
                    d.to_date     = '{{ $filters["to_date"] ?? "" }}';
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'ingaz_number' },
                { data: 'visa_number' },
                { data: 'visa_type_label' },
                { data: 'nationality_name' },
                { data: 'profession_name' },
                { data: 'client_name' },
                { data: 'status' },
                { data: 'total_due', className: 'text-end' },
                { data: 'is_paid_label', orderable: false },
                { data: 'commissioned_label', orderable: false },
            ],
            drawCallback: function (settings) {
                const info = this.api().page.info();
                $('#filteredCount').text(info.recordsDisplay);
                updateSummary();
            }
        });

        // تتبع التفويضات المحددة والتكلفة (عبر data-amount)
        function updateSummary() {
            const checked = $('.delegation-check:checked');
            const count = checked.length;
            let total = 0;
            checked.each(function() {
                total += parseFloat($(this).data('amount') || 0);
            });
            $('#selectedCount').text(count);
            $('#selectedTotal').text(total.toFixed(2));
            $('#selectionSummary').toggle(count > 0);
            $('#previewBtn').prop('disabled', count === 0);
        }

        $(document).on('change', '.delegation-check', updateSummary);

        $('#selectAllBtn').on('click', function () {
            $('.delegation-check').prop('checked', true);
            updateSummary();
        });

        $('#deselectAllBtn').on('click', function () {
            $('.delegation-check').prop('checked', false);
            updateSummary();
        });
    });
    </script>
@endsection
