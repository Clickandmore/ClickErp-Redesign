@extends('click.layout.indexs.index')

@php
    $isEdit = isset($requestItem);
    $payload = old('payload', $isEdit ? ($requestItem->payload ?? []) : []);

@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">{{ $isEdit ? 'تعديل طلب مالي' : 'طلب مالي جديد' }}</h3>
            <a class="btn btn-outline-secondary" href="{{ route('financial-requests.index') }}">رجوع</a>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('financial-requests.update', $requestItem) : route('financial-requests.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header fw-bold"><i class="fa fa-file-invoice me-1"></i> البيانات الأساسية</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">المدفوع له <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[paid_to]" class="form-control" required value="{{ old('payload.paid_to', $payload['paid_to'] ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الفرع <span class="text-danger">*</span></label>
                                    <select name="branch_id" class="form-select" required>
                                        <option value="">اختر الفرع</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $requestItem->branch_id ?? auth('admin')->user()?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الاسم المحاسبي <span class="text-danger">*</span></label>
                                    <input type="text" name="payload[accounting_name]" class="form-control" required value="{{ old('payload.accounting_name', $payload['accounting_name'] ?? ((($requestItem->type ?? '') === 'client_refund') ? 'مردودات المبيعات' : '')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">نوع الطلب</label>
                                    <select name="type" id="requestType" class="form-select" required>
                                        <option value="" disabled {{ !old('type', $requestItem->type ?? null) ? 'selected' : '' }}>اختر نوع الطلب</option>
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('type', $requestItem->type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">التصعيد</label>
                                    <select name="priority" class="form-select" required>
                                        @foreach ($priorities as $key => $label)
                                            <option value="{{ $key }}" {{ old('priority', $requestItem->priority ?? 'normal') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">تاريخ الطلب</label>
                                    <input type="date" name="request_date" class="form-control" value="{{ old('request_date', optional($requestItem->request_date ?? now())->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المبلغ</label>
                                    <input type="number" step="0.01" min="0" name="amount" class="form-control" required value="{{ old('amount', $requestItem->amount ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">مدين / دائن <span class="text-danger">*</span></label>
                                    <select name="payload[debit_credit]" class="form-select" required>
                                        <option value="debit" {{ old('payload.debit_credit', $payload['debit_credit'] ?? 'debit') === 'debit' ? 'selected' : '' }}>مدين</option>
                                        <option value="credit" {{ old('payload.debit_credit', $payload['debit_credit'] ?? '') === 'credit' ? 'selected' : '' }}>دائن</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select name="payload[payment_method]" id="paymentMethod" class="form-select" required>
                                        <option value="bank_transfer" {{ old('payload.payment_method', $payload['payment_method'] ?? 'bank_transfer') === 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                        <option value="cash" {{ old('payload.payment_method', $payload['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>نقدي</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">وصف الدفع / الغرض منه</label>
                                    <textarea name="description" class="form-control" required>{{ old('description', $requestItem->description ?? '') }}</textarea>
                                </div>
                            </div>
                            <hr>
                            <div id="dynamicFields" class="row g-3"></div>
                        </div>
                    </div>

                    <div class="card mb-3" id="bankTransferCard">
                        <div class="card-header fw-bold"><i class="fa fa-university me-1"></i> بيانات التحويل البنكي <small class="text-muted fw-normal">(اختيارية)</small></div>
                        <div class="card-body">
                            <div class="alert alert-light border small">يمكن اختيار حساب من الدليل المحاسبي أو إدخال بيانات المستفيد يدويًا، وجميع الحقول هنا اختيارية.</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">الحساب المحاسبي</label>
                                    <select name="payload[account_id]" id="accountId" class="form-select js-create-account-select" data-placeholder="ابحث بالكود أو اسم الحساب">
                                        <option value=""></option>
                                        @if(old('payload.account_id', $payload['account_id'] ?? null))
                                            <option value="{{ old('payload.account_id', $payload['account_id'] ?? '') }}" selected>الحساب المحدد #{{ old('payload.account_id', $payload['account_id'] ?? '') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">اسم البنك</label>
                                    <input type="text" name="payload[bank_name]" class="form-control" value="{{ old('payload.bank_name', $payload['bank_name'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">اسم صاحب الحساب</label>
                                    <input type="text" name="payload[account_holder_name]" class="form-control" value="{{ old('payload.account_holder_name', $payload['account_holder_name'] ?? '') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الحساب</label>
                                    <input type="text" name="payload[account_number]" class="form-control" value="{{ old('payload.account_number', $payload['account_number'] ?? '') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">رقم الآيبان</label>
                                    <input type="text" name="payload[iban]" class="form-control text-uppercase" maxlength="34" dir="ltr" value="{{ old('payload.iban', $payload['iban'] ?? '') }}" placeholder="SA00 0000 0000 0000 0000 0000">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header fw-bold"><i class="fa fa-paperclip me-1"></i> المرفقات</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">مرفق الطلب الرئيسي</label>
                                    <input type="hidden" name="attachment_labels[main]" value="مرفق الطلب الرئيسي">
                                    <input type="text" name="attachment_descriptions[main]" class="form-control mb-2" placeholder="وصف المرفق">
                                    <input type="file" name="attachments[main][]" class="form-control" multiple>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ملفات أخرى</label>
                                    <input type="hidden" name="attachment_labels[other]" value="ملفات أخرى">
                                    <input type="text" name="attachment_descriptions[other]" class="form-control mb-2" placeholder="وصف الملفات الأخرى">
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
                        <div class="card-header fw-bold"><i class="fa fa-link me-1"></i> ربط العميل والعقد</div>
                        <div class="card-body">
                            <label class="form-label">العميل</label>
                            <div class="input-group mb-2">
                                <input type="text" id="clientSearch" class="form-control" placeholder="ابحث باسم العميل أو رقم الهوية" value="{{ old('client_name', $requestItem->client_name ?? '') }}">
                                <button class="btn btn-outline-secondary" type="button" id="searchClientBtn">بحث</button>
                            </div>
                            <div id="clientResults" class="list-group small mb-2"></div>
                            <input type="hidden" name="client_id" id="clientId" value="{{ old('client_id', $requestItem->client_id ?? '') }}">
                            <input type="text" name="client_name" id="clientName" class="form-control mb-2" placeholder="اسم العميل" value="{{ old('client_name', $requestItem->client_name ?? '') }}">
                            <input type="text" name="client_identity" id="clientIdentity" class="form-control mb-3" placeholder="رقم الهوية" value="{{ old('client_identity', $requestItem->client_identity ?? '') }}">

                            <label class="form-label">العاملة</label>
                            <div class="input-group mb-2">
                                <input type="text" id="maidSearch" class="form-control" placeholder="ابحث باسم العاملة أو رقم الجواز" value="{{ old('maid_name', $requestItem->maid_name ?? '') }}">
                                <button class="btn btn-outline-secondary" type="button" id="searchMaidBtn">بحث</button>
                            </div>
                            <div id="maidResults" class="list-group small mb-2"></div>
                            <input type="hidden" name="maid_id" id="maidId" value="{{ old('maid_id', $requestItem->maid_id ?? '') }}">
                            <input type="text" name="maid_name" id="maidName" class="form-control mb-2" placeholder="اسم العاملة" value="{{ old('maid_name', $requestItem->maid_name ?? '') }}">
                            <input type="text" name="maid_passport" id="maidPassport" class="form-control mb-3" placeholder="رقم الجواز" value="{{ old('maid_passport', $requestItem->maid_passport ?? '') }}">

                            <label class="form-label">العقد</label>
                            <div class="input-group mb-2">
                                <select name="contract_type" id="contractType" class="form-select" style="max-width: 140px">
                                    <option value="">تلقائي</option>
                                    @foreach ($contractTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('contract_type', $requestItem->contract_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="contract_number" id="contractNumber" class="form-control" value="{{ old('contract_number', $requestItem->contract_number ?? '') }}" placeholder="رقم العقد">
                                <button class="btn btn-outline-primary" type="button" id="fetchContract">سحب</button>
                            </div>
                            <input type="hidden" name="contract_id" id="contractId" value="{{ old('contract_id', $requestItem->contract_id ?? '') }}">
                            <input type="number" step="0.01" name="contract_cost" id="contractCost" class="form-control mb-2" placeholder="تكلفة العقد" value="{{ old('contract_cost', $requestItem->contract_cost ?? '') }}">
                            <input type="number" step="0.01" name="contract_salary" id="contractSalary" class="form-control" placeholder="الراتب" value="{{ old('contract_salary', $requestItem->contract_salary ?? '') }}">

                            <div class="d-grid gap-2 mt-3">
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#musanedModal">
                                    <i class="fa fa-search"></i> جلب من مساند
                                </button>
                                <a class="btn btn-success" href="https://musaned.com.sa" target="_blank" rel="noopener">
                                    <i class="fa fa-external-link-alt"></i> فتح مساند مباشرة
                                </a>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">{{ $isEdit ? 'تحديث وإعادة الإرسال' : 'إرسال طلب الاعتماد' }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="musanedModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">البحث في مساند</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="رقم طلب مساند أو رقم هوية">
                        <a class="btn btn-success" href="https://musaned.com.sa" target="_blank" rel="noopener">فتح مساند</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const typeFields = @json($typeFields);
        const payload = @json($payload);
        const fieldFallbackLabels = {
            musaned_request_no: 'رقم طلب مساند',
            musaned_invoice_no: 'رقم فاتورة مساند',
            visa_number: 'رقم التأشيرة',
            government_reference: 'رقم المرجع الحكومي',
            enjaz_number: 'رقم إنجاز',
            authorization_no: 'رقم التفويض',
            iqama_number: 'رقم الإقامة',
            period: 'المدة',
            permit_number: 'رقم رخصة العمل',
            policy_number: 'رقم الوثيقة',
            insurance_company: 'شركة التأمين',
            salary_month: 'شهر الراتب',
            salary_days: 'عدد الأيام',
            refund_reason: 'سبب الاسترداد',
            iban: 'آيبان العميل',
            subject: 'موضوع الطلب',
            reference_no: 'رقم مرجعي'
        };

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
                placeholder: 'ابحث بالكود أو اسم الحساب',
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
            renderResults('clientResults', clients, 'لا توجد نتائج. يمكنك إدخال بيانات العميل يدوياً.', selectClient);
        }

        async function searchMaids() {
            maids = await searchRows('{{ route('financial-requests.search-maids') }}', maidSearch.value);
            renderResults('maidResults', maids, 'لا توجد نتائج. يمكنك إدخال بيانات العاملة يدوياً.', selectMaid);
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
            if (!response.ok) { alert('لم يتم العثور على العقد'); return; }
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
