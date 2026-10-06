@extends('admin.layout.indexs.index')

@section('page-title', 'إنشاء إشعار دائن ضريبي')

@section('content')
@php
    $isDelegation = (bool) $invoice->ingaz_delegation_id;
    $isGeneral = !$invoice->contract_id && !$invoice->ingaz_delegation_id;
    $sourceLabel = $isGeneral ? 'عامة' : ($isDelegation ? 'تفويض إنجاز' : 'استقدام');
    $sourceNumber = $isGeneral ? ($invoice->legacy_invoice_number ?: $invoice->id) : ($isDelegation ? $invoice->ingaz_delegation_id : $invoice->contract_id);
    $sourceClient = $isGeneral ? $invoice->client : ($isDelegation ? $invoice->ingazDelegation?->user : $invoice->contract?->user);
@endphp
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">إشعار دائن لفاتورة {{ $sourceLabel }} #{{ $invoice->legacy_invoice_number ?: $invoice->id }}</h4>
                <div class="text-muted">{{ $isGeneral ? 'الفاتورة' : ($isDelegation ? 'التفويض' : 'العقد') }} #{{ $sourceNumber }} — {{ $sourceClient?->name }}</div>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">رجوع</a>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->has('credit_note'))
                <div class="alert alert-danger">{{ $errors->first('credit_note') }}</div>
            @endif

            <div class="alert alert-info">
                إنشاء هذا الإشعار يعكس القيد المالي والضريبة ورصيد العميل، ولا يغيّر {{ $isGeneral ? 'الفاتورة الأصلية' : ($isDelegation ? 'حالة التفويض' : 'حالة العقد أو العاملة') }}.
                صرف المبلغ للعميل يتم بسند صرف مستقل.
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">المتبقي الخاضع للضريبة</small><div class="fs-5 fw-bold">{{ number_format($remaining['taxable_amount'], 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">المتبقي غير الخاضع</small><div class="fs-5 fw-bold">{{ number_format($remaining['non_taxable_amount'], 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">الضريبة المتبقية</small><div class="fs-5 fw-bold">{{ number_format($remaining['vat_amount'], 2) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted">الإجمالي المتبقي</small><div class="fs-5 fw-bold text-danger">{{ number_format($remaining['total'], 2) }}</div></div></div>
            </div>

            @if($remaining['total'] <= 0)
                <div class="alert alert-warning mb-0">تم إصدار إشعارات دائنة بكامل قيمة هذه الفاتورة.</div>
            @else
            <form method="post" action="{{ route('invoices.credit-notes.store', $invoice->id) }}" id="creditNoteForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">نوع المرتجع</label>
                        <select class="form-select" id="creditMode">
                            <option value="full">مرتجع كامل للرصيد المتبقي</option>
                            <option value="partial">مرتجع جزئي</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">تاريخ الإشعار</label>
                        <input type="date" class="form-control @error('issue_date') is-invalid @enderror" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required>
                        @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="send_to_zatca" value="0">
                            <input class="form-check-input" type="checkbox" name="send_to_zatca" value="1" id="sendToZatca" checked>
                            <label class="form-check-label fw-bold" for="sendToZatca">تجهيز للمشاركة مع ZATCA</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مبلغ خاضع للضريبة قبل الضريبة</label>
                        <input type="number" step="0.01" min="0" max="{{ $remaining['taxable_amount'] }}" class="form-control credit-amount" id="taxableAmount" name="taxable_amount" value="{{ old('taxable_amount', $remaining['taxable_amount']) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">مبلغ غير خاضع للضريبة</label>
                        <input type="number" step="0.01" min="0" max="{{ $remaining['non_taxable_amount'] }}" class="form-control credit-amount" id="nonTaxableAmount" name="non_taxable_amount" value="{{ old('non_taxable_amount', $remaining['non_taxable_amount']) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">سبب إصدار إشعار الدائن</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" name="reason" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="border rounded bg-light p-3 mt-4">
                    <div class="row text-center g-3">
                        <div class="col-md-4"><small class="text-muted">قبل الضريبة</small><div class="fs-5 fw-bold" id="subtotalPreview">0.00</div></div>
                        <div class="col-md-4"><small class="text-muted">ضريبة 15%</small><div class="fs-5 fw-bold" id="vatPreview">0.00</div></div>
                        <div class="col-md-4"><small class="text-muted">إجمالي إشعار الدائن</small><div class="fs-4 fw-bold text-danger" id="totalPreview">0.00</div></div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('سيتم إنشاء إشعار دائن وقيد مالي دائم. هل تريد المتابعة؟')">
                        <i class="fas fa-file-invoice-dollar me-1"></i> اعتماد إشعار الدائن
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const taxable = document.getElementById('taxableAmount');
    const nonTaxable = document.getElementById('nonTaxableAmount');
    const mode = document.getElementById('creditMode');
    if (!taxable || !nonTaxable) return;
    const maximum = @json($remaining);
    const money = value => Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
    const refresh = () => {
        const taxableValue = money(taxable.value);
        const nonTaxableValue = money(nonTaxable.value);
        const vat = money(taxableValue * 0.15);
        document.getElementById('subtotalPreview').textContent = money(taxableValue + nonTaxableValue).toFixed(2);
        document.getElementById('vatPreview').textContent = vat.toFixed(2);
        document.getElementById('totalPreview').textContent = money(taxableValue + nonTaxableValue + vat).toFixed(2);
    };
    mode.addEventListener('change', function () {
        if (this.value === 'full') {
            taxable.value = maximum.taxable_amount;
            nonTaxable.value = maximum.non_taxable_amount;
        } else {
            taxable.value = '0.00';
            nonTaxable.value = '0.00';
        }
        refresh();
    });
    taxable.addEventListener('input', refresh);
    nonTaxable.addEventListener('input', refresh);
    refresh();
});
</script>
@endsection
