@extends('admin.layout.indexs.index')

@section('page-title', 'صرف عمولة ' . $admin->name)

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.finance.index') }}">الإدارة المالية للعمولات</a></li>
    <li class="breadcrumb-item active">{{ $admin->name }}</li>
@endsection

@section('content')
<style>
    .payment-hero { background: linear-gradient(135deg, #064e3b, #047857); color: #fff; border-radius: 16px; }
    .payment-step { border: 1px solid #e2e8f0; border-radius: 13px; padding: 13px; background: #fff; min-height: 88px; }
    .payment-step.done { border-color: #86efac; background: #f0fdf4; }
    .payment-step.current { border-color: #fbbf24; background: #fffbeb; box-shadow: 0 3px 14px rgba(245,158,11,.12); }
    .step-number { width: 30px; height: 30px; border-radius: 50%; display: inline-grid; place-items: center; background: #cbd5e1; color: #334155; font-weight: 800; }
    .done .step-number { background: #16a34a; color: #fff; }
    .current .step-number { background: #f59e0b; color: #fff; }
    .payment-card { border: 0; border-radius: 14px; box-shadow: 0 4px 18px rgba(15,23,42,.07); }
    .balance-panel { background: linear-gradient(145deg, #f0fdf4, #ecfdf5); border: 1px solid #bbf7d0; }
    .amount-input { font-size: 1.45rem; font-weight: 800; text-align: center; min-height: 55px; }
    .voucher-row:hover { background: #f8fafc; }
    .accounting-box { background: #f8fafc; border: 1px solid #dbeafe; border-radius: 12px; padding: 16px; }
    .finance-state { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
</style>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="fa fa-check-circle me-1"></i>{{ session('success') }}</div>
@endif
@if(isset($errors) && $errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><i class="fa fa-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
@endif

<div class="payment-hero p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="small opacity-75 mb-1">الإدارة المالية ← صرف العمولات</div>
        <h4 class="mb-2 fw-bold">إصدار سند صرف للموظف: {{ $admin->name }}</h4>
        <div class="opacity-75">لا يمكن صرف أكثر من الرصيد الناتج عن عمولات معتمدة من التشغيل.</div>
    </div>
    <div class="d-flex gap-2">
        @if($balance > 0 && $approvedBatches->isNotEmpty())
            <button type="button" class="btn btn-outline-light" data-bs-toggle="collapse" data-bs-target="#balanceCorrection"><i class="fa fa-pen me-1"></i>تصحيح اعتماد</button>
        @endif
        <a href="{{ route('finance.bonds-sarf.index') }}" class="btn btn-warning"><i class="fa fa-file-invoice-dollar me-1"></i> سندات الصرف المالية</a>
        <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-outline-light"><i class="fa fa-clipboard-list me-1"></i> ملف العمليات</a>
        <a href="{{ route('commissions.finance.index') }}" class="btn btn-light"><i class="fa fa-arrow-right me-1"></i> المركز المالي</a>
    </div>
</div>

@if($balance > 0 && $approvedBatches->isNotEmpty())
<div class="collapse mb-4" id="balanceCorrection">
    <div class="card border-warning shadow-sm">
        <div class="card-header bg-warning bg-opacity-10"><strong><i class="fa fa-triangle-exclamation me-1"></i>تصحيح اعتماد عمولة</strong></div>
        <div class="card-body">
            <div class="alert alert-warning small">لا يتم تعديل أو حذف الاعتماد الأصلي حتى تبقى المراجعة المالية صحيحة. يسجل النظام قيدًا مدينًا لتخفيض رصيد الموظفة، فلا يصبح المبلغ المصحح متاحًا للصرف.</div>
            <form method="POST" action="{{ route('commissions.marketers.payments.adjustments.store', $admin->id) }}" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label fw-semibold">دفعة الاعتماد المراد تصحيحها</label>
                    <select name="batch_id" class="form-select" required>
                        @foreach($approvedBatches as $batch)
                            <option value="{{ $batch->id }}" @selected((string) old('batch_id') === (string) $batch->id)>دفعة #{{ $batch->id }} — {{ number_format($batch->total_commission, 2) }} ر.س — {{ optional($batch->approved_at)->format('Y-m-d') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">مبلغ التخفيض</label>
                    <div class="input-group"><input type="number" name="amount" step="0.01" min="0.01" max="{{ $balance }}" class="form-control" value="{{ old('amount') }}" required><span class="input-group-text">ر.س</span></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">سبب التصحيح</label>
                    <input name="reason" class="form-control" value="{{ old('reason') }}" placeholder="مثال: اعتماد نسبة 100% بالخطأ" required>
                </div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-warning"><i class="fa fa-save me-1"></i>تسجيل قيد التصحيح</button></div>
            </form>
            <hr>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><strong>هل تريد إعادة حساب العقود بدل تسجيل قيد؟</strong><div class="small text-muted">متاح فقط للدفعة التي لم يُصرف منها شيء ولم تقع بعدها أي حركة على الرصيد.</div></div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($rollbackBatches as $batch)
                        <form method="POST" action="{{ route('commissions.marketers.payments.batches.rollback', [$admin->id, $batch->id]) }}" onsubmit="return confirm('سيُلغى اعتماد الدفعة #{{ $batch->id }} ويُحذف قيد استحقاقها وتعود عقودها إلى شاشة العمليات لإعادة الاحتساب. هل تريد المتابعة؟');">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm"><i class="fa fa-rotate-left me-1"></i>التراجع عن دفعة #{{ $batch->id }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6"><div class="payment-step done">
        <div><span class="step-number"><i class="fa fa-check"></i></span><strong class="ms-2">اعتماد التشغيل</strong></div>
        <div class="small text-muted mt-2">عمولات معتمدة: {{ number_format($summary['approved'], 2) }} ر.س</div>
    </div></div>
    <div class="col-lg-3 col-sm-6"><div class="payment-step done">
        <div><span class="step-number"><i class="fa fa-check"></i></span><strong class="ms-2">تكوين الرصيد</strong></div>
        <div class="small text-muted mt-2">{{ $balance < 0 ? 'مستحق للشركة: ' . number_format(abs($balance), 2) : 'المتاح حاليًا: ' . number_format($balance, 2) }} ر.س</div>
    </div></div>
    <div class="col-lg-3 col-sm-6"><div class="payment-step {{ $balance > 0 ? 'current' : 'done' }}">
        <div><span class="step-number">3</span><strong class="ms-2">إصدار سند الصرف</strong></div>
        <div class="small text-muted mt-2">{{ $balance > 0 ? 'حدد المبلغ والتاريخ ثم أصدر السند.' : ($balance < 0 ? 'يوجد مبلغ مستحق للشركة بسبب عكس عمولات.' : 'لا يوجد رصيد بانتظار الصرف.') }}</div>
    </div></div>
    <div class="col-lg-3 col-sm-6"><div class="payment-step {{ $summary['payments_count'] > 0 ? 'done' : '' }}">
        <div><span class="step-number">4</span><strong class="ms-2">الاعتماد والقيد</strong></div>
        <div class="small text-muted mt-2">يظهر السند في المالية، وبعد اعتماده ينشأ القيد تلقائيًا.</div>
    </div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card payment-card h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-1 fw-bold"><i class="fa fa-file-invoice-dollar text-success me-2"></i>بيانات سند الصرف الجديد</h5>
                <div class="small text-muted">راجع اسم الموظف والرصيد قبل تأكيد الإصدار.</div>
            </div>
            <div class="card-body">
                @if($balance > 0)
                    <form method="POST" action="{{ route('commissions.marketers.payments.store', $admin->id) }}" id="paymentForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">الموظف المستلم</label>
                                <input class="form-control bg-light" value="{{ $admin->name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">تاريخ السند</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">المبلغ المراد صرفه</label>
                                <div class="input-group">
                                    <input id="paymentAmount" type="number" name="amount" class="form-control amount-input" step="0.01" min="0.01" max="{{ $balance }}" value="{{ old('amount', $balance) }}" required>
                                    <span class="input-group-text fw-bold">ريال</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-success amount-choice" data-value="{{ $balance }}">صرف كامل الرصيد</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary amount-choice" data-value="{{ round($balance / 2, 2) }}">نصف الرصيد</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">بيان السند / ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="مثال: صرف عمولات شهر يوليو 2026">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="accounting-box">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                        <div>
                                            <div class="fw-bold text-primary"><i class="fa fa-book me-1"></i> التوجيه المحاسبي</div>
                                            <div class="small text-muted">هذه البيانات تنشئ سند الصرف داخل الإدارة المالية.</div>
                                        </div>
                                        <span class="badge bg-primary">مطلوب</span>
                                    </div>
                                    <div class="row g-3">
                                        @if($hasAccrualBatches)
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">حساب استحقاق الموظف (مدين)</label>
                                                <input class="form-control bg-light" value="{{ $defaultPayableAccount ? trim(($defaultPayableAccount->code ? $defaultPayableAccount->code . ' - ' : '') . $defaultPayableAccount->name) : 'يُحدد تلقائيًا من قيد الاعتماد' }}" readonly>
                                            </div>
                                        @else
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">حساب مصروف العمولة القديمة (مدين)</label>
                                                <select name="debit_account_id" class="form-select commission-account-select" required>
                                                    <option value="">اختر حساب المصروف</option>
                                                </select>
                                            </div>
                                        @endif
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">حساب الصندوق / البنك (دائن)</label>
                                            <select name="credit_account_id" class="form-select commission-account-select" required>
                                                <option value="">اختر حساب الدفع</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">طريقة الدفع</label>
                                            <select name="payment_type" class="form-select payment-type-select" required>
                                                @foreach($financialForm['paymentTypes'] as $type)
                                                    <option value="{{ $type->value }}" @selected((string) old('payment_type', 1) === (string) $type->value)>{{ $type->lang() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">مركز التكلفة {{ $financialForm['costCenterRequired'] ? '(مطلوب)' : '' }}</label>
                                            <select name="cost_center_id" class="form-select commission-cost-center-select" @required($financialForm['costCenterRequired'])>
                                                <option value="">بدون مركز تكلفة</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 transfer-number-wrap d-none">
                                            <label class="form-label fw-semibold">رقم عملية التحويل</label>
                                            <input name="transfer_number" class="form-control" value="{{ old('transfer_number') }}" maxlength="100">
                                        </div>
                                        <div class="col-md-4 check-fields d-none">
                                            <label class="form-label fw-semibold">رقم الشيك</label>
                                            <input name="check_num" class="form-control" value="{{ old('check_num') }}" maxlength="100">
                                        </div>
                                        <div class="col-md-4 check-fields d-none">
                                            <label class="form-label fw-semibold">تاريخ الشيك</label>
                                            <input type="date" name="check_date" class="form-control" value="{{ old('check_date') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning small mt-3 mb-3">
                            <i class="fa fa-info-circle me-1"></i>
                            عند الإصدار يُخصم المبلغ من رصيد الموظف وينشأ سند في «سندات الصرف». القيد ينشأ عند اعتماد السند وفق إعدادات المالية.
                        </div>
                        @if($hasPendingAccrualConstraint)
                            <div class="alert alert-danger small mb-3">
                                <i class="fa fa-lock me-1"></i> يوجد قيد استحقاق عمولة بانتظار الاعتماد المالي؛ لا يمكن إصدار سند الصرف قبله.
                            </div>
                        @endif
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold" @disabled($hasPendingAccrualConstraint)>
                            <i class="fa fa-check-circle me-1"></i> إصدار سند الصرف وخصم الرصيد
                        </button>
                    </form>
                @else
                    <div class="text-center py-5">
                        <i class="fa {{ $balance < 0 ? 'fa-undo text-danger' : 'fa-check-circle text-success' }} mb-3" style="font-size:54px"></i>
                        <h5 class="fw-bold">{{ $balance < 0 ? 'يوجد مبلغ مستحق للشركة' : 'لا يوجد رصيد بانتظار الصرف' }}</h5>
                        <p class="text-muted">{{ $balance < 0 ? 'القيمة ناتجة عن عكس عمولات عقود مستردة أو مرتجعة: ' . number_format(abs($balance), 2) . ' ر.س.' : 'يجب اعتماد عمولات جديدة من شاشة التشغيل قبل إصدار سند آخر.' }}</p>
                        <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-outline-primary">عرض ملف العمليات</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card payment-card balance-panel h-100">
            <div class="card-body p-4">
                <div class="text-center border-bottom pb-3 mb-3">
                    <div class="text-muted small">{{ $balance < 0 ? 'مستحق للشركة على المسوق' : 'الرصيد المتاح للصرف الآن' }}</div>
                    <div class="display-5 fw-bold {{ $balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($balance), 2) }} <small class="fs-6">ر.س</small></div>
                </div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">إجمالي المعتمد</span><strong>{{ number_format($summary['approved'], 2) }} ر.س</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">إجمالي ما صُرف</span><strong class="text-danger">- {{ number_format($summary['paid'], 2) }} ر.س</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">عمولات معكوسة لعقود مستردة</span><strong class="text-danger">- {{ number_format($summary['reversed'], 2) }} ر.س</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">عمولات مقفلة بصفر</span><strong class="text-danger">- {{ number_format($summary['zero_adjustments'], 2) }} ر.س</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">تصحيحات الاعتمادات</span><strong class="text-danger">- {{ number_format($summary['adjustments'], 2) }} ر.س</strong></div>
                <div class="d-flex justify-content-between py-3 border-top mt-2"><span class="fw-bold">{{ $summary['balance'] < 0 ? 'مستحق للشركة' : 'الرصيد الحالي' }}</span><strong class="{{ $summary['balance'] < 0 ? 'text-danger' : 'text-success' }} fs-5">{{ number_format(abs($summary['balance']), 2) }} ر.س</strong></div>
                <div class="bg-white rounded p-3 mt-3 small text-muted">
                    <i class="fa fa-shield-alt text-success me-1"></i>
                    النظام يمنع تكرار الصرف أو إدخال مبلغ أكبر من الرصيد حتى عند وجود أكثر من مستخدم مالي.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card payment-card mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div><h5 class="mb-1 fw-bold"><i class="fa fa-book text-primary me-2"></i>كشف حساب الموظف</h5><div class="small text-muted">الاعتماد دائن للموظف، وسند الصرف أو التصحيح مدين عليه.</div></div>
        <span class="badge bg-light text-dark border">{{ $balanceTransactions->count() }} حركة</span>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>التاريخ</th><th>البيان</th><th>المرجع</th><th class="text-end">دائن للموظف</th><th class="text-end">مدين على الموظف</th><th class="text-end">الرصيد</th><th>بواسطة</th></tr></thead>
        <tbody>@forelse($balanceTransactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td><td>{{ $tx->notes }}</td>
                <td>#{{ $tx->reference_id }}</td>
                <td class="text-end text-success fw-bold">{{ $tx->type === 'credit' ? number_format($tx->amount, 2) : '—' }}</td>
                <td class="text-end text-danger fw-bold">{{ $tx->type === 'debit' ? number_format($tx->amount, 2) : '—' }}</td>
                <td class="text-end fw-bold">{{ number_format($tx->balance_after, 2) }} ر.س</td><td>{{ $tx->createdBy?->name ?? 'النظام' }}</td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-4">لا توجد حركات مالية حتى الآن.</td></tr>@endforelse</tbody>
    </table></div>
</div>

<div class="card payment-card">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><h5 class="mb-1 fw-bold"><i class="fa fa-archive text-secondary me-2"></i>أرشيف سندات {{ $admin->name }}</h5><div class="small text-muted">كل سند معتمد يمكن فتحه وطباعته في أي وقت.</div></div>
        <span class="badge bg-light text-dark border">{{ $payments->count() }} سند</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>سند العمولة</th><th>التاريخ</th><th class="text-end">المبلغ</th><th>سند المالية</th><th>القيد المحاسبي</th><th>اعتمد بواسطة</th><th class="text-center">الإجراءات</th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                <tr class="voucher-row">
                    <td class="fw-bold">#{{ $payment->id }}</td>
                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                    <td class="text-end fw-bold text-success">{{ number_format($payment->amount, 2) }} ر.س</td>
                    <td>
                        @if($payment->bond)
                            <div class="finance-state">
                                <span class="badge {{ $payment->bond->is_confirm ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $payment->bond->is_confirm ? 'معتمد' : 'بانتظار الاعتماد' }}
                                </span>
                                <strong>#{{ $payment->bond->code }}</strong>
                            </div>
                        @else
                            <span class="badge bg-secondary">سند داخلي قديم</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->bond?->constraint_id)
                            <a href="{{ route('finance.add-constraint.show', $payment->bond->constraint_id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-book me-1"></i>#{{ $payment->bond->constraint_id }}
                            </a>
                        @elseif($payment->bond)
                            <span class="text-warning small">ينشأ بعد الاعتماد</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $payment->approvedBy?->name ?? '-' }}</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('commissions.marketers.payments.print', [$admin->id, $payment->id]) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fa fa-print me-1"></i>طباعة</a>
                            @if($payment->bond)
                                <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $payment->bond->id, 'type' => 'sarf']) }}" class="btn btn-sm btn-outline-success" target="_blank"><i class="fa fa-file-invoice-dollar me-1"></i>سند المالية</a>
                            @else
                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#linkBond{{ $payment->id }}">
                                    <i class="fa fa-link me-1"></i>ترحيل إلى المالية
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @if(!$payment->bond)
                    <tr class="collapse" id="linkBond{{ $payment->id }}">
                        <td colspan="7" class="bg-light p-3">
                            <form method="POST" action="{{ route('commissions.marketers.payments.finance-bond.store', [$admin->id, $payment->id]) }}" class="legacy-bond-form">
                                @csrf
                                <div class="alert alert-info py-2 small">
                                    سيتم إنشاء سند صرف مالي بقيمة {{ number_format($payment->amount, 2) }} ر.س دون خصم الرصيد مرة أخرى.
                                </div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-3">
                                        <label class="form-label small fw-bold">حساب المصروف للدفعة القديمة (مدين)</label>
                                        <select name="debit_account_id" class="form-select commission-account-select" required>
                                            <option value="">اختر حساب المصروف</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="form-label small fw-bold">حساب الصندوق / البنك (دائن)</label>
                                        <select name="credit_account_id" class="form-select commission-account-select" required>
                                            <option value="">اختر الحساب</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label small fw-bold">طريقة الدفع</label>
                                        <select name="payment_type" class="form-select payment-type-select" required>
                                            @foreach($financialForm['paymentTypes'] as $type)
                                                <option value="{{ $type->value }}">{{ $type->lang() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label small fw-bold">مركز التكلفة</label>
                                        <select name="cost_center_id" class="form-select commission-cost-center-select" @required($financialForm['costCenterRequired'])>
                                            <option value="">بدون</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <button class="btn btn-primary w-100" type="submit"><i class="fa fa-check me-1"></i>تأكيد الترحيل</button>
                                    </div>
                                    <div class="col-lg-3 transfer-number-wrap d-none">
                                        <label class="form-label small fw-bold">رقم عملية التحويل</label>
                                        <input name="transfer_number" class="form-control" maxlength="100">
                                    </div>
                                    <div class="col-lg-3 check-fields d-none">
                                        <label class="form-label small fw-bold">رقم الشيك</label>
                                        <input name="check_num" class="form-control" maxlength="100">
                                    </div>
                                    <div class="col-lg-3 check-fields d-none">
                                        <label class="form-label small fw-bold">تاريخ الشيك</label>
                                        <input type="date" name="check_date" class="form-control">
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa fa-receipt d-block mb-2" style="font-size:38px;opacity:.35"></i>لم يصدر سند صرف لهذا الموظف حتى الآن.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    function togglePaymentFields(select) {
        const form = $(select).closest('form');
        const type = Number($(select).val());
        const transferTypes = [3, 4, 5, 6];
        form.find('.transfer-number-wrap').toggleClass('d-none', !transferTypes.includes(type));
        form.find('.check-fields').toggleClass('d-none', type !== 2);
        form.find('[name="check_num"], [name="check_date"]').prop('required', type === 2);
    }
    $('.payment-type-select').each(function () { togglePaymentFields(this); });
    $(document).on('change', '.payment-type-select', function () { togglePaymentFields(this); });

    if ($.fn.select2) {
        const optionsUrl = @json(route('commissions.payment-options'));
        function initRemoteSelect(selector, type, placeholder) {
            $(selector).select2({
                width: '100%',
                dir: 'rtl',
                placeholder: placeholder,
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: optionsUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { type: type, q: params.term || '', page: params.page || 1 };
                    },
                    processResults: function (data) { return data; },
                    cache: true
                }
            });
        }
        initRemoteSelect('.commission-account-select', 'account', 'ابحث بالاسم أو رقم الحساب');
        initRemoteSelect('.commission-cost-center-select', 'cost_center', 'ابحث عن مركز التكلفة');
    }

    $('.amount-choice').on('click', function () {
        $('#paymentAmount').val($(this).data('value')).trigger('focus');
    });
    $('#paymentForm').on('submit', function (event) {
        const amount = Number($('#paymentAmount').val() || 0);
        const balance = Number(@json($balance));
        if (amount <= 0 || amount > balance) {
            event.preventDefault();
            alert('المبلغ يجب أن يكون أكبر من صفر وألا يتجاوز الرصيد المتاح.');
        }
    });
});
</script>
@endsection
