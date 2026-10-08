@extends('admin.ingaz.invoices.create')

@section('page-title', 'العمليات المالية لعقد الاستقدام')

@section('content')
    <div class="page-toolbar">
        <h1 class="toolbar-title">استكمال العمليات المالية — عقد #{{ $financialContract->id }}</h1>
        <p>{{ $financialContract->user?->name }} — {{ $financialContract->status === 'arrival' ? 'وصول' : 'مكتملة' }}</p>
        <p class="toolbar-text">أصدر الفاتورة أولاً، ثم استكمل فاتورة المورد وتكلفة التفويض وتحويل مساند من هنا. تُحسب العمولة وضريبتها وفق إعدادات النظام. قيود الوصول تُسجل بتاريخ الفاتورة عند تفعيل القيود التلقائية.</p>
        <a href="{{ route('contracts.details', $financialContract->id) }}" class="btn btn-outline-secondary mt-2">تفاصيل العقد</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (!$automaticEntriesEnabled)<div class="alert alert-warning">القيود المحاسبية التلقائية غير مفعلة. سيتم حفظ الفاتورة والتحويل دون إنشاء قيود تلقائية.</div>@endif

    @include('admin.ingaz.RecruitmentContract.partials.financial-dispatch')
    @include('admin.ingaz.RecruitmentContract.partials.financial-delegation-cost')

    @if (!$financialContract->invoice)
        @parent
        <div class="alert alert-info mt-3">تسجيل تحويل مساند متاح هنا بعد إصدار فاتورة العقد.</div>
    @else
        @php
            $invoice = $financialContract->invoice;
            $transferItem = $invoice->musanedTransferItem;
        @endphp
        <div class="editor-panel mb-3">
            <h2 class="panel-title">فاتورة العقد #{{ $invoice->id }}</h2>
            <p class="mt-2">الإجمالي: <strong>{{ number_format($invoice->total_due, 2) }} ريال</strong></p>
            @if (\App\Support\InvoiceAuthorization::canView())
                <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-outline-primary" target="_blank">عرض الفاتورة</a>
            @endif
            @if (\App\Support\InvoiceAuthorization::canPrint())
                <a href="{{ route('invoice.download', $invoice->id) }}" class="btn btn-outline-secondary" target="_blank">طباعة الفاتورة</a>
            @endif
        </div>
        <div class="editor-panel">
            <h2 class="panel-title mb-3">تحويل مساند</h2>
            @if ($transferItem?->transfer)
                <div class="alert alert-success mb-0">
                    تم تسجيل التحويل #{{ $transferItem->transfer->id }} بتاريخ {{ $transferItem->transfer->transfer_date?->format('Y-m-d') }}.
                    المبلغ المحوّل: {{ number_format($transferItem->transferred_amount, 2) }} ريال.
                </div>
            @else
                <form method="post" action="{{ route('RecruitmentContract.lateFinance.transfer', $financialContract->id) }}">
                    @csrf
                    <input type="hidden" name="items[0][contract_id]" value="{{ $financialContract->id }}">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">تاريخ التحويل</label><input type="date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">رقم المرجع</label><input name="reference_number" value="{{ old('reference_number') }}" class="form-control" maxlength="255"></div>
                        <div class="col-md-4"><label class="form-label">الحساب البنكي</label>
                            <select name="bank_account_id" class="form-select" @if($automaticEntriesEnabled) required @endif>
                                <option value="">اختر الحساب</option>
                                @foreach ($bankAccounts as $account)<option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">المبلغ المحوّل فعلياً إلى البنك</label><input type="number" id="workspaceTransferred" name="items[0][transferred_amount]" value="{{ old('items.0.transferred_amount') }}" class="form-control" min="0.01" max="{{ $invoice->total_due }}" step="0.01" required></div>
                        <div class="col-md-4"><label class="form-label">العمولة شاملة ضريبتها</label><input id="workspaceCommission" class="form-control" readonly></div>
                        <div class="col-md-4"><label class="form-label">ضريبة العمولة</label><input id="workspaceTax" class="form-control" readonly></div>
                        <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div>
                    </div>
                    <button class="btn btn-primary mt-3">تسجيل تحويل مساند</button>
                </form>
            @endif
        </div>
    @endif
@endsection

@section('js')
    @if (!$financialContract->invoice)
        @parent
    @else
        <script>
            $(function() {
                $('#workspaceTransferred').on('input', function() {
                    const amount = Number(this.value);
                    const total = @json((float) $financialContract->invoice->total_due);
                    const rate = @json($automaticEntriesEnabled ? (float) $expenseTaxRate : 0);
                    const commission = Math.round((total - amount) * 100) / 100;
                    $('#workspaceCommission').val(commission.toFixed(2));
                    $('#workspaceTax').val((commission > 0 ? commission - commission / (1 + rate / 100) : 0).toFixed(2));
                }).trigger('input');
            });
        </script>
    @endif
    <script>
        $(function() {
            $('#delegationPaymentSource').on('change', function() {
                const source = this.value;
                const select = $('#delegationPaymentAccount');
                select.find('option[data-source]').each(function() {
                    const unavailable = this.dataset.source !== source;
                    this.hidden = unavailable;
                    this.disabled = unavailable;
                    if (unavailable && this.selected) select.val('');
                });
            }).trigger('change');
        });
    </script>
@endsection
