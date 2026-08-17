@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء إشعار دائن لفاتورة تأجير')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">إشعار دائن لفاتورة التأجير #{{ $invoice->id }}</h4>
                <div class="text-muted">العقد #{{ $invoice->rentalContract?->contract_number ?: $invoice->rental_contract_id }} — {{ $invoice->rentalContract?->user?->name }}</div>
            </div>
            <a href="{{ route('RentContract.show', $invoice->rental_contract_id) }}" class="btn btn-outline-secondary">رجوع</a>
        </div>
        <div class="card-body">
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            <div class="alert alert-info">
                هذا المرتجع ينشئ إشعاراً ضريبياً وقيداً عكسياً مستقلاً، ولا ينهي عقد التأجير ولا يغيّر الفاتورة الأصلية.
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">الرصيد المتبقي</small><div class="fs-5 fw-bold text-danger">{{ number_format($remaining, 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">خدمات خاضعة</small><div class="fs-5 fw-bold">{{ number_format($breakdown['taxable_subtotal'], 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">راتب معفى</small><div class="fs-5 fw-bold">{{ number_format($breakdown['salary_exempt_amount'], 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">الضريبة</small><div class="fs-5 fw-bold">{{ number_format($breakdown['vat_amount'], 2) }}</div></div></div>
            </div>
            @if($remaining <= 0)
                <div class="alert alert-warning mb-0">تم إصدار إشعارات دائنة بكامل قيمة الفاتورة.</div>
            @else
            <form method="post" action="{{ route('rental-invoices.credit-notes.store', $invoice->id) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">تاريخ الإشعار</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">قيمة المرتجع الإجمالية</label>
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $remaining }}" class="form-control" value="{{ old('amount', $remaining) }}" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="send_to_zatca" value="0">
                            <input class="form-check-input" type="checkbox" name="send_to_zatca" value="1" id="sendToZatca" checked>
                            <label class="form-check-label fw-bold" for="sendToZatca">تجهيز للمشاركة مع ZATCA</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">سبب المرتجع</label>
                        <textarea name="reason" class="form-control" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('RentContract.show', $invoice->rental_contract_id) }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button class="btn btn-danger" onclick="return confirm('سيتم إنشاء إشعار دائن وقيد مالي دائم. هل تريد المتابعة؟')">
                        <i class="fas fa-file-invoice-dollar me-1"></i> اعتماد إشعار الدائن
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
