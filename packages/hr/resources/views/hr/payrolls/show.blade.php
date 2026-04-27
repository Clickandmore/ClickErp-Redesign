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
                <a href="{{ route('payrolls.index') }}" class="btn btn-outline-secondary">رجوع</a>
            </div>
        </div>

        <div class="payroll-note mb-4">
            <strong>ملاحظة:</strong><br>
           إجمالي الراتب = (أيام العمل × (الراتب الأساسي ÷ 30)) + حافز + إضافات أخرى = أيام الشهر - أيام الغياب
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
@endsection
