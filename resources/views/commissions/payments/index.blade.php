@extends('admin.layout.indexs.index')

@section('page-title')
    سندات صرف عمولة الموظف — {{ $admin->name }}
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.show', $admin->id) }}">{{ $admin->name }}</a></li>
    <li class="breadcrumb-item active">سندات الصرف</li>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row mb-4">
    {{-- إصدار سند جديد --}}
    <div class="col-md-5">
        <div class="card border-warning">
            <div class="card-header bg-warning-subtle">
                <h6 class="mb-0 fw-bold"><i class="fa fa-plus me-2"></i>إصدار سند صرف جديد</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 text-center">
                    <div class="text-muted small">الرصيد المتاح للصرف</div>
                    <div class="fw-bold text-danger fs-3">{{ number_format($balance, 2) }} ريال</div>
                </div>

                @if($balance > 0)
                <form method="POST" action="{{ route('commissions.marketers.payments.store', $admin->id) }}">
                    @csrf
                    @if($errors->any())
                        <div class="alert alert-danger small">{{ $errors->first() }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">المبلغ المراد صرفه (ريال)</label>
                        <input type="number" name="amount" class="form-control"
                               step="0.01" min="0.01" max="{{ $balance }}"
                               value="{{ old('amount', $balance) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">تاريخ السند</label>
                        <input type="date" name="payment_date" class="form-control"
                               value="{{ old('payment_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-money-bill-wave me-1"></i>إصدار سند الصرف
                    </button>
                </form>
                @else
                <div class="alert alert-info text-center mb-0">
                    لا يوجد رصيد متاح للصرف.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- أرشيف السندات --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold"><i class="fa fa-archive me-2"></i>أرشيف سندات الصرف</h6>
            </div>
            <div class="card-body p-0">
                @if($payments->isEmpty())
                <div class="text-center text-muted py-4">لا توجد سندات صرف سابقة.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>تاريخ السند</th>
                                <th class="text-end">المبلغ (ريال)</th>
                                <th>الحالة</th>
                                <th>المعتمد</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="text-end fw-bold">{{ number_format($payment->amount, 2) }}</td>
                                <td>
                                    @if($payment->status === 'approved')
                                        <span class="badge bg-success">معتمد</span>
                                    @else
                                        <span class="badge bg-warning text-dark">معلق</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ optional($payment->approvedBy)->name ?? '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('commissions.marketers.payments.print', [$admin->id, $payment->id]) }}"
                                       class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fa fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
