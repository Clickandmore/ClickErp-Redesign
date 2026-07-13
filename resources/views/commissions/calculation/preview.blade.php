@extends('admin.layout.indexs.index')

@section('page-title')
    مراجعة العمولة — {{ $admin->name }}
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.show', $admin->id) }}">{{ $admin->name }}</a></li>
    <li class="breadcrumb-item active">مراجعة الاحتساب</li>
@endsection

@section('content')

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">عدد الطلبات المحددة</div>
                <div class="fw-bold fs-2 text-primary">{{ $preview['total_requests'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">إجمالي التكلفة</div>
                <div class="fw-bold fs-2 text-dark">{{ number_format($preview['total_base'], 2) }} <small class="fs-6">ريال</small></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm bg-warning-subtle">
            <div class="card-body">
                <div class="text-muted small">إجمالي العمولة المحتسبة</div>
                <div class="fw-bold fs-2 text-warning">{{ number_format($preview['total_commission'], 2) }} <small class="fs-6">ريال</small></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0 fw-bold"><i class="fa fa-table me-2"></i>تفاصيل احتساب العمولة لـ {{ $admin->name }}</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>رقم التفويض</th>
                        <th>رقم التأشيرة</th>
                        <th>نوع الخدمة</th>
                        <th class="text-end">التكلفة (ريال)</th>
                        <th class="text-center">طريقة الاحتساب</th>
                        <th class="text-center">النسبة / المبلغ</th>
                        <th class="text-end">العمولة (ريال)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['items'] as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item['delegation']->ingaz_number ?? '-' }}</td>
                        <td>{{ $item['delegation']->visa_number ?? '-' }}</td>
                        <td>
                            {{ \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$item['service_type']] ?? $item['service_type'] }}
                        </td>
                        <td class="text-end">{{ number_format($item['base_amount'], 2) }}</td>
                        <td class="text-center">
                            @if($item['method'] === 'percentage')
                                <span class="badge bg-info">نسبة مئوية</span>
                            @else
                                <span class="badge bg-secondary">مبلغ ثابت</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item['method'] === 'percentage')
                                {{ $item['rate_or_amount'] }}%
                            @else
                                {{ number_format($item['rate_or_amount'], 2) }} ريال
                            @endif
                        </td>
                        <td class="text-end fw-bold text-success">{{ number_format($item['commission_amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-warning">
                    <tr>
                        <td colspan="4" class="text-end fw-bold">الإجمالي</td>
                        <td class="text-end fw-bold">{{ number_format($preview['total_base'], 2) }}</td>
                        <td colspan="2"></td>
                        <td class="text-end fw-bold text-success fs-5">{{ number_format($preview['total_commission'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- نموذج الاعتماد --}}
<form method="POST" action="{{ route('commissions.marketers.approve', $admin->id) }}" class="mt-4">
    @csrf
    @foreach($preview['items'] as $item)
        <input type="hidden" name="delegation_ids[]" value="{{ $item['delegation']->id }}">
    @endforeach

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">ملاحظات (اختياري)</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="أي ملاحظات على دفعة العمولة..."></textarea>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-check me-1"></i>اعتماد وإضافة للرصيد
                </button>
                <a href="{{ route('commissions.marketers.show', $admin->id) }}"
                   class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-right me-1"></i>العودة وتعديل التحديد
                </a>
            </div>
        </div>
    </div>
</form>

@endsection
