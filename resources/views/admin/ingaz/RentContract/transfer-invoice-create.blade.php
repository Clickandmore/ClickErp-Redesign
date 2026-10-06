@extends('admin.layout.indexs.index')
@section('page-title', 'إصدار فاتورة نقل الخدمات')
@section('content')
<div class="card">
    <div class="card-header"><h4>إصدار فاتورة نقل الخدمات — {{ $contract->contract_number ?: $contract->id }}</h4></div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif
        <p>العميل: {{ $contract->user?->name }} — العاملة: {{ $contract->maid?->name }}</p>
        @if ($contract->transferInvoice)
            <div class="alert alert-info">توجد فاتورة نقل خدمات رقم {{ $contract->transferInvoice->id }} لهذا العقد. سيتم ربط الفاتورة الموجودة بطلب النقل.</div>
        @endif
        <form method="POST" action="{{ route('rental.transfer-invoice.store', $contract->id) }}">
            @csrf
            @if ($transfer ?? null)
                <input type="hidden" name="transfer_id" value="{{ $transfer->id }}">
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="issue-date">تاريخ الإصدار</label>
                    <input class="form-control" type="date" id="issue-date" name="issue_date" required
                        min="{{ $contract->closed_at?->format('Y-m-d') }}" max="{{ now()->toDateString() }}" value="{{ old('issue_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vat-percent">الضريبة ضمن المبلغ المسجل</label>
                    @php($selectedVat = $contract->transferInvoice ? ((float) $contract->transferInvoice->vat_amount > 0 ? 15 : 0) : old('vat_percent', 15))
                    @if ($contract->transferInvoice)
                        <input type="hidden" name="vat_percent" value="{{ $selectedVat }}">
                    @endif
                    <select class="form-select" id="vat-percent" name="vat_percent" required {{ $contract->transferInvoice ? 'disabled' : '' }}>
                        <option value="15" {{ $selectedVat == 15 ? 'selected' : '' }}>15% — المبلغ شامل الضريبة</option>
                        <option value="0" {{ $selectedVat == 0 ? 'selected' : '' }}>بدون ضريبة</option>
                    </select>
                </div>
            </div>
            <table class="table mt-4">
                <tr><th>قيمة الخدمة قبل الضريبة</th><td id="transfer-subtotal"></td></tr>
                <tr><th>الضريبة</th><td id="transfer-vat"></td></tr>
                <tr><th>الإجمالي</th><td>{{ number_format($amounts['total_due'], 2) }} ريال</td></tr>
                <tr><th>المدفوع المسجل</th><td>{{ number_format($amounts['paid_amount'], 2) }} ريال</td></tr>
                <tr><th>المتبقي</th><td>{{ number_format($amounts['remaining_amount'], 2) }} ريال</td></tr>
            </table>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="send_to_zatca" value="1" id="send-to-zatca" {{ old('send_to_zatca') ? 'checked' : '' }}>
                <label class="form-check-label" for="send-to-zatca">إدراج الفاتورة في مسار المشاركة مع الزكاة</label>
            </div>
            <button type="submit" class="btn btn-primary">{{ $contract->transferInvoice ? 'ربط الفاتورة بطلب النقل' : 'إصدار فاتورة نقل الخدمات' }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('RentContract.show', $contract->id) }}">العودة للعقد</a>
        </form>
    </div>
</div>
@endsection
@section('js')
<script>
    const transferTotal = @json($amounts['total_due']);
    const transferVatSelect = document.getElementById('vat-percent');
    function updateTransferInvoiceAmounts() {
        const subtotal = Math.round(transferTotal / (1 + Number(transferVatSelect.value) / 100) * 100) / 100;
        document.getElementById('transfer-subtotal').textContent = subtotal.toFixed(2) + ' ريال';
        document.getElementById('transfer-vat').textContent = (transferTotal - subtotal).toFixed(2) + ' ريال';
    }
    transferVatSelect.addEventListener('change', updateTransferInvoiceAmounts);
    updateTransferInvoiceAmounts();
</script>
@endsection
