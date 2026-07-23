@extends('admin.layout.indexs.index')

@section('page-title', 'عمولات المسوقين')

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">المسوقون</li>
@endsection

@section('content')
<style>
    .employee-avatar { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; background: #e0f2fe; color: #0369a1; font-weight: 800; }
    .service-count { min-width: 45px; display: inline-block; font-weight: 700; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h4 class="mb-1 fw-bold">عمولات المسوقين</h4><div class="text-muted">العقود والخدمات المرتبطة بكل مسوق وحالة احتسابها.</div></div>
    <div class="d-flex flex-wrap gap-2">
        @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_SETTINGS))
            <a href="{{ route('commissions.settings.index') }}" class="btn btn-light">
                <i class="fa fa-sliders-h me-1"></i> إعدادات العمولات
            </a>
        @endif
        @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_PAY))
            <a href="{{ route('commissions.finance.index') }}" class="btn btn-outline-success">
                <i class="fa fa-wallet me-1"></i> مركز الصرف المالي
            </a>
        @endif
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
                <thead class="table-light text-center align-middle">
                    <tr>
                        <th rowspan="2" class="text-start">المسوق</th>
                        <th colspan="3" class="table-primary">عقود الاستقدام</th>
                        <th colspan="3" class="table-info">عقود التأجير</th>
                        <th colspan="3" class="table-warning">نقل الخدمات</th>
                        <th colspan="3" class="table-secondary">التفاويض</th>
                        <th rowspan="2">إجمالي غير محتسبة</th>
                        <th rowspan="2">قيمة المحتسب المسدد</th>
                        <th rowspan="2" class="text-end">الرصيد</th>
                        <th rowspan="2">الإجراء</th>
                    </tr>
                    <tr>
                        @foreach(range(1, 4) as $group)
                            <th>الإجمالي</th><th>غير محتسبة</th><th>محتسبة</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($marketers as $employee)
                    @php $s = $employee->commission_summary; @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">{{ mb_substr($employee->commission_display_name, 0, 1) }}</div>
                                <div><div class="fw-bold">{{ $employee->commission_display_name }}</div><div class="text-muted small">#{{ $employee->id }} · {{ optional($employee->role)->name ?? 'خدمة العملاء' }}</div></div>
                            </div>
                        </td>
                        <td class="text-center fw-bold text-primary">{{ $s['recruitment'] }}</td>
                        <td class="text-center text-danger">{{ $s['recruitment_pending'] }}</td>
                        <td class="text-center text-success">{{ $s['recruitment_commissioned'] }}</td>
                        <td class="text-center fw-bold text-info">{{ $s['rental'] }}</td>
                        <td class="text-center text-danger">{{ $s['rental_pending'] }}</td>
                        <td class="text-center text-success">{{ $s['rental_commissioned'] }}</td>
                        <td class="text-center fw-bold text-warning">{{ $s['transfer'] }}</td>
                        <td class="text-center text-danger">{{ $s['transfer_pending'] }}</td>
                        <td class="text-center text-success">{{ $s['transfer_commissioned'] }}</td>
                        <td class="text-center fw-bold">{{ $s['delegation'] }}</td>
                        <td class="text-center text-danger">{{ $s['delegation_pending'] }}</td>
                        <td class="text-center text-success">{{ $s['delegation_commissioned'] }}</td>
                        <td class="text-center"><span class="badge {{ $s['pending'] ? 'bg-warning text-dark' : 'bg-light text-muted border' }}">{{ $s['pending'] }}</span></td>
                        <td class="text-center fw-bold text-success">{{ number_format($s['commissioned_amount'], 2) }} ر.س</td>
                        <td class="text-end fw-bold {{ $employee->commission_balance > 0 ? 'text-success' : 'text-muted' }}">{{ number_format($employee->commission_balance, 2) }} ر.س</td>
                        <td class="text-center">
                            <a href="{{ route('commissions.marketers.show', $employee->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-eye me-1"></i> مراجعة</a>
                            @if($employee->commission_balance > 0)
                                <a href="{{ route('commissions.marketers.payments.index', $employee->id) }}" class="btn btn-sm btn-outline-success"><i class="fa fa-receipt"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="17" class="text-center text-muted py-5">لا يوجد مسوقون مرتبطون بعمليات.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
