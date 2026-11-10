@extends('click.layout.indexs.index')

@php
    $isEdit = isset($requestItem);
    $payload = old('payload', $isEdit ? ($requestItem->payload ?? []) : []);
    $selectedAccountId = old('payload.account_id', $payload['account_id'] ?? null);
    $selectedAccountText = \App\Models\FinancialRequest::accountLabel($selectedAccountId);
    // الطلب مدفوع دائماً، لذلك طبيعة المبلغ الافتراضية دائن.
    $debitCredit = old('payload.debit_credit', $payload['debit_credit'] ?? 'credit');
    $paymentMethod = old('payload.payment_method', $payload['payment_method'] ?? 'bank_transfer');
    $amountReadonly = $isEdit && (! AdminGuardHasPerm(\App\PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT) || ! $requestItem->canChangeAmount());
@endphp

@push('css')
    <style>
        .fr-form .section-card { border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 3px 14px rgba(31,45,61,.05); }
        .fr-form .section-card > .card-header { background: #fff; border-bottom: 1px solid #edf0f3; padding: .9rem 1.25rem; font-weight: 700; color: #25324b; }
        .fr-form .section-card > .card-header .step { display: inline-flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem; border-radius: 50%; background: #25324b; color: #fff; font-size: .8rem; margin-inline-end: .5rem; }
        .fr-form .form-label { font-weight: 600; font-size: .85rem; color: #344767; }
        .fr-form .form-text { font-size: .75rem; }
        .fr-form .nature-toggle .btn { flex: 1 1 0; min-width: 0; }
        .fr-form .summary-card { position: sticky; top: 1rem; }
        .fr-form .summary-row { display: flex; justify-content: space-between; gap: .75rem; padding: .55rem 0; border-bottom: 1px dashed #e8ebef; font-size: .85rem; }
        .fr-form .summary-row:last-child { border-bottom: 0; }
        .fr-form .summary-row .label { color: #6c757d; white-space: nowrap; }
        .fr-form .summary-row .value { font-weight: 600; text-align: end; overflow-wrap: anywhere; }
        .fr-form .summary-amount { font-size: 1.6rem; font-weight: 700; color: #198754; }
        .fr-form .linked-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 9px; padding: .75rem; }
        @media (max-width: 991.98px) { .fr-form .summary-card { position: static; } }
    </style>
@endpush

@section('content')
    <div class="container-fluid fr-form">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">{{ $isEdit ? __('financial_requests.edit') : __('financial_requests.new') }}
                @if($isEdit)<small class="text-muted fs-6" dir="ltr">{{ $requestItem->request_no }}</small>@endif
            </h3>
            <a class="btn btn-outline-secondary" href="{{ $isEdit ? route('financial-requests.show', $requestItem) : route('financial-requests.index') }}">{{ __('financial_requests.back') }}</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>{{ __('financial_requests.validation_errors') }}</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('financial-requests.update', $requestItem) : route('financial-requests.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-lg-8">
                    {{-- 1. بيانات الطلب --}}
                    <div class="card section-card mb-3">
                        <div class="card-header"><span class="step">1</span>{{ __('financial_requests.request_data') }}</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.request_type') }} <span class="text-danger">*</span></label>
                                    <select name="type" id="requestType" class="form-select" required>
                                        <option value="" disabled {{ !old('type', $requestItem->type ?? null) ? 'selected' : '' }}>{{ __('financial_requests.select_request_type') }}</option>
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('type', $requestItem->type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.branch') }} <span class="text-danger">*</span></label>
                                    <select name="branch_id" class="form-select" required>
                                        <option value="">{{ __('financial_requests.select_branch') }}</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $requestItem->branch_id ?? auth('admin')->user()?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.request_date') }}</label>
                                    <input type="date" name="request_date" class="form-control" value="{{ old('request_date', optional($requestItem->request_date ?? now())->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.amount') }} ({{ __('financial_requests.currency') }}) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" max="9999999999.99" name="amount" id="amount" class="form-control fw-bold" required value="{{ old('amount', $requestItem->amount ?? '') }}" @if($amountReadonly) readonly @endif>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.priority') }}</label>
                                    <select name="priority" class="form-select" required>
                                        @foreach ($priorities as $key => $label)
                                            <option value="{{ $key }}" {{ old('priority', $requestItem->priority ?? 'normal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="typeDetailsWrap" class="mt-3 d-none">
                                <div class="text-muted small fw-bold mb-2">{{ __('financial_requests.type_details') }}</div>
                                <div id="dynamicFields" class="row g-3"></div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">{{ __('financial_requests.description') }} <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="2" required>{{ old('description', $requestItem->description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- 2. البيانات المحاسبية --}}
                    <div class="card section-card mb-3">
                        <div class="card-header"><span class="step">2</span>{{ __('financial_requests.accounting_data') }}</div>
                        <div class="card-body">
                            <div class="alert alert-light border small py-2">{{ __('financial_requests.accounting_help') }}</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.accounting_account') }}</label>
                                    <select name="payload[account_id]" id="accountId" class="form-select" data-placeholder="{{ __('financial_requests.search_account') }}">
                                        <option value=""></option>
                                        @if($selectedAccountId)
                                            <option value="{{ $selectedAccountId }}" selected>{{ $selectedAccountText ?: __('financial_requests.selected_account') . ' #' . $selectedAccountId }}</option>
                                        @endif
                                    </select>
                                    <div class="form-text">{{ __('financial_requests.account_help') }}</div>
                                    <div id="clientAccountMissing" class="form-text text-warning d-none">{{ __('financial_requests.client_account_missing') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.accounting_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[accounting_name]" id="accountingName" class="form-control" required value="{{ old('payload.accounting_name', $payload['accounting_name'] ?? '') }}">
                                    <div class="form-text">{{ __('financial_requests.accounting_name_help') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.paid_to') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[paid_to]" id="paidTo" class="form-control" required value="{{ old('payload.paid_to', $payload['paid_to'] ?? '') }}">
                                </div>
                                <div class="w-100 m-0 d-none d-md-block"></div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">{{ __('financial_requests.amount_nature') }} <span class="text-danger">*</span></label>
                                    <div class="btn-group nature-toggle w-100" role="group">
                                        <input type="radio" class="btn-check" name="payload[debit_credit]" id="natureCredit" value="credit" {{ $debitCredit === 'credit' ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-success" for="natureCredit">{{ __('financial_requests.credit') }}</label>
                                        <input type="radio" class="btn-check" name="payload[debit_credit]" id="natureDebit" value="debit" {{ $debitCredit === 'debit' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger" for="natureDebit">{{ __('financial_requests.debit') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.payment_method') }} <span class="text-danger">*</span></label>
                                    <select name="payload[payment_method]" id="paymentMethod" class="form-select" required>
                                        <option value="bank_transfer" {{ $paymentMethod === 'bank_transfer' ? 'selected' : '' }}>{{ __('financial_requests.bank_transfer') }}</option>
                                        <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>{{ __('financial_requests.cash') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div id="bankTransferCard" class="mt-3">
                                <div class="text-muted small fw-bold mb-2"><i class="fa fa-university me-1"></i> {{ __('financial_requests.bank_details') }} <span class="fw-normal">({{ __('financial_requests.optional') }})</span></div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('financial_requests.account_holder') }}</label>
                                        <input type="text" name="payload[account_holder_name]" class="form-control" value="{{ old('payload.account_holder_name', $payload['account_holder_name'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('financial_requests.bank_name') }}</label>
                                        <input type="text" name="payload[bank_name]" class="form-control" value="{{ old('payload.bank_name', $payload['bank_name'] ?? '') }}">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('financial_requests.account_number') }}</label>
                                        <input type="text" name="payload[account_number]" class="form-control" dir="ltr" value="{{ old('payload.account_number', $payload['account_number'] ?? '') }}">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">{{ __('financial_requests.iban') }}</label>
                                        <input type="text" name="payload[iban]" class="form-control text-uppercase" maxlength="34" dir="ltr" value="{{ old('payload.iban', $payload['iban'] ?? '') }}" placeholder="SA00 0000 0000 0000 0000 0000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. المرفقات --}}
                    <div class="card section-card mb-3">
                        <div class="card-header"><span class="step">3</span>{{ __('financial_requests.attachments') }}</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.main_attachment') }}</label>
                                    <input type="hidden" name="attachment_labels[main]" value="{{ __('financial_requests.main_attachment') }}">
                                    <input type="text" name="attachment_descriptions[main]" class="form-control mb-2" placeholder="{{ __('financial_requests.attachment_description') }}">
                                    <input type="file" name="attachments[main][]" class="form-control" multiple>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.other_files') }}</label>
                                    <input type="hidden" name="attachment_labels[other]" value="{{ __('financial_requests.other_files') }}">
                                    <input type="text" name="attachment_descriptions[other]" class="form-control mb-2" placeholder="{{ __('financial_requests.other_files_description') }}">
                                    <input type="file" name="attachments[other][]" class="form-control" multiple>
                                </div>
                            </div>
                            @if($isEdit && $requestItem->attachments->isNotEmpty())
                                <div class="mt-3">
                                    @foreach($requestItem->attachments as $attachment)
                                        <a class="badge bg-light text-dark border me-1 text-decoration-none" href="{{ showFile($attachment->file_path) }}" target="_blank">
                                            <i class="fa fa-file"></i> {{ $attachment->file_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ملخص الطلب للمراجعة قبل الإرسال --}}
                <div class="col-lg-4">
                    {{-- ربط العميل والعقد: في العمود الجانبي فوق الملخص --}}
                    <div class="card section-card mb-3">
                        <div class="card-header"><i class="fa fa-user me-1"></i> {{ __('financial_requests.linking') }}</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">{{ __('financial_requests.client') }}</label>
                                    <div class="input-group mb-2">
                                        <input type="text" id="clientSearch" class="form-control" placeholder="{{ __('financial_requests.search_client') }}">
                                        <button class="btn btn-outline-secondary" type="button" id="searchClientBtn"><i class="fa fa-search"></i> {{ __('financial_requests.search') }}</button>
                                    </div>
                                    <div id="clientResults" class="list-group small mb-2"></div>
                                    <div class="linked-box">
                                        <input type="hidden" name="client_id" id="clientId" value="{{ old('client_id', $requestItem->client_id ?? '') }}">
                                        <input type="text" name="client_name" id="clientName" class="form-control form-control-sm mb-2" placeholder="{{ __('financial_requests.client_name') }}" value="{{ old('client_name', $requestItem->client_name ?? '') }}">
                                        <input type="text" name="client_identity" id="clientIdentity" class="form-control form-control-sm" placeholder="{{ __('financial_requests.identity') }}" value="{{ old('client_identity', $requestItem->client_identity ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('financial_requests.worker') }}</label>
                                    <div class="input-group mb-2">
                                        <input type="text" id="maidSearch" class="form-control" placeholder="{{ __('financial_requests.search_worker') }}">
                                        <button class="btn btn-outline-secondary" type="button" id="searchMaidBtn"><i class="fa fa-search"></i> {{ __('financial_requests.search') }}</button>
                                    </div>
                                    <div id="maidResults" class="list-group small mb-2"></div>
                                    <div class="linked-box">
                                        <input type="hidden" name="maid_id" id="maidId" value="{{ old('maid_id', $requestItem->maid_id ?? '') }}">
                                        <input type="text" name="maid_name" id="maidName" class="form-control form-control-sm mb-2" placeholder="{{ __('financial_requests.worker_name') }}" value="{{ old('maid_name', $requestItem->maid_name ?? '') }}">
                                        <input type="text" name="maid_passport" id="maidPassport" class="form-control form-control-sm" placeholder="{{ __('financial_requests.passport') }}" value="{{ old('maid_passport', $requestItem->maid_passport ?? '') }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('financial_requests.contract') }}</label>
                                    <div class="input-group mb-2">
                                        <select name="contract_type" id="contractType" class="form-select" style="max-width: 110px">
                                            <option value="">{{ __('financial_requests.automatic') }}</option>
                                            @foreach ($contractTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('contract_type', $requestItem->contract_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="contract_number" id="contractNumber" class="form-control" value="{{ old('contract_number', $requestItem->contract_number ?? '') }}" placeholder="{{ __('financial_requests.contract_number') }}">
                                        <button class="btn btn-outline-primary" type="button" id="fetchContract"><i class="fa fa-download"></i> {{ __('financial_requests.fetch') }}</button>
                                    </div>
                                    <input type="hidden" name="contract_id" id="contractId" value="{{ old('contract_id', $requestItem->contract_id ?? '') }}">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="number" step="0.01" name="contract_cost" id="contractCost" class="form-control form-control-sm" placeholder="{{ __('financial_requests.contract_cost') }}" value="{{ old('contract_cost', $requestItem->contract_cost ?? '') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="number" step="0.01" name="contract_salary" id="contractSalary" class="form-control form-control-sm" placeholder="{{ __('financial_requests.salary') }}" value="{{ old('contract_salary', $requestItem->contract_salary ?? '') }}">
                                        </div>
                                    </div>
                                    <a class="btn btn-sm btn-link px-0 mt-2" href="https://musaned.com.sa" target="_blank" rel="noopener">
                                        <i class="fa fa-external-link-alt"></i> {{ __('financial_requests.open_musaned') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card section-card summary-card">
                        <div class="card-header"><i class="fa fa-clipboard-check me-1"></i> {{ __('financial_requests.summary') }}</div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="summary-amount"><span id="sumAmount">0.00</span> <small class="fs-6">{{ __('financial_requests.currency') }}</small></div>
                                <span id="sumNature" class="badge"></span>
                            </div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.request_type') }}</span><span class="value" id="sumType"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.paid_to') }}</span><span class="value" id="sumPaidTo"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.accounting_name') }}</span><span class="value" id="sumAccountingName"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.accounting_account') }}</span><span class="value" id="sumAccount" dir="auto"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.payment_method') }}</span><span class="value" id="sumPayment"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.client') }}</span><span class="value" id="sumClient"></span></div>
                            <div class="summary-row"><span class="label">{{ __('financial_requests.contract') }}</span><span class="value" id="sumContract"></span></div>

                            <button class="btn btn-primary w-100 mt-3" type="submit">
                                <i class="fa fa-paper-plane"></i> {{ $isEdit ? __('financial_requests.update_resubmit') : __('financial_requests.submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeFields = @json($typeFields);
            const payload = @json($payload);
            const fieldFallbackLabels = @json(__('financial_requests.fields'));
            const t = {
                none: @json(__('financial_requests.summary_none')),
                debit: @json(__('financial_requests.debit')),
                credit: @json(__('financial_requests.credit')),
                noClient: @json(__('financial_requests.no_client_results')),
                noWorker: @json(__('financial_requests.no_worker_results')),
                contractNotFound: @json(__('financial_requests.contract_not_found')),
                searchAccount: @json(__('financial_requests.search_account')),
            };
            const $ = id => document.getElementById(id);

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
            }

            // ---------- request-type fields ----------
            function renderDynamicFields() {
                const wrapper = $('dynamicFields');
                const fields = typeFields[$('requestType').value] || {};
                wrapper.innerHTML = '';
                // الحقول النصية الطويلة (سبب الاسترداد، موضوع الطلب) بعرض كامل وتأتي بعد الحقول القصيرة.
                const longFields = ['refund_reason', 'subject'];
                const ordered = Object.entries(fields).sort(([a], [b]) => longFields.includes(a) - longFields.includes(b));
                ordered.forEach(([key, label]) => {
                    const isLong = longFields.includes(key);
                    const col = document.createElement('div');
                    col.className = isLong ? 'col-12' : 'col-md-6';
                    const fieldLabel = document.createElement('label');
                    fieldLabel.className = 'form-label';
                    fieldLabel.textContent = label || fieldFallbackLabels[key] || key.replaceAll('_', ' ');
                    const field = document.createElement(isLong ? 'textarea' : 'input');
                    if (isLong) field.rows = 2; else field.type = 'text';
                    field.className = 'form-control';
                    field.name = `payload[${key}]`;
                    field.value = payload[key] || '';
                    col.append(fieldLabel, field);
                    wrapper.appendChild(col);
                });
                $('typeDetailsWrap').classList.toggle('d-none', Object.keys(fields).length === 0);
            }

            // ---------- auto accounting name ----------
            // Auto-fill stops as soon as the user types the value themselves.
            const accountingName = $('accountingName');
            const paidTo = $('paidTo');
            let accountingNameAuto = accountingName.value.trim() === '';
            let paidToAuto = paidTo.value.trim() === '';
            accountingName.addEventListener('input', () => accountingNameAuto = accountingName.value.trim() === '');
            paidTo.addEventListener('input', () => paidToAuto = paidTo.value.trim() === '');

            function suggestAccountingName(name) {
                if (name && accountingNameAuto) accountingName.value = name;
            }
            function suggestPaidTo(name) {
                if (name && paidToAuto) paidTo.value = name;
            }

            // ---------- accounting account ----------
            const accountSelect = $('accountId');
            const jq = window.jQuery;
            const hasSelect2 = !!(jq && jq.fn && jq.fn.select2);

            function accountNameFromText(text) {
                const parts = String(text || '').split(' - ');
                return parts.length > 1 ? parts.slice(1).join(' - ') : text;
            }

            function setAccount(id, text) {
                if (!id) return;
                let option = Array.from(accountSelect.options).find(o => String(o.value) === String(id));
                if (!option) {
                    option = new Option(text, id, true, true);
                    accountSelect.appendChild(option);
                }
                option.selected = true;
                if (hasSelect2) jq(accountSelect).trigger('change'); else accountSelect.dispatchEvent(new Event('change'));
            }

            if (hasSelect2) {
                jq(accountSelect).select2({
                    width: '100%',
                    allowClear: true,
                    dir: document.documentElement.dir || 'rtl',
                    placeholder: t.searchAccount,
                    ajax: {
                        url: @json(route('financial-requests.search-accounts')),
                        dataType: 'json',
                        delay: 250,
                        data: params => ({q: params.term || ''}),
                        processResults: data => data,
                    },
                });
                jq(accountSelect).on('select2:select', e => suggestAccountingName(accountNameFromText(e.params.data.text)));
                jq(accountSelect).on('change', updateSummary);
            } else {
                // No select2: load the first batch of accounts into the plain select.
                fetch(@json(route('financial-requests.search-accounts')), {headers: {'Accept': 'application/json'}})
                    .then(r => r.ok ? r.json() : {results: []})
                    .then(data => (data.results || []).forEach(row => {
                        if (!Array.from(accountSelect.options).some(o => String(o.value) === String(row.id))) {
                            accountSelect.appendChild(new Option(row.text, row.id));
                        }
                    }));
                accountSelect.addEventListener('change', () => {
                    const option = accountSelect.selectedOptions[0];
                    if (option && option.value) suggestAccountingName(accountNameFromText(option.textContent));
                    updateSummary();
                });
            }

            function applyClientAccount(row) {
                $('clientAccountMissing').classList.add('d-none');
                if (row.account_id) {
                    setAccount(row.account_id, row.account_text);
                    suggestAccountingName(row.account_name || row.name);
                } else if (row.id) {
                    $('clientAccountMissing').classList.remove('d-none');
                    suggestAccountingName(row.name);
                }
                suggestPaidTo(row.name);
            }

            // ---------- client / worker search ----------
            async function searchRows(url, term) {
                if (!term || term.length < 2) return [];
                const response = await fetch(url + '?q=' + encodeURIComponent(term), {headers: {'Accept': 'application/json'}});
                return response.ok ? response.json() : [];
            }

            function renderResults(containerId, rows, emptyText, onSelect) {
                const container = $(containerId);
                container.innerHTML = '';
                if (!rows.length) {
                    container.innerHTML = `<div class="text-muted border rounded p-2">${escapeHtml(emptyText)}</div>`;
                    return;
                }
                rows.forEach(row => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.innerHTML = `<strong>${escapeHtml(row.name || '-')}</strong><br><span class="text-muted">${escapeHtml(row.text || '')}</span>`
                        + (row.account_text ? `<br><small class="text-success" dir="auto"><i class="fa fa-book"></i> ${escapeHtml(row.account_text)}</small>` : '');
                    button.addEventListener('click', () => { onSelect(row); container.innerHTML = ''; });
                    container.appendChild(button);
                });
            }

            function selectClient(row) {
                $('clientId').value = row.id || '';
                $('clientName').value = row.name || '';
                $('clientIdentity').value = row.identity || '';
                $('clientSearch').value = '';
                applyClientAccount(row);
                updateSummary();
            }

            function selectMaid(row) {
                $('maidId').value = row.id || '';
                $('maidName').value = row.name || '';
                $('maidPassport').value = row.passport || '';
                $('maidSearch').value = '';
                $('contractSalary').value = row.salary || $('contractSalary').value;
            }

            async function searchClients() {
                renderResults('clientResults', await searchRows(@json(route('financial-requests.search-clients')), $('clientSearch').value), t.noClient, selectClient);
            }
            async function searchMaids() {
                renderResults('maidResults', await searchRows(@json(route('financial-requests.search-maids')), $('maidSearch').value), t.noWorker, selectMaid);
            }

            $('searchClientBtn').addEventListener('click', searchClients);
            $('searchMaidBtn').addEventListener('click', searchMaids);
            $('clientSearch').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchClients(); } });
            $('maidSearch').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchMaids(); } });

            $('fetchContract').addEventListener('click', async () => {
                const params = new URLSearchParams({contract_number: $('contractNumber').value, contract_type: $('contractType').value});
                const response = await fetch(@json(route('financial-requests.contract-details')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
                if (!response.ok) { alert(t.contractNotFound); return; }
                const data = await response.json();
                $('contractId').value = data.contract_id || '';
                $('contractType').value = data.contract_type || '';
                $('contractNumber').value = data.contract_number || $('contractNumber').value;
                $('contractCost').value = data.contract_cost || '';
                $('contractSalary').value = data.contract_salary || '';
                selectClient({id: data.client_id, name: data.client_name, identity: data.client_identity, account_id: data.client_account_id, account_name: data.client_account_name, account_text: data.client_account_text});
                selectMaid({id: data.maid_id, name: data.maid_name, passport: data.maid_passport, salary: data.contract_salary});
                updateSummary();
            });

            // ---------- payment method ----------
            function toggleBankDetails() {
                $('bankTransferCard').classList.toggle('d-none', $('paymentMethod').value !== 'bank_transfer');
            }

            // ---------- summary ----------
            function selectedText(select) {
                const option = select.selectedOptions[0];
                return option && option.value ? option.textContent.trim() : '';
            }

            function updateSummary() {
                const amount = parseFloat($('amount').value || 0);
                $('sumAmount').textContent = amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const nature = document.querySelector('input[name="payload[debit_credit]"]:checked')?.value;
                const natureBadge = $('sumNature');
                natureBadge.textContent = nature === 'debit' ? t.debit : (nature === 'credit' ? t.credit : '');
                natureBadge.className = 'badge ' + (nature === 'debit' ? 'bg-danger' : 'bg-success');
                $('sumType').textContent = selectedText($('requestType')) || t.none;
                $('sumPaidTo').textContent = paidTo.value.trim() || t.none;
                $('sumAccountingName').textContent = accountingName.value.trim() || t.none;
                $('sumAccount').textContent = selectedText(accountSelect) || t.none;
                $('sumPayment').textContent = selectedText($('paymentMethod'));
                $('sumClient').textContent = [$('clientName').value, $('clientIdentity').value].filter(Boolean).join(' - ') || t.none;
                $('sumContract').textContent = [selectedText($('contractType')), $('contractNumber').value].filter(Boolean).join(' #') || t.none;
            }

            $('requestType').addEventListener('change', () => { renderDynamicFields(); updateSummary(); });
            $('paymentMethod').addEventListener('change', () => { toggleBankDetails(); updateSummary(); });
            document.querySelector('.fr-form form').addEventListener('input', updateSummary);
            document.querySelector('.fr-form form').addEventListener('change', updateSummary);

            renderDynamicFields();
            toggleBankDetails();
            updateSummary();
        });
    </script>
@endpush
