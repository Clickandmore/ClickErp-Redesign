@extends('click.layout.indexs.index')

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">مسير الرواتب</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $monthLabel }}</li>
@endsection

@section('style')
    @include('hr::hr.payrolls._styles')
@endsection

@section('content')
    <div class="payroll-page">
        <div class="payroll-toolbar mb-4">
            <div>
                <h4 class="mb-1">{{ $bladeTitle }}</h4>
                <div class="payroll-meta">مسير شهر {{ $monthLabel }}</div>
            </div>

            <div class="d-flex align-items-end gap-2 flex-wrap">
                <a href="{{ $exportRoute }}" class="btn btn-outline-success">Excel</a>
                <button type="button" class="btn btn-outline-dark" onclick="window.print()">طباعة</button>
                <a href="{{ route('payrolls.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </div>

        <div class="payroll-note mb-4">
            <strong>ملاحظة:</strong><br>
           إجمالي الراتب = (أيام العمل × (الراتب الأساسي ÷ {{ $payroll->month_days }})) + حافز + إضافات أخرى، وأيام الغياب = أيام الشهر - أيام العمل
            <br>
            إجمالي الخصومات = سلفة + جزاء + خصومات أخرى
            <br>
            صافي الراتب = إجمالي الراتب - إجمالي الخصومات
        </div>

        <div class="card payroll-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">الشهر</label>
                        <input type="text" class="form-control" value="{{ $monthLabel }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">عدد العاملات</label>
                        <input type="text" class="form-control" value="{{ $payroll->workers_count }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">أيام الشهر</label>
                        <input type="text" class="form-control" value="{{ $payroll->month_days }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">إجمالي الراتب</label>
                        <input type="text" class="form-control" value="{{ number_format((float) $payroll->total_gross_salary, 2) }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">صافي الرواتب</label>
                        <input type="text" class="form-control payroll-net" value="{{ number_format((float) $payroll->total_net_salary, 2) }}" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">ملاحظة عامة</label>
                        <textarea class="form-control" rows="2" readonly>{{ $payroll->notes }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card payroll-card">
            <div class="table-responsive">
                <table class="table payroll-table mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
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
                    @forelse($payroll->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $item->employee_name }}</td>
                            <td>{{ $item->nationality_name }}</td>
                            <td><input type="text" class="form-control payroll-input" value="{{ $item->work_days + 0 }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->basic_salary, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->incentive, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->other_additions, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->gross_salary, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ $item->absence_days + 0 }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->advance, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->penalty, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->other_deductions, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ number_format((float) $item->total_deductions, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input payroll-net" value="{{ number_format((float) $item->net_salary, 2, '.', '') }}" readonly></td>
                            <td><input type="text" class="form-control payroll-input" value="{{ $item->notes }}" readonly></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="payroll-empty">لا توجد تفاصيل لهذا المسير.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="payroll-print-sheet" dir="rtl">
        <div class="payroll-print-header">
            <div class="payroll-print-logo">
                @if($headerLogoUrl)
                    <img src="{{ $headerLogoUrl }}" alt="company logo">
                @endif
            </div>
            <div class="payroll-print-title">
                <h2>مسير الرواتب</h2>
                <div>مسير شهر {{ $monthLabel }}</div>
            </div>
            <div class="payroll-print-date">
                {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>

        <div class="payroll-print-summary">
            <div>
                <span>الشهر</span>
                <strong>{{ $monthLabel }}</strong>
            </div>
            <div>
                <span>عدد العاملات</span>
                <strong>{{ $payroll->workers_count }}</strong>
            </div>
            <div>
                <span>إجمالي الراتب</span>
                <strong>{{ number_format((float) $payroll->total_gross_salary, 2) }}</strong>
            </div>
            <div>
                <span>إجمالي الخصومات</span>
                <strong>{{ number_format((float) $payroll->total_deductions, 2) }}</strong>
            </div>
            <div>
                <span>صافي الرواتب</span>
                <strong>{{ number_format((float) $payroll->total_net_salary, 2) }}</strong>
            </div>
        </div>

        @if($payroll->notes)
            <div class="payroll-print-notes">
                <strong>ملاحظة عامة:</strong>
                <span>{{ $payroll->notes }}</span>
            </div>
        @endif

        <table class="payroll-print-table">
            <colgroup>
                <col class="print-col-index">
                <col class="print-col-name">
                <col class="print-col-passport">
                <col class="print-col-id">
                <col class="print-col-nationality">
                <col class="print-col-days">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-days">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-money">
                <col class="print-col-notes">
            </colgroup>
            <thead>
            <tr>
                <th>#</th>
                <th>الاسم</th>
                <th>رقم الجواز</th>
                <th>رقم الإقامة</th>
                <th>الجنسية</th>
                <th>أيام</th>
                <th>أساسي</th>
                <th>حافز</th>
                <th>إضافات</th>
                <th>إجمالي</th>
                <th>غياب</th>
                <th>سلفة</th>
                <th>جزاء</th>
                <th>خصومات</th>
                <th>إجمالي خصم</th>
                <th>الصافي</th>
                <th>ملاحظة</th>
            </tr>
            </thead>
            <tbody>
            @forelse($payroll->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->employee_name }}</td>
                    <td>{{ $item->maid?->passport_num ?: '-' }}</td>
                    <td>{{ $item->maid?->id_number ?: '-' }}</td>
                    <td>{{ $item->nationality_name }}</td>
                    <td>{{ $item->work_days + 0 }}</td>
                    <td>{{ number_format((float) $item->basic_salary, 2) }}</td>
                    <td>{{ number_format((float) $item->incentive, 2) }}</td>
                    <td>{{ number_format((float) $item->other_additions, 2) }}</td>
                    <td>{{ number_format((float) $item->gross_salary, 2) }}</td>
                    <td>{{ $item->absence_days + 0 }}</td>
                    <td>{{ number_format((float) $item->advance, 2) }}</td>
                    <td>{{ number_format((float) $item->penalty, 2) }}</td>
                    <td>{{ number_format((float) $item->other_deductions, 2) }}</td>
                    <td>{{ number_format((float) $item->total_deductions, 2) }}</td>
                    <td>{{ number_format((float) $item->net_salary, 2) }}</td>
                    <td>{{ $item->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17">لا توجد تفاصيل لهذا المسير.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('print') === '1') {
                if (document.readyState === 'complete') {
                    window.print();
                } else {
                    window.addEventListener('load', function () {
                        window.print();
                    });
                }
            }
        })();
    </script>
@endsection
