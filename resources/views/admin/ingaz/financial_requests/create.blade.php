@extends('click.layout.indexs.index')

@php
    $isEdit = isset($requestItem);
    $payload = old('payload', $isEdit ? ($requestItem->payload ?? []) : []);

@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">{{ $isEdit ? __('financial_requests.edit') : __('financial_requests.new') }}</h3>
            <a class="btn btn-outline-secondary" href="{{ route('financial-requests.index') }}">{{ __('financial_requests.back') }}</a>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('financial-requests.update', $requestItem) : route('financial-requests.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header fw-bold"><i class="fa fa-file-invoice me-1"></i> {{ __('financial_requests.basic_data') }}</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.paid_to') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[paid_to]" class="form-control" required value="{{ old('payload.paid_to', $payload['paid_to'] ?? '') }}">
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
                                    <label class="form-label">{{ __('financial_requests.accounting_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[accounting_name]" class="form-control" required value="{{ old('payload.accounting_name', $payload['accounting_name'] ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.request_type') }}</label>
                                    <select name="type" id="requestType" class="form-select" required>
                                        <option value="" disabled {{ !old('type', $requestItem->type ?? null) ? 'selected' : '' }}>{{ __('financial_requests.select_request_type') }}</option>
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('type', $requestItem->type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.priority') }}</label>
                                    <select name="priority" class="form-select" required>
                                        @foreach ($priorities as $key => $label)
                                            <option value="{{ $key }}" {{ old('priority', $requestItem->priority ?? 'normal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.request_date') }}</label>
                                    <input type="date" name="request_date" class="form-control" value="{{ old('request_date', optional($requestItem->request_date ?? now())->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.amount') }}</label>
                                    <input type="number" step="0.01" min="0.01" max="9999999999.99" name="amount" class="form-control" required value="{{ old('amount', $requestItem->amount ?? '') }}" @if(isset($requestItem) && (!AdminGuardHasPerm(\App\PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT) || !$requestItem->canChangeAmount())) readonly @endif>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.debit_credit') }} <span class="text-danger">*</span></label>
                                    <select name="payload[debit_credit]" class="form-select" required>
                                        <option value="debit" {{ old('payload.debit_credit', $payload['debit_credit'] ?? 'debit') === 'debit' ? 'selected' : '' }}>{{ __('financial_requests.debit') }}</option>
                                        <option value="credit" {{ old('payload.debit_credit', $payload['debit_credit'] ?? '') === 'credit' ? 'selected' : '' }}>{{ __('financial_requests.credit') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('financial_requests.payment_method') }} <span class="text-danger">*</span></label>
                                    <select name="payload[payment_method]" id="paymentMethod" class="form-select" required>
                                        <option value="bank_transfer" {{ old('payload.payment_method', $payload['payment_method'] ?? 'bank_transfer') === 'bank_transfer' ? 'selected' : '' }}>{{ __('financial_requests.bank_transfer') }}</option>
                                        <option value="cash" {{ old('payload.payment_method', $payload['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>{{ __('financial_requests.cash') }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('financial_requests.description') }}</label>
                                    <textarea name="description" class="form-control" required>{{ old('description', $requestItem->description ?? '') }}</textarea>
                                </div>
                            </div>
                            <hr>
                            <div id="dynamicFields" class="row g-3"></div>
                        </div>
                    </div>

                    <div class="card mb-3" id="bankTransferCard">
                        <div class="card-header fw-bold"><i class="fa fa-university me-1"></i> {{ __('financial_requests.bank_details') }} <small class="text-muted fw-normal">({{ __('financial_requests.optional') }})</small></div>
                        <div class="card-body">
                            <div class="alert alert-light border small">{{ __('financial_requests.bank_help') }}</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.accounting_account') }}</label>
                                    <select name="payload[account_id]" id="accountId" class="form-select js-create-account-select" data-placeholder="{{ __('financial_requests.search_account') }}">
                                        <option value=""></option>
                                        @if(old('payload.account_id', $payload['account_id'] ?? null))
                                            <option value="{{ old('payload.account_id', $payload['account_id'] ?? '') }}" selected>{{ __('financial_requests.selected_account') }} #{{ old('payload.account_id', $payload['account_id'] ?? '') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.bank_name') }}</label>
                                    <input type="text" name="payload[bank_name]" class="form-control" value="{{ old('payload.bank_name', $payload['bank_name'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.account_holder') }}</label>
                                    <input type="text" name="payload[account_holder_name]" class="form-control" value="{{ old('payload.account_holder_name', $payload['account_holder_name'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('financial_requests.account_number') }}</label>
                                    <input type="text" name="payload[account_number]" class="form-control" value="{{ old('payload.account_number', $payload['account_number'] ?? '') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('financial_requests.iban') }}</label>
                                    <input type="text" name="payload[iban]" class="form-control text-uppercase" maxlength="34" dir="ltr" value="{{ old('payload.iban', $payload['iban'] ?? '') }}" placeholder="SA00 0000 0000 0000 0000 0000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header fw-bold"><i class="fa fa-paperclip me-1"></i> {{ __('financial_requests.attachments') }}</div>
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
                                        <span class="badge bg-light text-dark border me-1">{{ $attachment->file_name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header fw-bold"><i class="fa fa-link me-1"></i> {{ __('financial_requests.linking') }}</div>
                        <div class="card-body">
                            <label class="form-label">{{ __('financial_requests.client') }}</label>
                            <div class="input-group mb-2">
                                <input type="text" id="clientSearch" class="form-control" placeholder="{{ __('financial_requests.search_client') }}" value="{{ old('client_name', $requestItem->client_name ?? '') }}">
                                <button class="btn btn-outline-secondary" type="button" id="searchClientBtn">{{ __('financial_requests.search') }}</button>
                            </div>
                            <div id="clientResults" class="list-group small mb-2"></div>
                            <input type="hidden" name="client_id" id="clientId" value="{{ old('client_id', $requestItem->client_id ?? '') }}">
                            <input type="text" name="client_name" id="clientName" class="form-control mb-2" placeholder="{{ __('financial_requests.client_name') }}" value="{{ old('client_name', $requestItem->client_name ?? '') }}">
                            <input type="text" name="client_identity" id="clientIdentity" class="form-control mb-3" placeholder="{{ __('financial_requests.identity') }}" value="{{ old('client_identity', $requestItem->client_identity ?? '') }}">

                            <label class="form-label">{{ __('financial_requests.worker') }}</label>
                            <div class="input-group mb-2">
                                <input type="text" id="maidSearch" class="form-control" placeholder="{{ __('financial_requests.search_worker') }}" value="{{ old('maid_name', $requestItem->maid_name ?? '') }}">
                                <button class="btn btn-outline-secondary" type="button" id="searchMaidBtn">{{ __('financial_requests.search') }}</button>
                            </div>
                            <div id="maidResults" class="list-group small mb-2"></div>
                            <input type="hidden" name="maid_id" id="maidId" value="{{ old('maid_id', $requestItem->maid_id ?? '') }}">
                            <input type="text" name="maid_name" id="maidName" class="form-control mb-2" placeholder="{{ __('financial_requests.worker_name') }}" value="{{ old('maid_name', $requestItem->maid_name ?? '') }}">
                            <input type="text" name="maid_passport" id="maidPassport" class="form-control mb-3" placeholder="{{ __('financial_requests.passport') }}" value="{{ old('maid_passport', $requestItem->maid_passport ?? '') }}">

                            <label class="form-label">{{ __('financial_requests.contract') }}</label>
                            <div class="input-group mb-2">
                                <select name="contract_type" id="contractType" class="form-select" style="max-width: 140px">
                                    <option value="">{{ __('financial_requests.automatic') }}</option>
                                    @foreach ($contractTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('contract_type', $requestItem->contract_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="contract_number" id="contractNumber" class="form-control" value="{{ old('contract_number', $requestItem->contract_number ?? '') }}" placeholder="{{ __('financial_requests.contract_number') }}">
                                <button class="btn btn-outline-primary" type="button" id="fetchContract">{{ __('financial_requests.fetch') }}</button>
                            </div>
                            <input type="hidden" name="contract_id" id="contractId" value="{{ old('contract_id', $requestItem->contract_id ?? '') }}">
                            <input type="number" step="0.01" name="contract_cost" id="contractCost" class="form-control mb-2" placeholder="{{ __('financial_requests.contract_cost') }}" value="{{ old('contract_cost', $requestItem->contract_cost ?? '') }}">
                            <input type="number" step="0.01" name="contract_salary" id="contractSalary" class="form-control" placeholder="{{ __('financial_requests.salary') }}" value="{{ old('contract_salary', $requestItem->contract_salary ?? '') }}">

                            <div class="d-grid gap-2 mt-3">
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#musanedModal">
                                    <i class="fa fa-search"></i> {{ __('financial_requests.fetch_musaned') }}
                                </button>
                                <a class="btn btn-success" href="https://musaned.com.sa" target="_blank" rel="noopener">
                                    <i class="fa fa-external-link-alt"></i> {{ __('financial_requests.open_musaned') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">{{ $isEdit ? __('financial_requests.update_resubmit') : __('financial_requests.submit') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="musanedModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('financial_requests.musaned_search') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="{{ __('financial_requests.musaned_search_placeholder') }}">
                        <a class="btn btn-success" href="https://musaned.com.sa" target="_blank" rel="noopener">{{ __('financial_requests.open_musaned') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const typeFields = @json($typeFields);
        const payload = @json($payload);
        const fieldFallbackLabels = @json(__('financial_requests.fields'));

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function renderDynamicFields() {
            const type = document.getElementById('requestType').value;
            const wrapper = document.getElementById('dynamicFields');
            wrapper.innerHTML = '';
            Object.entries(typeFields[type] || {}).forEach(([key, label]) => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                const fieldLabel = document.createElement('label');
                fieldLabel.className = 'form-label';
                fieldLabel.textContent = label || fieldFallbackLabels[key] || key.replaceAll('_', ' ');

                const field = document.createElement('input');
                field.type = 'text';
                field.className = 'form-control';
                field.name = `payload[${key}]`;
                field.value = payload[key] || '';

                col.appendChild(fieldLabel);
                col.appendChild(field);
                wrapper.appendChild(col);
            });
        }

        async function searchRows(url, term) {
            if (!term || term.length < 2) return [];
            const response = await fetch(url + '?q=' + encodeURIComponent(term), {headers: {'Accept': 'application/json'}});
            if (!response.ok) return [];

            return response.json();
        }

        function renderResults(containerId, rows, emptyText, onSelect) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            if (!rows.length) {
                container.innerHTML = `<div class="text-muted border rounded p-2">${emptyText}</div>`;
                return;
            }

            rows.forEach(row => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = `<strong>${escapeHtml(row.name || '-')}</strong><br><span class="text-muted">${escapeHtml(row.text || '')}</span>`;
                button.addEventListener('click', () => {
                    onSelect(row);
                    container.innerHTML = '';
                });
                container.appendChild(button);
            });
        }

        function selectClient(row) {
            clientId.value = row.id || '';
            clientName.value = row.name || '';
            clientIdentity.value = row.identity || '';
            clientSearch.value = row.text || row.name || '';
        }

        function selectMaid(row) {
            maidId.value = row.id || '';
            maidName.value = row.name || '';
            maidPassport.value = row.passport || '';
            maidSearch.value = row.text || row.name || '';
            contractSalary.value = row.salary || contractSalary.value;
        }

        let clients = [];
        let maids = [];
        document.getElementById('requestType').addEventListener('change', renderDynamicFields);
        renderDynamicFields();

        function toggleBankDetails() {
            const isBankTransfer = document.getElementById('paymentMethod').value === 'bank_transfer';
            document.getElementById('bankTransferCard').classList.toggle('d-none', !isBankTransfer);
        }

        document.getElementById('paymentMethod').addEventListener('change', toggleBankDetails);
        toggleBankDetails();

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery('.js-create-account-select').select2({
                width: '100%',
                allowClear: true,
                placeholder: @json(__('financial_requests.search_account')),
                ajax: {
                    url: '{{ route('financial-requests.search-accounts') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({q: params.term || ''}),
                    processResults: data => data,
                },
            });
        }

        async function searchClients() {
            clients = await searchRows('{{ route('financial-requests.search-clients') }}', clientSearch.value);
            renderResults('clientResults', clients, @json(__('financial_requests.no_client_results')), selectClient);
        }

        async function searchMaids() {
            maids = await searchRows('{{ route('financial-requests.search-maids') }}', maidSearch.value);
            renderResults('maidResults', maids, @json(__('financial_requests.no_worker_results')), selectMaid);
        }

        document.getElementById('searchClientBtn').addEventListener('click', searchClients);
        document.getElementById('searchMaidBtn').addEventListener('click', searchMaids);
        document.getElementById('clientSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchClients();
            }
        });
        document.getElementById('maidSearch').addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchMaids();
            }
        });

        document.getElementById('fetchContract').addEventListener('click', async () => {
            const params = new URLSearchParams({contract_number: contractNumber.value, contract_type: contractType.value});
            const response = await fetch('{{ route('financial-requests.contract-details') }}?' + params.toString(), {headers: {'Accept': 'application/json'}});
            if (!response.ok) { alert(@json(__('financial_requests.contract_not_found'))); return; }
            const data = await response.json();
            contractId.value = data.contract_id || '';
            contractType.value = data.contract_type || '';
            contractNumber.value = data.contract_number || contractNumber.value;
            contractCost.value = data.contract_cost || '';
            contractSalary.value = data.contract_salary || '';
            selectClient({id: data.client_id, name: data.client_name, identity: data.client_identity, text: data.client_name || data.client_identity || ''});
            selectMaid({id: data.maid_id, name: data.maid_name, passport: data.maid_passport, text: data.maid_name || data.maid_passport || ''});
        });
    </script>
@endsection
