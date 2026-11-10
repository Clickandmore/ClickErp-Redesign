@extends('click.layout.indexs.index')

@section('content')
    @php
        $typeLabel = $requestItem->type_label;
        $statusLabel = $requestItem->status_label;
        $priorityLabel = $requestItem->priority_label;
        $contractTypeLabel = $requestItem->contract_type_label;
        $financeBondTypeLabel = $bondTypes[$requestItem->finance_bond_type] ?? null;
        $isCreator = (int) $requestItem->created_by_id === (int) auth('admin')->id();
        $canEdit = $isCreator
            && in_array($requestItem->status, [\App\Models\FinancialRequest::STATUS_PREPARING, \App\Models\FinancialRequest::STATUS_FOR_APPROVAL, \App\Models\FinancialRequest::STATUS_REJECTED], true)
            && @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CREATE, \App\PermKeys::FINANCIAL_REQUESTS_EDIT, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        $isApprover = @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        $canRequestClosing = @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_REQUEST_CLOSING, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        $canClose = @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CLOSE, \App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        $actions = [];
        if ($isApprover && $requestItem->status === \App\Models\FinancialRequest::STATUS_FOR_APPROVAL) {
            $actions = ['approve' => [\App\Models\FinancialRequest::STATUS_APPROVED, 'اعتماد', 'success'], 'reject' => [\App\Models\FinancialRequest::STATUS_PREPARING, 'غير معتمد', 'danger']];
        } elseif ($isCreator && in_array($requestItem->status, [\App\Models\FinancialRequest::STATUS_PREPARING, \App\Models\FinancialRequest::STATUS_REJECTED], true)) {
            $actions = ['resubmit' => [\App\Models\FinancialRequest::STATUS_FOR_APPROVAL, 'إعادة الإرسال للاعتماد', 'primary']];
        } elseif ($isCreator && $requestItem->status === \App\Models\FinancialRequest::STATUS_APPROVED) {
            $actions = ['paid' => [\App\Models\FinancialRequest::STATUS_PAID, 'تم السداد', 'primary']];
        } elseif ($canRequestClosing && $requestItem->status === \App\Models\FinancialRequest::STATUS_PAID) {
            $actions = ['verify' => [\App\Models\FinancialRequest::STATUS_REQUEST_CLOSING, 'طلب الإقفال', 'warning']];
        } elseif ($canClose && $requestItem->status === \App\Models\FinancialRequest::STATUS_REQUEST_CLOSING) {
            $actions = ['close' => [\App\Models\FinancialRequest::STATUS_CLOSED, 'إقفال وإغلاق الطلب', 'success']];
        } elseif ($canClose && $requestItem->status === \App\Models\FinancialRequest::STATUS_RECEIPT_ATTACHED) {
            $actions = ['close' => [\App\Models\FinancialRequest::STATUS_CLOSED, 'إقفال وإغلاق الطلب', 'success'], 'incomplete' => [\App\Models\FinancialRequest::STATUS_REQUEST_CLOSING, 'غير مكتمل', 'danger']];
        }
    @endphp

    <style>
        .financial-request-page { --fr-primary: #25324b; --fr-muted: #6c757d; }
        .financial-request-page .request-hero {
            background: linear-gradient(135deg, #25324b 0%, #344767 100%);
            border: 0;
            border-radius: 14px;
            color: #fff;
            box-shadow: 0 8px 24px rgba(37, 50, 75, .14);
        }
        .financial-request-page .request-hero .hero-label { color: rgba(255,255,255,.68); font-size: .78rem; margin-bottom: .25rem; }
        .financial-request-page .request-hero .hero-value { font-size: .95rem; font-weight: 600; }
        .financial-request-page .section-card { border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 3px 14px rgba(31,45,61,.05); overflow: hidden; }
        .financial-request-page .section-card .card-header { background: #fff; border-bottom: 1px solid #edf0f3; padding: 1rem 1.25rem; }
        .financial-request-page .section-title { color: var(--fr-primary); font-size: 1rem; font-weight: 700; margin: 0; }
        .financial-request-page .detail-item { padding: .8rem 0; border-bottom: 1px dashed #e8ebef; }
        .financial-request-page .detail-item:last-child { border-bottom: 0; }
        .financial-request-page .detail-label { color: var(--fr-muted); font-size: .8rem; margin-bottom: .25rem; }
        .financial-request-page .detail-value { color: #273142; font-weight: 600; overflow-wrap: anywhere; }
        .financial-request-page .amount-value { color: #198754; font-size: 1.35rem; }
        .financial-request-page .timeline { position: relative; padding-right: 1.5rem; }
        .financial-request-page .timeline::before { background: #dfe4ea; content: ""; position: absolute; right: .38rem; top: .5rem; bottom: .5rem; width: 2px; }
        .financial-request-page .timeline-item { position: relative; padding: 0 1.25rem 1.4rem 0; }
        .financial-request-page .timeline-item:last-child { padding-bottom: 0; }
        .financial-request-page .timeline-dot { background: #fff; border: 3px solid #415a77; border-radius: 50%; height: .85rem; position: absolute; right: -.02rem; top: .25rem; width: .85rem; z-index: 1; }
        .financial-request-page .timeline-item:first-child .timeline-dot { border-color: #198754; }
        .financial-request-page .timeline-meta { color: var(--fr-muted); font-size: .78rem; }
        .financial-request-page .action-card { position: sticky; top: 1rem; }
        .financial-request-page .attachment-link { border: 1px solid #e1e6eb; border-radius: 9px; color: #344767; display: flex; gap: .75rem; padding: .75rem; text-decoration: none; }
        .financial-request-page .attachment-link:hover { background: #f8f9fa; border-color: #b9c2cc; }
        @media (max-width: 991.98px) { .financial-request-page .action-card { position: static; } }
    </style>

    <div class="container-fluid financial-request-page">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>تعذر تنفيذ الإجراء:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <div class="text-muted small mb-1">الطلبات المالية / تفاصيل الطلب</div>
                <h3 class="mb-0">طلب مالي <span dir="ltr">{{ $requestItem->request_no }}</span></h3>
            </div>
            <div class="d-flex gap-2">
                @if($canEdit)
                    <a class="btn btn-outline-dark px-3" href="{{ route('financial-requests.edit', $requestItem) }}">
                        <i class="fa fa-edit ms-1"></i> {{ __('financial_requests.edit') }}
                    </a>
                @endif
                <a class="btn btn-outline-secondary px-3" href="{{ route('financial-requests.index') }}">
                    <i class="fa fa-arrow-right ms-1"></i> رجوع للقائمة
                </a>
            </div>
        </div>

        <div class="card request-hero mb-3">
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white bg-opacity-10 p-3"><i class="fa fa-file-invoice-dollar fa-lg"></i></div>
                            <div>
                                <div class="hero-label">نوع الطلب</div>
                                <div class="fs-5 fw-bold">{{ $typeLabel }}</div>
                                <div class="mt-2"><span class="badge bg-light text-dark px-3 py-2">{{ $statusLabel }}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2"><div class="hero-label">المبلغ</div><div class="hero-value">{{ $requestItem->amount ? number_format((float) $requestItem->amount, 2) : '-' }} ر.س</div></div>
                    <div class="col-6 col-lg-2"><div class="hero-label">الأولوية</div><div class="hero-value">{{ $priorityLabel }}</div></div>
                    <div class="col-6 col-lg-2"><div class="hero-label">رافع الطلب</div><div class="hero-value">{{ $requestItem->creator?->name ?: '-' }}</div></div>
                    <div class="col-6 col-lg-2"><div class="hero-label">تاريخ الرفع</div><div class="hero-value" dir="ltr">{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card section-card">
                    <div class="card-header"><h5 class="section-title"><i class="fa fa-info-circle ms-2 text-muted"></i> البيانات الأساسية</h5></div>
                    <div class="card-body px-4">
                        <div class="row gx-4">
                            <div class="col-md-6 detail-item"><div class="detail-label">الفرع</div><div class="detail-value">{{ $requestItem->branch?->name_ar ?: $requestItem->branch?->name_en ?: '-' }}</div></div>
                            <div class="col-md-6 detail-item"><div class="detail-label">تاريخ الطلب</div><div class="detail-value" dir="ltr">{{ $requestItem->request_date?->format('Y-m-d') ?: '-' }}</div></div>
                            <div class="col-md-6 detail-item">
                                <div class="detail-label">المبلغ</div>
                                <div class="detail-value amount-value">{{ $requestItem->amount ? number_format((float) $requestItem->amount, 2) : '-' }} <small>ر.س</small></div>
                                @if(AdminGuardHasPerm(\App\PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT) && $requestItem->canChangeAmount())
                                    <details class="mt-2" @if($errors->has('amount')) open @endif>
                                        <summary class="text-primary">تعديل المبلغ</summary>
                                        <form method="POST" action="{{ route('financial-requests.amount', $requestItem) }}" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="request-amount" class="form-label">المبلغ الجديد (ر.س)</label>
                                            <input id="request-amount" type="number" name="amount" min="0.01" max="9999999999.99" step="0.01" required class="form-control" value="{{ old('amount', $requestItem->amount) }}">
                                            @error('amount')<div class="text-danger">{{ $message }}</div>@enderror
                                            @if($requestItem->status === \App\Models\FinancialRequest::STATUS_APPROVED)
                                                <small class="text-muted d-block mt-1">تعديل المبلغ يعيد الطلب للاعتماد.</small>
                                            @endif
                                            <button type="submit" class="btn btn-primary btn-sm mt-2">حفظ المبلغ</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                            <div class="col-md-6 detail-item"><div class="detail-label">العميل</div><div class="detail-value">{{ $requestItem->client_name ?: '-' }} @if($requestItem->client_identity)<small class="text-muted d-block">{{ $requestItem->client_identity }}</small>@endif</div></div>
                            <div class="col-md-6 detail-item"><div class="detail-label">العاملة</div><div class="detail-value">{{ $requestItem->maid_name ?: '-' }} @if($requestItem->maid_passport)<small class="text-muted d-block">{{ $requestItem->maid_passport }}</small>@endif</div></div>
                            <div class="col-md-6 detail-item"><div class="detail-label">العقد</div><div class="detail-value">{{ $requestItem->contract_number ?: '-' }} @if($requestItem->contract_number)<small class="text-muted d-block">{{ $contractTypeLabel }}</small>@endif</div></div>
                            <div class="col-md-6 detail-item">
                                <div class="detail-label">السند المالي</div><div class="detail-value">
                                @if($requestItem->financeBond)
                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $requestItem->financeBond->id, 'type' => $requestItem->finance_bond_type]) }}" target="_blank">#{{ $requestItem->financeBond->id }}</a>
                                    @if($financeBondTypeLabel)<small class="text-muted">- {{ $financeBondTypeLabel }}</small>@endif
                                @else
                                    -
                                @endif
                                </div>
                            </div>
                            <div class="col-12 detail-item"><div class="detail-label">وصف الطلب</div><div class="detail-value fw-normal" style="white-space: pre-line">{{ $requestItem->description ?: '-' }}</div></div>
                        </div>
                    </div>
                </div>

                @php
                    $p = $requestItem->payload ?? [];
                    $nature = data_get($p, 'debit_credit');
                    $method = data_get($p, 'payment_method');
                    $bankRows = array_filter([
                        'اسم صاحب الحساب' => data_get($p, 'account_holder_name'),
                        'اسم البنك' => data_get($p, 'bank_name'),
                        'رقم الحساب' => data_get($p, 'account_number'),
                        'رقم الآيبان' => data_get($p, 'iban'),
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <div class="card section-card mt-3">
                    <div class="card-header"><h5 class="section-title"><i class="fa fa-book ms-2 text-muted"></i> البيانات المحاسبية</h5></div>
                    <div class="card-body px-4">
                        <div class="row gx-4">
                            <div class="col-md-6 detail-item"><div class="detail-label">المدفوع له</div><div class="detail-value">{{ data_get($p, 'paid_to') ?: '-' }}</div></div>
                            <div class="col-md-6 detail-item"><div class="detail-label">الاسم المحاسبي</div><div class="detail-value">{{ data_get($p, 'accounting_name') ?: '-' }}</div></div>
                            <div class="col-md-6 detail-item">
                                <div class="detail-label">الحساب المحاسبي</div>
                                <div class="detail-value" dir="auto">
                                    @if($requestAccountLabel)
                                        {{ $requestAccountLabel }}
                                    @elseif($partyAccount)
                                        {{ $partyAccount['text'] }} <small class="text-muted d-block">حساب العميل المربوط</small>
                                    @else
                                        <span class="text-warning">غير محدد</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 col-6 detail-item">
                                <div class="detail-label">طبيعة المبلغ</div>
                                <div class="detail-value">
                                    @if($nature === 'debit')<span class="badge bg-danger">مدين</span>
                                    @elseif($nature === 'credit')<span class="badge bg-success">دائن</span>
                                    @else - @endif
                                </div>
                            </div>
                            <div class="col-md-3 col-6 detail-item"><div class="detail-label">طريقة الدفع</div><div class="detail-value">{{ ['bank_transfer' => 'تحويل بنكي', 'cash' => 'نقدي'][$method] ?? ($method ?: '-') }}</div></div>
                            @foreach($bankRows as $label => $value)
                                <div class="col-md-6 detail-item"><div class="detail-label">{{ $label }}</div><div class="detail-value" dir="auto">{{ $value }}</div></div>
                            @endforeach
                            @if($requestItem->financeBond)
                                <div class="col-12 detail-item">
                                    <div class="detail-label">قيد السند</div>
                                    <div class="detail-value">
                                        <span class="badge bg-danger-subtle text-danger border">مدين</span> {{ $requestItem->financeBond->debitAccount ? $requestItem->financeBond->debitAccount->code . ' - ' . $requestItem->financeBond->debitAccount->name : '-' }}
                                        <span class="mx-2 text-muted">|</span>
                                        <span class="badge bg-success-subtle text-success border">دائن</span> {{ $requestItem->financeBond->creditAccount ? $requestItem->financeBond->creditAccount->code . ' - ' . $requestItem->financeBond->creditAccount->name : '-' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @php
                    $typeDetails = collect($requestItem->payload ?? [])
                        ->except(\App\Models\FinancialRequest::ACCOUNTING_PAYLOAD_KEYS)
                        ->filter(fn ($value) => $value !== null && $value !== '');
                @endphp
                @if($typeDetails->isNotEmpty())
                <div class="card section-card mt-3">
                    <div class="card-header"><h5 class="section-title"><i class="fa fa-list-alt ms-2 text-muted"></i> تفاصيل نوع الطلب</h5></div>
                    <div class="card-body px-4">
                        <div class="row gx-4">
                        @foreach($typeDetails as $key => $value)
                            <div class="col-md-6 detail-item">
                                <div class="detail-label">{{ \App\Models\FinancialRequest::labelForPayloadField($requestItem->type, $key) }}</div>
                                <div class="detail-value">{{ is_array($value) ? implode('، ', $value) : $value }}</div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div class="card section-card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="section-title"><i class="fa fa-history ms-2 text-muted"></i> سجل الطلب</h5>
                        <span class="badge bg-light text-dark">{{ $workflowHistory->count() }} إجراء</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="timeline">
                            @foreach($workflowHistory->sortByDesc('occurred_at') as $log)
                                <div class="timeline-item">
                                    <span class="timeline-dot"></span>
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div class="fw-bold text-dark">
                                            {{ $statuses[$log['status']] ?? $log['status'] }}
                                            @if($log['inferred'])<span class="badge bg-light text-dark me-1">حالة حالية</span>@endif
                                        </div>
                                        <div class="timeline-meta" dir="ltr">{{ $log['occurred_at']?->format('Y-m-d H:i') ?: '-' }}</div>
                                    </div>
                                    <div class="timeline-meta mt-1"><i class="fa fa-user ms-1"></i>{{ $log['performer'] ?: 'النظام' }}</div>
                                    @if($log['remarks'])<div class="mt-2 text-secondary">{{ $log['remarks'] }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card section-card mt-3">
                    <div class="card-header"><h5 class="section-title"><i class="fa fa-paperclip ms-2 text-muted"></i> المرفقات</h5></div>
                    <div class="card-body">
                        @forelse($requestItem->attachments as $attachment)
                            <a class="attachment-link mb-2" href="{{ showFile($attachment->file_path) }}" target="_blank">
                                <i class="fa fa-file mt-1"></i>
                                <span><strong>{{ $attachment->label ?: $attachment->file_name }}</strong><small class="text-muted d-block">{{ $attachment->file_name }}</small></span>
                            </a>
                        @empty
                            <div class="text-center text-muted py-3">لا توجد مرفقات لهذا الطلب.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card section-card mb-3">
                    <div class="card-header"><h5 class="section-title"><i class="fa fa-user-clock ms-2 text-muted"></i> معلومات المتابعة</h5></div>
                    <div class="card-body px-4">
                        <div class="detail-item"><div class="detail-label">رفع الطلب</div><div class="detail-value">{{ $requestItem->creator?->name ?: '-' }}<small class="text-muted d-block" dir="ltr">{{ $requestItem->created_at?->format('Y-m-d H:i') ?: '-' }}</small></div></div>
                        <div class="detail-item"><div class="detail-label">آخر تحديث</div><div class="detail-value">{{ $requestItem->updater?->name ?: '-' }}<small class="text-muted d-block" dir="ltr">{{ $requestItem->updated_at?->format('Y-m-d H:i') ?: '-' }}</small></div></div>
                        @if($requestItem->approver)
                            <div class="detail-item"><div class="detail-label">اعتماد الطلب</div><div class="detail-value">{{ $requestItem->approver->name }}</div></div>
                        @endif
                    </div>
                </div>
                @if ($actions)
                    <div class="card section-card action-card">
                        <div class="card-header"><h5 class="section-title"><i class="fa fa-tasks ms-2 text-muted"></i> الإجراء المطلوب</h5></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" action="{{ route('financial-requests.status', $requestItem) }}">
                                @csrf
                                <input type="hidden" name="priority" value="{{ $requestItem->priority }}">
                                <label class="form-label">التصعيد</label>
                                <select name="priority" class="form-select mb-2">
                                    @foreach($priorities as $key => $label)
                                        <option value="{{ $key }}" {{ $requestItem->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if(isset($actions['paid']))
                                    <label class="form-label">إثبات التحويل البنكي <span class="text-danger">*</span></label>
                                    <input type="hidden" name="attachment_labels[payment_proof]" value="إثبات التحويل البنكي">
                                    <input type="file" name="attachments[payment_proof][]" class="form-control mb-2" required multiple>
                                @endif
                                @if(isset($actions['receipt']) || isset($actions['close']))
                                    <label class="form-label">الإيصال الرسمي <span class="text-muted">(اختياري)</span></label>
                                    <input type="hidden" name="attachment_labels[official_receipt]" value="الإيصال الرسمي">
                                    <input type="file" name="attachments[official_receipt][]" class="form-control mb-2" multiple>
                                @endif
                                @if(isset($actions['close']) && $automaticJournalEntriesEnabled && ! $requestItem->finance_bond_id)
                                @php
                                    $bondType = old('bond_type', \Nami\Finance\Enums\BondTypes::Sarf->value);
                                    $isSarf = $bondType === \Nami\Finance\Enums\BondTypes::Sarf->value;
                                    $oldDebit = old('debit_account_id');
                                    $oldCredit = old('credit_account_id');
                                    // سند صرف: العميل مدين والخزينة/البنك دائن. سند قبض: العكس.
                                    $debitPreset = $oldDebit ? ['id' => $oldDebit, 'text' => \App\Models\FinancialRequest::accountLabel($oldDebit)] : ($isSarf ? $partyAccount : null);
                                    $creditPreset = $oldCredit ? ['id' => $oldCredit, 'text' => \App\Models\FinancialRequest::accountLabel($oldCredit)] : ($isSarf ? null : $partyAccount);
                                @endphp
                                <div class="alert alert-info py-2 small mb-2">
                                    سيتم إنشاء السند المحاسبي تلقائيًا عند الإقفال.
                                    <span class="d-block mt-1" id="bondHint"></span>
                                </div>
                                <label class="form-label">نوع السند عند الإقفال <span class="text-danger">*</span></label>
                                <select name="bond_type" id="bondType" class="form-select mb-2" required>
                                    @foreach($bondTypes as $key => $label)
                                        <option value="{{ $key }}" @selected($bondType === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">طريقة الدفع</label>
                                <select name="payment_type" class="form-select mb-2">
                                    @foreach($paymentTypes as $key => $label)
                                        <option value="{{ $key }}" @selected((int) old('payment_type', $defaultPaymentType) === (int) $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">الحساب المدين <span class="text-danger">*</span> <small class="text-muted" id="debitRole"></small></label>
                                <select name="debit_account_id" id="debitAccount" class="form-select js-account-select mb-2" data-placeholder="ابحث بالكود أو اسم الحساب" required>
                                    <option value=""></option>
                                    @if($debitPreset && $debitPreset['text'])<option value="{{ $debitPreset['id'] }}" selected>{{ $debitPreset['text'] }}</option>@endif
                                </select>
                                <label class="form-label">الحساب الدائن <span class="text-danger">*</span> <small class="text-muted" id="creditRole"></small></label>
                                <select name="credit_account_id" id="creditAccount" class="form-select js-account-select mb-2" data-placeholder="ابحث بالكود أو اسم الحساب" required>
                                    <option value=""></option>
                                    @if($creditPreset && $creditPreset['text'])<option value="{{ $creditPreset['id'] }}" selected>{{ $creditPreset['text'] }}</option>@endif
                                </select>
                                @if(! $partyAccount)
                                    <div class="small text-warning mb-2">لا يوجد حساب محاسبي للعميل/المستفيد في الطلب؛ اختر حسابه يدويًا.</div>
                                @endif
                                @endif
                                <label class="form-label">الملاحظات</label>
                                <textarea name="remarks" class="form-control mb-3" placeholder="إلزامية عند الرفض أو عدم الاكتمال"></textarea>
                                <div class="d-grid gap-2">
                                    @foreach($actions as $action)
                                        <button class="btn btn-{{ $action[2] }}" name="status" value="{{ $action[0] }}" type="submit">{{ $action[1] }}</button>
                                    @endforeach
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const accountUrl = @json(route('financial-requests.search-accounts'));
            const sarf = @json(\Nami\Finance\Enums\BondTypes::Sarf->value);
            const bondType = document.getElementById('bondType');
            const debit = document.getElementById('debitAccount');
            const credit = document.getElementById('creditAccount');
            const jq = window.jQuery;
            const hasSelect2 = !!(jq && jq.fn && jq.fn.select2);

            // الطرف الخزيني (خزينة/بنك) يُبحث فيه من حسابات الصناديق والبنوك فقط.
            function treasurySelect() {
                if (!bondType) return null;
                return bondType.value === sarf ? credit : debit;
            }

            function initSelect(select) {
                if (!hasSelect2) return;
                if (jq(select).data('select2')) jq(select).select2('destroy');
                jq(select).select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: jq(select).data('placeholder') || 'اختر الحساب',
                    ajax: {
                        url: accountUrl,
                        dataType: 'json',
                        delay: 250,
                        data: params => ({q: params.term || '', scope: select === treasurySelect() ? 'treasury' : ''}),
                        processResults: data => data,
                    },
                });
            }

            function refreshHints() {
                if (!bondType) return;
                const isSarf = bondType.value === sarf;
                document.getElementById('debitRole').textContent = isSarf ? '(حساب العميل / المستفيد)' : '(الخزينة / البنك)';
                document.getElementById('creditRole').textContent = isSarf ? '(الخزينة / البنك)' : '(حساب العميل / المستفيد)';
                document.getElementById('bondHint').textContent = isSarf
                    ? 'سند صرف: من حساب العميل (مدين) إلى الخزينة أو البنك (دائن).'
                    : 'سند قبض: من الخزينة أو البنك (مدين) إلى حساب العميل (دائن).';
            }

            // تبديل نوع السند يقلب طرفي القيد.
            function swapSides() {
                const d = debit.selectedOptions[0], c = credit.selectedOptions[0];
                const dVal = d && d.value ? [d.value, d.textContent] : null;
                const cVal = c && c.value ? [c.value, c.textContent] : null;
                [[debit, cVal], [credit, dVal]].forEach(([select, val]) => {
                    select.innerHTML = '<option value=""></option>';
                    if (val) select.appendChild(new Option(val[1], val[0], true, true));
                });
            }

            if (bondType) {
                bondType.addEventListener('change', () => {
                    swapSides();
                    refreshHints();
                    initSelect(debit);
                    initSelect(credit);
                });
                refreshHints();
            }

            if (hasSelect2) {
                document.querySelectorAll('.js-account-select').forEach(initSelect);
                return;
            }

            fetch(accountUrl, {headers: {'Accept': 'application/json'}})
                .then(response => response.ok ? response.json() : {results: []})
                .then(data => {
                    document.querySelectorAll('.js-account-select').forEach(select => {
                        data.results.forEach(row => {
                            if (Array.from(select.options).some(o => String(o.value) === String(row.id))) return;
                            select.appendChild(new Option(row.text, row.id));
                        });
                    });
                });
        });
    </script>
@endsection
