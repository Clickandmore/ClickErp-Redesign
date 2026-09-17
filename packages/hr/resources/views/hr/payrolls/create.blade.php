@extends('click.layout.indexs.index')

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">مسير الرواتب</a></li>
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
                <div class="payroll-meta">إنشاء مسير شهر {{ $monthLabel }}</div>
            </div>

            <div class="d-flex align-items-end gap-2 flex-wrap">
                <form method="GET" action="{{ route('payrolls.create') }}" class="d-flex align-items-end gap-2 flex-wrap">
                    <label for="month" class="form-label mb-1">اختيار الشهر</label>
                    <input
                        id="month"
                        name="month"
                        type="month"
                        class="form-control"
                        value="{{ $selectedMonth }}"
                        onchange="this.form.submit()"
                    >
                    <div>
                        <label for="month_days_display_option" class="form-label mb-1">خيار عرض الشهر</label>
                        <select
                            id="month_days_display_option"
                            name="month_days_display_option"
                            class="form-control"
                            onchange="this.form.submit()"
                        >
                            <option value="30" {{ ($monthDaysDisplayOption ?? 'current') === '30' ? 'selected' : '' }}>صيغة 30</option>
                            <option value="28" {{ ($monthDaysDisplayOption ?? 'current') === '28' ? 'selected' : '' }}>صيغة 28</option>
                            <option value="31" {{ ($monthDaysDisplayOption ?? 'current') === '31' ? 'selected' : '' }}>صيغة 31</option>
                            <option value="current" {{ ($monthDaysDisplayOption ?? 'current') === 'current' ? 'selected' : '' }}>حسب الشهر الحالي ({{ $actualMonthDays }} يوم)</option>
                        </select>
                    </div>
                    <div>
                        <label for="passport_num" class="form-label mb-1">رقم جواز العاملة</label>
                        <input
                            id="passport_num"
                            name="passport_num"
                            type="text"
                            class="form-control"
                            value="{{ $passportNumber }}"
                        >
                    </div>
                    <div>
                        <label for="residence_num" class="form-label mb-1">رقم الإقامة</label>
                        <input
                            id="residence_num"
                            name="residence_num"
                            type="text"
                            class="form-control"
                            value="{{ $residenceNumber }}"
                        >
                    </div>
                    <div>
                        <label for="worker_scope" class="form-label mb-1">نطاق العاملات</label>
                        <select
                            id="worker_scope"
                            name="worker_scope"
                            class="form-control"
                            onchange="this.form.submit()"
                        >
                            <option value="current" {{ ($workerScope ?? 'current') === 'current' ? 'selected' : '' }}>الحالة الحالية لمسير الرواتب</option>
                            <option value="all" {{ ($workerScope ?? 'current') === 'all' ? 'selected' : '' }}>جميع عاملات التأجير</option>
                        </select>
                    </div>
                    @include('admin.partials.branch-filter', [
                        'id' => 'payroll_branch_id',
                        'name' => 'branch_id',
                        'label' => __('financial_requests.branch'),
                        'containerClass' => '',
                    ])
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="{{ route('payrolls.create', array_filter(['month' => $selectedMonth, 'branch_id' => $selectedBranchId])) }}" class="btn btn-outline-secondary">إعادة ضبط</a>
                </form>

                <a href="{{ $indexRoute }}" class="btn btn-outline-secondary">
                    رجوع
                </a>
            </div>
        </div>

        <div class="alert alert-info d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="fa fa-info-circle mt-1" aria-hidden="true"></i>
            <div>
                <strong>العاملات المشمولات:</strong>
                لا تدخل في هذا المسير إلا العاملات اللاتي ما زلن على كفالة الشركة.
                خيار «الحالة الحالية» يعرض صاحبات عقود الإيجار النشطة، وخيار «جميع عاملات التأجير» يشمل بقية عاملات الشركة مثل الموجودات في الإيواء.
                العاملات المنقولات إلى كفيل آخر أو المُرحّلات لا يظهرن، أما النقل الفاشل فلا يستبعد العاملة.
            </div>
        </div>

        <div class="payroll-note mb-4">
            <strong>ملاحظة:</strong> <br>
           إجمالي الراتب = (أيام العمل × (الراتب الأساسي ÷ {{ $monthDays }})) + حافز + إضافات أخرى، وأيام الغياب = أيام الشهر - أيام العمل
            <br>
            إجمالي الخصومات = سلفة + جزاء + خصومات أخرى
            <br>
            صافي الراتب = إجمالي الراتب - إجمالي الخصومات
        </div>

        <form class="from-submit-global" method="POST" action="{{ $storeRoute }}">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
            <input type="hidden" name="month_year" value="{{ $selectedMonth }}">
            <input type="hidden" name="month_days_display_option" value="{{ $monthDaysDisplayOption }}">

            <div class="card payroll-card mb-3">
                <div class="card-body">
                    <label for="payroll_notes" class="form-label">ملاحظة عامة على المسير</label>
                    <textarea name="payroll_notes" id="payroll_notes" rows="2" class="form-control" placeholder="تظهر هذه الملاحظة داخل المسير المحفوظ"></textarea>
                </div>
            </div>

            <div class="card payroll-card">
                <div class="table-responsive">
                    <table class="table payroll-table mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الجواز</th>
                            <th>رقم الإقامة</th>
                            <th>اختيار</th>
                            <th>الاسم</th>
                            <th>الجنسية</th>
                            <th>أيام العمل</th>
                            <th>الراتب الأساسي</th>
                            <th>حافز</th>
                            <th>إضافات أخرى</th>
                            <th>إجمالي الراتب</th>
                            <th>غياب (أيام)</th>
                            <th>سلفة</th>
                            <th>جزاء</th>
                            <th>خصومات أخرى</th>
                            <th>إجمالي الخصومات</th>
                            <th>صافي الراتب</th>
                            <th>ملاحظة</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr class="payroll-row" data-month-days="{{ $row['month_days'] }}">
                                <td class="fw-bold">{{ $loop->iteration }}</td>
                                <td>{{ $row['passport_num'] ?: '-' }}</td>
                                <td>{{ $row['residence_num'] ?: '-' }}</td>
                                <td>
                                    <input class="form-check-input payroll-row-selector" type="checkbox" name="selected_rows[{{ $loop->index }}]" value="1" checked>
                                    <input type="hidden" name="employee_id[]" value="{{ $row['employee_id'] }}">
                                    <input type="hidden" name="maid_id[]" value="{{ $row['maid_id'] }}">
                                    <input type="hidden" name="employee_name[]" value="{{ $row['employee_name'] }}">
                                    <input type="hidden" name="nationality_name[]" value="{{ $row['nationality_name'] }}">
                                </td>
                                <td class="fw-semibold">{{ $row['employee_name'] }}</td>
                                <td>{{ $row['nationality_name'] }}</td>
                                <td><input type="number" class="form-control payroll-input work-days" name="work_days[]" min="0" max="{{ $row['month_days'] }}" step="1" value="{{ $row['work_days'] }}"></td>
                                <td><input type="number" class="form-control payroll-input basic-salary" name="basic_salary[]" min="0" step="0.01" value="{{ number_format((float) $row['basic_salary'], 2, '.', '') }}"></td>
                                <td><input type="number" class="form-control payroll-input incentive" name="incentive[]" min="0" step="0.01" value="{{ number_format((float) $row['incentive'], 2, '.', '') }}"></td>
                                <td><input type="number" class="form-control payroll-input other-additions" name="other_additions[]" min="0" step="0.01" value="{{ number_format((float) $row['other_additions'], 2, '.', '') }}"></td>
                                <td><input type="text" class="form-control payroll-input gross-salary" value="{{ number_format((float) $row['gross_salary'], 2, '.', '') }}" readonly></td>
                                <td><input type="text" class="form-control payroll-input absence-days" value="{{ $row['absence_days'] }}" readonly></td>
                                <td><input type="number" class="form-control payroll-input advance" name="advance[]" min="0" step="0.01" value="{{ number_format((float) $row['advance'], 2, '.', '') }}"></td>
                                <td><input type="number" class="form-control payroll-input penalty" name="penalty[]" min="0" step="0.01" value="{{ number_format((float) $row['penalty'], 2, '.', '') }}"></td>
                                <td><input type="number" class="form-control payroll-input other-deductions" name="other_deductions[]" min="0" step="0.01" value="{{ number_format((float) $row['other_deductions'], 2, '.', '') }}"></td>
                                <td><input type="text" class="form-control payroll-input total-deductions" value="{{ number_format((float) $row['total_deductions'], 2, '.', '') }}" readonly></td>
                                <td><input type="text" class="form-control payroll-input payroll-net net-salary" value="{{ number_format((float) $row['net_salary'], 2, '.', '') }}" readonly></td>
                                <td><input type="text" class="form-control payroll-input" name="notes[]" value="{{ $row['notes'] }}"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="payroll-empty">
                                    لا توجد عاملات إيجار متاحات بهذا الشهر، أو تم إنشاء مسير لهن مسبقًا.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($rows->isNotEmpty())
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">حفظ مسير الرواتب</button>
                </div>
            @endif
        </form>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            function parseNumber(value) {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function refreshRow(row) {
                const monthDays = parseNumber(row.dataset.monthDays || 30);
                const workDaysInput = row.querySelector('.work-days');
                const basicSalaryInput = row.querySelector('.basic-salary');
                const incentiveInput = row.querySelector('.incentive');
                const otherAdditionsInput = row.querySelector('.other-additions');
                const advanceInput = row.querySelector('.advance');
                const penaltyInput = row.querySelector('.penalty');
                const otherDeductionsInput = row.querySelector('.other-deductions');

                let workDays = parseNumber(workDaysInput.value);
                if (workDays > monthDays) {
                    workDays = monthDays;
                    workDaysInput.value = monthDays;
                }
                if (workDays < 0) {
                    workDays = 0;
                    workDaysInput.value = 0;
                }

                const basicSalary = parseNumber(basicSalaryInput.value);
                const incentive = parseNumber(incentiveInput.value);
                const otherAdditions = parseNumber(otherAdditionsInput.value);
                const advance = parseNumber(advanceInput.value);
                const penalty = parseNumber(penaltyInput.value);
                const otherDeductions = parseNumber(otherDeductionsInput.value);

                const grossSalary = ((workDays * (basicSalary / monthDays)) + incentive + otherAdditions);
                const totalDeductions = advance + penalty + otherDeductions;
                const netSalary = grossSalary - totalDeductions;
                const absenceDays = monthDays - workDays;

                row.querySelector('.gross-salary').value = grossSalary.toFixed(2);
                row.querySelector('.total-deductions').value = totalDeductions.toFixed(2);
                row.querySelector('.net-salary').value = netSalary.toFixed(2);
                row.querySelector('.absence-days').value = absenceDays.toFixed(0);
            }

            function toggleRowState(row, checked) {
                row.classList.toggle('is-inactive', !checked);
            }

            document.querySelectorAll('.payroll-row').forEach(function (row) {
                refreshRow(row);

                row.querySelectorAll('.work-days, .basic-salary, .incentive, .other-additions, .advance, .penalty, .other-deductions')
                    .forEach(function (input) {
                        input.addEventListener('input', function () {
                            refreshRow(row);
                        });
                    });

                const selector = row.querySelector('.payroll-row-selector');
                if (selector) {
                    toggleRowState(row, selector.checked);
                    selector.addEventListener('change', function () {
                        toggleRowState(row, selector.checked);
                    });
                }
            });
        })();
    </script>
@endsection
