@extends('admin.layout.indexs.index')

@section('page-title', 'عمولات المسوقين')

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">المسوقون</li>
@endsection

@section('content')
@php
    $totalOperations = $marketers->sum(fn ($employee) => $employee->commission_summary['total']);
    $pendingOperations = $marketers->sum(fn ($employee) => $employee->commission_summary['pending']);
    $totalBalance = $marketers->sum('commission_balance');
@endphp

<style>
    .commission-hero { background: linear-gradient(135deg, #075985, #0e7490); color: #fff; border-radius: 16px; }
    .metric-card { border: 0; border-radius: 14px; box-shadow: 0 4px 18px rgba(15, 23, 42, .07); }
    .metric-icon { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 13px; }
    .employee-avatar { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; background: #e0f2fe; color: #0369a1; font-weight: 800; }
    .service-count { min-width: 45px; display: inline-block; font-weight: 700; }
</style>

<div class="commission-hero p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="small opacity-75 mb-1">دورة العمولة</div>
        <h4 class="mb-2 fw-bold">عقود وخدمات المسوقين في مكان واحد</h4>
        <div class="opacity-75">راجع العمليات، حدّد النسبة أو المبلغ، اعتمد الرصيد ثم أصدر سند الصرف.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_SETTINGS))
            <a href="{{ route('commissions.settings.index') }}" class="btn btn-light">
                <i class="fa fa-sliders-h me-1"></i> إعدادات العمولات
            </a>
        @endif
        @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_PAY))
            <a href="{{ route('commissions.finance.index') }}" class="btn btn-outline-light">
                <i class="fa fa-wallet me-1"></i> مركز الصرف المالي
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card metric-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="metric-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-headset"></i></div>
            <div><div class="text-muted small">المسوقون المرتبطون بعمليات</div><div class="fs-4 fw-bold">{{ number_format($marketers->count()) }}</div></div>
        </div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card metric-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="metric-icon bg-info bg-opacity-10 text-info"><i class="fa fa-folder-open"></i></div>
            <div><div class="text-muted small">إجمالي العمليات</div><div class="fs-4 fw-bold">{{ number_format($totalOperations) }}</div></div>
        </div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card metric-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="metric-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-calculator"></i></div>
            <div><div class="text-muted small">بانتظار الاحتساب</div><div class="fs-4 fw-bold">{{ number_format($pendingOperations) }}</div></div>
        </div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card metric-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="metric-icon bg-success bg-opacity-10 text-success"><i class="fa fa-wallet"></i></div>
            <div><div class="text-muted small">الأرصدة المستحقة</div><div class="fs-4 fw-bold">{{ number_format($totalBalance, 2) }} <small class="fs-6">ر.س</small></div></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-1 fw-bold"><i class="fa fa-users text-primary me-2"></i>ملفات المسوقين</h5>
        <div class="text-muted small">تشمل عقود الاستقدام والتأجير ونقل الخدمة والتفاويض فقط.</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الموظف</th>
                        <th class="text-center">الاستقدام</th>
                        <th class="text-center">التأجير</th>
                        <th class="text-center">نقل الخدمة</th>
                        <th class="text-center">التفاويض</th>
                        <th class="text-center">غير محتسبة</th>
                        <th class="text-end">الرصيد</th>
                        <th class="text-center">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($marketers as $employee)
                    @php $s = $employee->commission_summary; @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">{{ mb_substr($employee->name, 0, 1) }}</div>
                                <div><div class="fw-bold">{{ $employee->name }}</div><div class="text-muted small">#{{ $employee->id }} · {{ optional($employee->role)->name ?? 'خدمة العملاء' }}</div></div>
                            </div>
                        </td>
                        <td class="text-center"><span class="service-count text-primary">{{ $s['recruitment'] }}</span></td>
                        <td class="text-center"><span class="service-count text-info">{{ $s['rental'] }}</span></td>
                        <td class="text-center"><span class="service-count text-warning">{{ $s['transfer'] }}</span></td>
                        <td class="text-center"><span class="service-count text-dark">{{ $s['delegation'] }}</span></td>
                        <td class="text-center"><span class="badge {{ $s['pending'] ? 'bg-warning text-dark' : 'bg-light text-muted border' }}">{{ $s['pending'] }}</span></td>
                        <td class="text-end fw-bold {{ $employee->commission_balance > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($employee->commission_balance, 2) }} ر.س</td>
                        <td class="text-center">
                            <a href="{{ route('commissions.marketers.show', $employee->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye me-1"></i> مراجعة</a>
                            @if($employee->commission_balance > 0)
                                <a href="{{ route('commissions.marketers.payments.index', $employee->id) }}" class="btn btn-sm btn-outline-success"><i class="fa fa-receipt"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">لا يوجد مسوقون مرتبطون بعمليات.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
