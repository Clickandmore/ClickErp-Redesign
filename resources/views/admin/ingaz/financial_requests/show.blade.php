@extends('click.layout.indexs.index')

@section('content')
    @php
        $typeLabel = $requestItem->type_label;
        $statusLabel = $requestItem->status_label;
        $priorityLabel = $requestItem->priority_label;
        $contractTypeLabel = $requestItem->contract_type_label;
        $financeBondTypeLabel = $bondTypes[$requestItem->finance_bond_type] ?? null;
        $isCreator = (int) $requestItem->created_by_id === (int) auth('admin')->id();
        $isAuditor = @AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]);
        $actions = [];
        if ($isAuditor && $requestItem->status === \App\Models\FinancialRequest::STATUS_FOR_APPROVAL) {
            $actions = ['approve' => [\App\Models\FinancialRequest::STATUS_APPROVED, 'اعتماد', 'success'], 'reject' => [\App\Models\FinancialRequest::STATUS_PREPARING, 'غير معتمد', 'danger']];
        } elseif ($isCreator && in_array($requestItem->status, [\App\Models\FinancialRequest::STATUS_PREPARING, \App\Models\FinancialRequest::STATUS_REJECTED], true)) {
            $actions = ['resubmit' => [\App\Models\FinancialRequest::STATUS_FOR_APPROVAL, 'إعادة الإرسال للاعتماد', 'primary']];
        } elseif ($isCreator && $requestItem->status === \App\Models\FinancialRequest::STATUS_APPROVED) {
            $actions = ['paid' => [\App\Models\FinancialRequest::STATUS_PAID, 'تم السداد', 'primary']];
        } elseif ($isAuditor && $requestItem->status === \App\Models\FinancialRequest::STATUS_PAID) {
            $actions = ['verify' => [\App\Models\FinancialRequest::STATUS_REQUEST_CLOSING, 'طلب الإقفال', 'warning']];
        } elseif ($isCreator && $requestItem->status === \App\Models\FinancialRequest::STATUS_REQUEST_CLOSING) {
            $actions = ['receipt' => [\App\Models\FinancialRequest::STATUS_RECEIPT_ATTACHED, 'تم إرفاق الإيصال', 'info']];
        } elseif ($isAuditor && $requestItem->status === \App\Models\FinancialRequest::STATUS_RECEIPT_ATTACHED) {
            $actions = ['close' => [\App\Models\FinancialRequest::STATUS_CLOSED, 'مكتمل وإقفال', 'success'], 'incomplete' => [\App\Models\FinancialRequest::STATUS_REQUEST_CLOSING, 'غير مكتمل', 'danger']];
        }
    @endphp

    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="mb-1">طلب مالي {{ $requestItem->request_no }}</h3>
                <div class="text-muted">{{ $typeLabel }}</div>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('financial-requests.index') }}">رجوع</a>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">الحالة</dt><dd class="col-sm-9">{{ $statusLabel }}</dd>
                            <dt class="col-sm-3">المدفوع له</dt><dd class="col-sm-9">{{ data_get($requestItem->payload, 'paid_to', '-') }}</dd>
                            <dt class="col-sm-3">الفرع</dt><dd class="col-sm-9">{{ $requestItem->branch_id ?: '-' }}</dd>
                            <dt class="col-sm-3">التصعيد</dt><dd class="col-sm-9">{{ $priorityLabel }}</dd>
                            <dt class="col-sm-3">المبلغ</dt><dd class="col-sm-9">{{ $requestItem->amount ? number_format((float) $requestItem->amount, 2) : '-' }}</dd>
                            <dt class="col-sm-3">العميل</dt><dd class="col-sm-9">{{ $requestItem->client_name ?: '-' }} / {{ $requestItem->client_identity ?: '-' }}</dd>
                            <dt class="col-sm-3">العاملة</dt><dd class="col-sm-9">{{ $requestItem->maid_name ?: '-' }} / {{ $requestItem->maid_passport ?: '-' }}</dd>
                            <dt class="col-sm-3">العقد</dt><dd class="col-sm-9">{{ $requestItem->contract_number ?: '-' }} - {{ $contractTypeLabel }}</dd>
                            <dt class="col-sm-3">الوصف</dt><dd class="col-sm-9">{{ $requestItem->description ?: '-' }}</dd>
                            <dt class="col-sm-3">السند المالي</dt>
                            <dd class="col-sm-9">
                                @if($requestItem->financeBond)
                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $requestItem->financeBond->id, 'type' => $requestItem->finance_bond_type]) }}" target="_blank">#{{ $requestItem->financeBond->id }}</a>
                                    @if($financeBondTypeLabel)
                                        <span class="text-muted">- {{ $financeBondTypeLabel }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header fw-bold">الحقول الإضافية</div>
                    <div class="card-body">
                        @forelse(($requestItem->payload ?? []) as $key => $value)
                            <div class="mb-2"><strong>{{ \App\Models\FinancialRequest::labelForPayloadField($requestItem->type, $key) }}:</strong> {{ $value }}</div>
                        @empty
                            <div class="text-muted">لا توجد حقول إضافية.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header fw-bold">سجل الحالات</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>الحالة</th><th>بواسطة</th><th>التاريخ</th><th>الوقت</th><th>الملاحظات</th></tr></thead>
                            <tbody>
                            @forelse($requestItem->statusLogs as $log)
                                <tr><td>{{ $statuses[$log->to_status] ?? $log->to_status }}</td><td>{{ $log->performer?->name ?: '-' }}</td><td>{{ $log->created_at->format('Y-m-d') }}</td><td>{{ $log->created_at->format('H:i') }}</td><td>{{ $log->remarks ?: '-' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">لا يوجد سجل سابق.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header fw-bold">المرفقات</div>
                    <div class="card-body">
                        @forelse($requestItem->attachments as $attachment)
                            <a class="btn btn-sm btn-outline-dark mb-2" href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">
                                <i class="fa fa-paperclip"></i> {{ $attachment->label ?: $attachment->file_name }}
                            </a>
                        @empty
                            <div class="text-muted">لا توجد مرفقات.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @if ($actions)
                    <div class="card">
                        <div class="card-header fw-bold">تحديث الحالة</div>
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
                                @if(isset($actions['receipt']))
                                    <label class="form-label">الإيصال الرسمي <span class="text-danger">*</span></label>
                                    <input type="hidden" name="attachment_labels[official_receipt]" value="الإيصال الرسمي">
                                    <input type="file" name="attachments[official_receipt][]" class="form-control mb-2" required multiple>
                                @endif
                                @if(isset($actions['close']))
                                <label class="form-label">نوع السند عند الإقفال</label>
                                <select name="bond_type" class="form-select mb-2">
                                    <option value="">بدون</option>
                                    @foreach($bondTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">طريقة الدفع</label>
                                <select name="payment_type" class="form-select mb-2">
                                    @foreach($paymentTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">الحساب المدين</label>
                                <select name="debit_account_id" class="form-select js-account-select mb-2" data-placeholder="ابحث بالكود أو اسم الحساب">
                                    <option value=""></option>
                                </select>
                                <label class="form-label">الحساب الدائن</label>
                                <select name="credit_account_id" class="form-select js-account-select mb-2" data-placeholder="ابحث بالكود أو اسم الحساب">
                                    <option value=""></option>
                                </select>
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
            const accountUrl = '{{ route('financial-requests.search-accounts') }}';
            const selects = document.querySelectorAll('.js-account-select');

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery('.js-account-select').select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: function () {
                        return window.jQuery(this).data('placeholder') || 'اختر الحساب';
                    },
                    ajax: {
                        url: accountUrl,
                        dataType: 'json',
                        delay: 250,
                        data: params => ({q: params.term || ''}),
                        processResults: data => data,
                    },
                });
                return;
            }

            fetch(accountUrl, {headers: {'Accept': 'application/json'}})
                .then(response => response.ok ? response.json() : {results: []})
                .then(data => {
                    selects.forEach(select => {
                        data.results.forEach(row => {
                            const option = document.createElement('option');
                            option.value = row.id;
                            option.textContent = row.text;
                            select.appendChild(option);
                        });
                    });
                });
        });
    </script>
@endsection
