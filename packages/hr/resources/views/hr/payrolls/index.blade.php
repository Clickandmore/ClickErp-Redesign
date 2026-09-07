@extends('click.layout.indexs.index')

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('style')
    @include('hr::hr.payrolls._styles')
@endsection

@section('content')
    <div class="payroll-page">
        <div class="payroll-toolbar mb-4">
            <div>
                <h4 class="mb-1">{{ $bladeTitle }}</h4>
                <div class="payroll-meta">صفحة مستقلة لمسيرات رواتب عاملات الإيجار</div>
                <div class="payroll-selection-summary mt-2" data-selected-count>لم يتم تحديد أي مسير</div>
            </div>

            <div class="d-flex align-items-end gap-2 flex-wrap">
                <form method="GET" action="{{ route('payrolls.index') }}" class="d-flex align-items-end gap-2 flex-wrap">
                    <div>
                        <label for="month" class="form-label mb-1">تصفية بالشهر</label>
                        <input
                            id="month"
                            name="month"
                            type="month"
                            class="form-control"
                            value="{{ $selectedMonth }}"
                            onchange="this.form.submit()"
                        >
                    </div>

                    @include('admin.partials.branch-filter', [
                        'id' => 'payroll_branch_id',
                        'name' => 'branch_id',
                        'label' => __('financial_requests.branch'),
                        'containerClass' => '',
                    ])
                </form>

                <a href="{{ $createRoute }}" class="btn btn-primary">
                    إضافة مسير راتب جديد
                </a>
            </div>
        </div>

        <div class="card payroll-card">
            <div class="table-responsive">
                <table class="table payroll-summary mb-0">
                    <thead>
                    <tr>
                        <th class="payroll-select-header">
                            <input type="checkbox" class="form-check-input payroll-select-input" data-select-all aria-label="تحديد الكل">
                        </th>
                        <th>#</th>
                        <th>الشهر</th>
                        <th>الفرع</th>
                        <th>عدد العاملات</th>
                        <th>إجمالي الراتب</th>
                        <th>إجمالي الخصومات</th>
                        <th>صافي الرواتب</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراء</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payrolls as $payroll)
                        <tr class="payroll-summary-row">
                            <td class="payroll-select-cell">
                                <input
                                    type="checkbox"
                                    class="form-check-input payroll-select-input payroll-row-selector"
                                    value="{{ $payroll->id }}"
                                    aria-label="تحديد مسير {{ $loop->iteration }}"
                                >
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payroll->month_year)->locale(app()->getLocale())->translatedFormat('F Y') }}</td>
                            <td>{{ $payroll->branch?->name ?? '-' }}</td>
                            <td>{{ $payroll->workers_count }}</td>
                            <td>{{ number_format((float) $payroll->total_gross_salary, 2) }}</td>
                            <td>{{ number_format((float) $payroll->total_deductions, 2) }}</td>
                            <td class="payroll-net">{{ number_format((float) $payroll->total_net_salary, 2) }}</td>
                            <td>{{ optional($payroll->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('payrolls.show', $payroll->id) }}" class="btn btn-sm btn-outline-primary">
                                    عرض
                                </a>
                                <a href="{{ route('payrolls.export', ['payroll_id' => $payroll->id]) }}" class="btn btn-sm btn-outline-success">
                                    Excel
                                </a>
                                <a href="{{ route('payrolls.show', ['payroll' => $payroll->id, 'print' => 1]) }}" class="btn btn-sm btn-outline-dark" target="_blank">
                                    طباعة
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="payroll-empty">لا توجد مسيرات رواتب محفوظة حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const branchFilter = document.querySelector('[name="branch_id"]');
            if (branchFilter && branchFilter.tagName === 'SELECT') {
                branchFilter.addEventListener('change', function () {
                    this.form.submit();
                });
            }

            const rows = Array.from(document.querySelectorAll('.payroll-summary-row'));
            const selectAll = document.querySelector('[data-select-all]');
            const summary = document.querySelector('[data-selected-count]');

            function selectedRows() {
                return rows.filter(function (row) {
                    const checkbox = row.querySelector('.payroll-row-selector');
                    return checkbox && checkbox.checked;
                });
            }

            function refreshSelectionState() {
                const selected = selectedRows();
                const selectedCount = selected.length;

                rows.forEach(function (row) {
                    const checkbox = row.querySelector('.payroll-row-selector');
                    row.classList.toggle('is-selected', !!checkbox && checkbox.checked);
                });

                if (summary) {
                    summary.textContent = selectedCount
                        ? 'تم تحديد ' + selectedCount + ' مسير'
                        : 'لم يتم تحديد أي مسير';
                }

                if (selectAll) {
                    selectAll.checked = selectedCount > 0 && selectedCount === rows.length;
                    selectAll.indeterminate = selectedCount > 0 && selectedCount < rows.length;
                }
            }

            rows.forEach(function (row) {
                const checkbox = row.querySelector('.payroll-row-selector');
                if (!checkbox) {
                    return;
                }

                row.addEventListener('click', function (event) {
                    if (event.target.closest('a, button, input, label')) {
                        return;
                    }

                    checkbox.checked = !checkbox.checked;
                    refreshSelectionState();
                });

                checkbox.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

                checkbox.addEventListener('change', refreshSelectionState);
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rows.forEach(function (row) {
                        const checkbox = row.querySelector('.payroll-row-selector');
                        if (checkbox) {
                            checkbox.checked = selectAll.checked;
                        }
                    });

                    refreshSelectionState();
                });
            }

            refreshSelectionState();
        })();
    </script>
@endsection
