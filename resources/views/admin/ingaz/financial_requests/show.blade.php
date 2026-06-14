@extends('click.layout.indexs.index')

@section('content')
    @php
        $typeLabel = $requestItem->type_label;
        $statusLabel = $requestItem->status_label;
        $priorityLabel = $requestItem->priority_label;
        $contractTypeLabel = $requestItem->contract_type_label;
        $financeBondTypeLabel = $bondTypes[$requestItem->finance_bond_type] ?? null;
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
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::FINANCIAL_REQUESTS_CHANGE_STATUS, \App\PermKeys::FINANCIAL_REQUESTS_APPROVE, \App\PermKeys::FINANCIAL_REQUESTS_CONTROL]))
                    <div class="card">
                        <div class="card-header fw-bold">تحديث الحالة</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('financial-requests.status', $requestItem) }}">
                                @csrf
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select mb-2" required>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" {{ $requestItem->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">التصعيد</label>
                                <select name="priority" class="form-select mb-2">
                                    @foreach($priorities as $key => $label)
                                        <option value="{{ $key }}" {{ $requestItem->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">نوع السند عند السداد</label>
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
                                <button class="btn btn-primary w-100" type="submit">حفظ الحالة</button>
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
