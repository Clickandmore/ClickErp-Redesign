@extends('admin.layout.indexs.index')

@section('page-title', 'إضافة تحويل مساند')

@section('style')
    <style>
        .musaned-transfer-card .table th,
        .musaned-transfer-card .table td {
            vertical-align: middle;
        }

        .musaned-transfer-card .summary-box {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 16px;
            background: #f8fafc;
        }

        .musaned-transfer-card .summary-label {
            color: #6c757d;
            font-size: 13px;
        }

        .musaned-transfer-card .summary-value {
            font-size: 20px;
            font-weight: 700;
        }

        .musaned-transfer-card .selected-transfer-sources {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 38px;
            border: 1px dashed #d8dee9;
            border-radius: 8px;
            padding: 8px;
            background: #f8fafc;
        }

        .musaned-transfer-card .selected-transfer-source {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            border: 1px solid #cfe2ff;
            border-radius: 6px;
            padding: 6px 10px;
            background: #eef6ff;
            color: #084298;
            font-size: 13px;
        }

        .musaned-transfer-card .selected-transfer-source span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .musaned-transfer-card .selected-transfer-source button {
            flex: 0 0 auto;
            border: 0;
            padding: 0;
            background: transparent;
            color: #842029;
            line-height: 1;
        }

        .musaned-transfer-card .transfer-source-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
        }

        .musaned-transfer-card .transfer-source-result {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            border: 0;
            border-bottom: 1px solid #edf0f2;
            padding: 10px 12px;
            background: #fff;
            color: #212529;
            text-align: right;
        }

        .musaned-transfer-card .transfer-source-result:last-child {
            border-bottom: 0;
        }

        .musaned-transfer-card .transfer-source-result:not(:disabled):hover,
        .musaned-transfer-card .transfer-source-result:not(:disabled):focus {
            background: #eef6ff;
            outline: 0;
        }

        .musaned-transfer-card .transfer-source-result:disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: default;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid musaned-transfer-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">إضافة تحويل مساند</h4>
                <div class="text-muted">يمكن تسجيل حوالة لعقد أو تفويض إنجاز أو عدة بنود تحت مرجع مساند واحد.</div>
            </div>
            <a href="{{ route('musaned-transfers.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>الرجوع للتحويلات
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$hasEligibleInvoices)
            <div class="alert alert-info">
                لا توجد فواتير مؤهلة حالياً لتسجيل تحويل مساند. يجب أن تكون الفاتورة موجودة ولم يُسجل لها تحويل سابق.
            </div>
        @else
            <form method="POST" action="{{ route('musaned-transfers.store') }}">
                @csrf

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="reference_number" class="form-label">مرجع الحوالة</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number"
                                    value="{{ old('reference_number') }}" placeholder="UTI Ref أو رقم الحوالة">
                            </div>
                            <div class="col-md-4">
                                <label for="transfer_date" class="form-label">تاريخ الحوالة</label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date"
                                    value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="bank_account_id" class="form-label">الحساب البنكي</label>
                                <select class="form-control select2" id="bank_account_id" name="bank_account_id" @required($automaticEntriesEnabled ?? true)>
                                    <option value="">اختر الحساب البنكي</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}"
                                            @selected((string) old('bank_account_id') === (string) $account->id)>{{ $account->code }} / {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="contract_musaned_search" class="form-label">العقود والتفاويض المشمولة في الحوالة</label>
                                <div class="input-group">
                                    <input type="search" class="form-control" id="contract_musaned_search"
                                        placeholder="ابحث برقم عقد مساند أو اسم العميل أو رقم العقد"
                                        autocomplete="off" aria-controls="contract_search_results">
                                    <button type="button" class="btn btn-outline-secondary" id="clear_contract_search"
                                        aria-label="مسح البحث" title="مسح البحث">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center my-2">
                                    <small class="text-muted">تظهر أحدث العقود غير المحولة، ويمكنك البحث للوصول إلى بقية العقود.</small>
                                    <span class="badge bg-primary" id="selected_sources_count">0 محدد</span>
                                </div>
                                <div id="contract_search_results" class="transfer-source-results mb-2"
                                    role="listbox" aria-label="نتائج البحث عن العقود والتفاويض"></div>
                                <select class="d-none" id="contract_selector" multiple aria-hidden="true" tabindex="-1">
                                    @foreach ($invoiceOptions as $option)
                                        <option value="{{ $option['source_key'] }}"
                                            data-search-text="{{ $option['label'] }} {{ $option['msaned_number'] }}"
                                            @selected(
                                                ($option['source_type'] === 'contract' && in_array($option['source_id'], old('selected_contract_ids', $selectedContractIds), true))
                                                || ($option['source_type'] === 'delegation' && in_array($option['source_id'], old('selected_ingaz_delegation_ids', $selectedIngazDelegationIds ?? []), true))
                                            )>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="selected_transfer_sources_preview" class="selected-transfer-sources mt-2">
                                    <span class="text-muted">لم يتم اختيار أي عقود أو تفاويض بعد</span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    العمولة = قيمة الفاتورة - المبلغ المحول من مساند. ضريبة المصروفات تحتسب وفق نسبة {{ number_format($expenseTaxRate, 2) }}%.
                                </small>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">ملاحظات</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                    placeholder="أي ملاحظات إضافية على الحوالة">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>تفاصيل البنود</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>البند</th>
                                        <th>العميل</th>
                                        <th>رقم مساند</th>
                                        <th>قيمة الفاتورة</th>
                                        <th>المبلغ المحول من مساند</th>
                                        <th>التكاليف البنكية</th>
                                        <th>ضريبة المصروفات</th>
                                        <th>صافي العمولة</th>
                                        <th class="text-center">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="transfer_items_body">
                                    <tr id="empty_transfer_row">
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            اختر عقداً أو تفويضاً واحداً أو أكثر لإضافة بنود الحوالة.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="summary-box">
                            <div class="summary-label mb-1">إجمالي قيمة العميل</div>
                            <div class="summary-value" id="summary_customer_paid">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <div class="summary-label mb-1">إجمالي المحول من مساند</div>
                            <div class="summary-value text-primary" id="summary_transferred">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <div class="summary-label mb-1">إجمالي العمولة</div>
                            <div class="summary-value text-danger" id="summary_commission">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-box">
                            <div class="summary-label mb-1">إجمالي ضريبة المصروفات</div>
                            <div class="summary-value text-success" id="summary_tax">0.00</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>{{ \App\Support\InvoiceSupplierWorkflow::enabled() ? 'تأكيد وصول المبلغ من مساند' : 'حفظ التحويل' }}
                    </button>
                    <a href="{{ route('musaned-transfers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>

            <script>
                $(document).ready(function() {
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                        $('#bank_account_id').select2({ width: '100%' });
                    }
                });
            </script>

            <script>
                (function() {
                    const expenseTaxRate = {{ json_encode((float) $expenseTaxRate) }};
                    const searchUrl = @json(route('musaned-transfers.search'));
                    const contractsLookup = @json($invoiceOptions);
                    let displayedOptions = [];
                    let searchTimer = null;
                    let searchSequence = 0;
                    const selectedFromOld = @json(old('items', []));
                    const initialSelectedKeys = @json(
                        collect(old('items', []))
                            ->map(function ($item) {
                                if (!empty($item['contract_id'])) {
                                    return 'contract:' . (int) $item['contract_id'];
                                }

                                if (!empty($item['ingaz_delegation_id'])) {
                                    return 'delegation:' . (int) $item['ingaz_delegation_id'];
                                }

                                return null;
                            })
                            ->filter()
                            ->values()
                            ->all() ?:
                            collect($selectedContractIds)
                                ->map(fn($id) => 'contract:' . (int) $id)
                                ->merge(collect($selectedIngazDelegationIds ?? [])->map(fn($id) => 'delegation:' . (int) $id))
                                ->values()
                                ->all()
                    );
                    const selector = document.getElementById('contract_selector');
                    const searchInput = document.getElementById('contract_musaned_search');
                    const clearSearchButton = document.getElementById('clear_contract_search');
                    const searchResults = document.getElementById('contract_search_results');
                    const selectedCount = document.getElementById('selected_sources_count');
                    const body = document.getElementById('transfer_items_body');
                    const emptyRow = document.getElementById('empty_transfer_row');
                    const selectedPreview = document.getElementById('selected_transfer_sources_preview');

                    const summaries = {
                        customerPaid: document.getElementById('summary_customer_paid'),
                        transferred: document.getElementById('summary_transferred'),
                        commission: document.getElementById('summary_commission'),
                        tax: document.getElementById('summary_tax'),
                    };

                    const transferredValues = {};
                    selectedFromOld.forEach(function(item) {
                        const sourceKey = item.contract_id
                            ? `contract:${parseInt(item.contract_id, 10)}`
                            : (item.ingaz_delegation_id ? `delegation:${parseInt(item.ingaz_delegation_id, 10)}` : null);

                        if (sourceKey) {
                            transferredValues[sourceKey] = item.transferred_amount || '';
                        }
                    });

                    function roundMoney(value) {
                        return Math.round((value + Number.EPSILON) * 100) / 100;
                    }

                    function formatMoney(value) {
                        return roundMoney(parseFloat(value) || 0).toFixed(2);
                    }

                    function normalizeDigits(value) {
                        const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
                        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';

                        return String(value || '').replace(/[٠-٩۰-۹]/g, function(digit) {
                            const arabicIndex = arabicDigits.indexOf(digit);
                            if (arabicIndex !== -1) {
                                return arabicIndex;
                            }

                            return persianDigits.indexOf(digit);
                        });
                    }

                    function normalizeSearch(value) {
                        return normalizeDigits(value)
                            .toLowerCase()
                            .replace(/[\s\-_/\\.:#]+/g, '');
                    }

                    function sourceMatchesSearch(option, normalizedTerm) {
                        if (!normalizedTerm) {
                            return true;
                        }

                        const searchText = `${option.label || ''} ${option.msaned_number || ''} ${option.client_name || ''}`;
                        return normalizeSearch(searchText).includes(normalizedTerm);
                    }

                    function renderSearchResults(message) {
                        const normalizedTerm = normalizeSearch(searchInput ? searchInput.value : '');
                        const selectedKeys = new Set(getSelectedKeys());
                        const matches = displayedOptions
                            .filter(option => sourceMatchesSearch(option, normalizedTerm))
                            .slice(0, 50);

                        searchResults.innerHTML = '';
                        selectedCount.textContent = `${selectedKeys.size} محدد`;

                        if (!matches.length) {
                            const emptyMessage = document.createElement('div');
                            emptyMessage.className = 'text-center text-muted py-3 px-2';
                            emptyMessage.textContent = message || 'لا توجد نتائج مطابقة للبحث.';
                            searchResults.appendChild(emptyMessage);
                            return;
                        }

                        matches.forEach(function(option) {
                            const isSelected = selectedKeys.has(option.source_key);
                            const result = document.createElement('button');
                            result.type = 'button';
                            result.className = 'transfer-source-result';
                            result.disabled = isSelected;
                            result.setAttribute('role', 'option');
                            result.setAttribute('aria-selected', isSelected ? 'true' : 'false');

                            const label = document.createElement('span');
                            label.textContent = option.label;
                            result.appendChild(label);

                            const status = document.createElement('span');
                            status.className = isSelected ? 'badge bg-success' : 'badge bg-light text-primary border';
                            status.textContent = isSelected ? 'تمت الإضافة' : 'إضافة';
                            result.appendChild(status);

                            result.addEventListener('click', function() {
                                setOptionSelected(option.source_key, true);
                                searchInput.focus();
                            });

                            searchResults.appendChild(result);
                        });
                    }

                    function mergeOptions(options) {
                        options.forEach(function(option) {
                            if (!contractsLookup.some(item => item.source_key === option.source_key)) {
                                contractsLookup.push(option);
                            }

                            if (!Array.from(selector.options).some(item => item.value === option.source_key)) {
                                const selectorOption = document.createElement('option');
                                selectorOption.value = option.source_key;
                                selectorOption.textContent = option.label;
                                selector.appendChild(selectorOption);
                            }
                        });
                    }

                    function searchSources() {
                        const term = searchInput ? searchInput.value.trim() : '';
                        const currentSequence = ++searchSequence;

                        if (normalizeSearch(term).length === 1) {
                            displayedOptions = [];
                            renderSearchResults('اكتب حرفين على الأقل للبحث.');
                            return;
                        }

                        renderSearchResults('جارٍ البحث...');
                        fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Search request failed');
                                }
                                return response.json();
                            })
                            .then(payload => {
                                if (currentSequence !== searchSequence) {
                                    return;
                                }
                                displayedOptions = Array.isArray(payload.data) ? payload.data : [];
                                mergeOptions(displayedOptions);
                                renderSearchResults();
                            })
                            .catch(() => {
                                if (currentSequence === searchSequence) {
                                    displayedOptions = [];
                                    renderSearchResults('تعذر إتمام البحث. حاول مرة أخرى.');
                                }
                            });
                    }

                    function calculateExpenseTax(commission) {
                        const numericCommission = parseFloat(commission) || 0;
                        if (numericCommission <= 0 || expenseTaxRate <= 0) {
                            return 0;
                        }

                        const divider = 1 + (expenseTaxRate / 100);
                        return roundMoney(numericCommission - (numericCommission / divider));
                    }

                    function updateRowCalculations(row) {
                        const invoiceTotal = parseFloat(row.dataset.invoiceTotal || 0);
                        const transferredInput = row.querySelector('.transferred-amount');
                        const commissionInput = row.querySelector('.commission-amount');
                        const taxInput = row.querySelector('.expense-tax-amount');
                        const commissionNetInput = row.querySelector('.commission-net-amount');

                        let transferred = parseFloat(transferredInput.value || 0);
                        if (transferred > invoiceTotal) {
                            transferred = invoiceTotal;
                            transferredInput.value = formatMoney(invoiceTotal);
                        }

                        if (transferred < 0) {
                            transferred = 0;
                            transferredInput.value = '';
                        }

                        const commission = roundMoney(invoiceTotal - transferred);
                        const tax = calculateExpenseTax(commission);
                        const commissionNet = roundMoney(commission - tax);

                        commissionInput.value = formatMoney(commission);
                        taxInput.value = formatMoney(tax);
                        commissionNetInput.value = formatMoney(commissionNet);
                    }

                    function updateSummary() {
                        let customerPaid = 0;
                        let transferred = 0;
                        let commission = 0;
                        let tax = 0;

                        body.querySelectorAll('tr.transfer-item-row').forEach(function(row) {
                            customerPaid += parseFloat(row.dataset.invoiceTotal || 0);
                            transferred += parseFloat(row.querySelector('.transferred-amount').value || 0);
                            commission += parseFloat(row.querySelector('.commission-amount').value || 0);
                            tax += parseFloat(row.querySelector('.expense-tax-amount').value || 0);
                        });

                        summaries.customerPaid.textContent = formatMoney(customerPaid);
                        summaries.transferred.textContent = formatMoney(transferred);
                        summaries.commission.textContent = formatMoney(commission);
                        summaries.tax.textContent = formatMoney(tax);
                    }

                    function getSelectedKeys() {
                        return Array.from(selector.selectedOptions).map(option => option.value);
                    }

                    function setOptionSelected(sourceKey, selected) {
                        const option = Array.from(selector.options).find(item => item.value === sourceKey);
                        if (!option) {
                            return;
                        }

                        option.selected = selected;
                        handleSelectorChange();
                    }

                    function renderSelectedPreview(selectedKeys) {
                        selectedPreview.innerHTML = '';

                        if (!selectedKeys.length) {
                            const emptyText = document.createElement('span');
                            emptyText.className = 'text-muted';
                            emptyText.textContent = 'لم يتم اختيار أي عقود أو تفاويض بعد';
                            selectedPreview.appendChild(emptyText);
                            return;
                        }

                        selectedKeys.forEach(function(sourceKey) {
                            const option = contractsLookup.find(item => item.source_key === sourceKey);
                            if (!option) {
                                return;
                            }

                            const item = document.createElement('span');
                            item.className = 'selected-transfer-source';

                            const label = document.createElement('span');
                            label.textContent = option.label;
                            item.appendChild(label);

                            const removeButton = document.createElement('button');
                            removeButton.type = 'button';
                            removeButton.setAttribute('aria-label', 'إزالة');
                            removeButton.innerHTML = '&times;';
                            removeButton.addEventListener('click', function() {
                                setOptionSelected(sourceKey, false);
                            });
                            item.appendChild(removeButton);

                            selectedPreview.appendChild(item);
                        });
                    }

                    function buildRow(option, index) {
                        const row = document.createElement('tr');
                        row.className = 'transfer-item-row';
                        row.dataset.sourceKey = option.source_key;
                        row.dataset.invoiceTotal = option.invoice_total;

                        const transferredValue = transferredValues[option.source_key] ?? option.expected_transfer ?? '';
                        const hiddenSourceInput = option.source_type === 'contract'
                            ? `<input type="hidden" name="items[${index}][contract_id]" value="${option.contract_id}">`
                            : `<input type="hidden" name="items[${index}][ingaz_delegation_id]" value="${option.ingaz_delegation_id}">`;
                        const sourceText = option.source_type === 'contract'
                            ? `عقد #${option.contract_id}`
                            : `تفويض #${option.ingaz_delegation_id}`;

                        row.innerHTML = `
                            <td>
                                ${hiddenSourceInput}
                                <div class="fw-semibold">${sourceText}</div>
                            </td>
                            <td>${option.client_name}</td>
                            <td>${option.msaned_number}</td>
                            <td>
                                <input type="text" class="form-control" value="${formatMoney(option.invoice_total)}" readonly>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" max="${formatMoney(option.invoice_total)}"
                                    class="form-control transferred-amount"
                                    name="items[${index}][transferred_amount]"
                                    value="${transferredValue}">
                            </td>
                            <td>
                                <input type="text" class="form-control commission-amount" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control expense-tax-amount" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control commission-net-amount" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-transfer-item"
                                    title="حذف هذا العقد" aria-label="حذف ${sourceText}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        `;

                        row.querySelector('.transferred-amount').addEventListener('input', function() {
                            transferredValues[option.source_key] = this.value;
                            updateRowCalculations(row);
                            updateSummary();
                        });

                        row.querySelector('.remove-transfer-item').addEventListener('click', function() {
                            setOptionSelected(option.source_key, false);
                        });

                        updateRowCalculations(row);

                        return row;
                    }

                    function renderRows() {
                        body.querySelectorAll('tr.transfer-item-row').forEach(function(row) {
                            const input = row.querySelector('.transferred-amount');
                            if (input) {
                                transferredValues[row.dataset.sourceKey] = input.value;
                            }
                        });

                        const selectedKeys = getSelectedKeys();
                        body.innerHTML = '';
                        renderSelectedPreview(selectedKeys);

                        if (!selectedKeys.length) {
                            body.appendChild(emptyRow);
                            updateSummary();
                            return;
                        }

                        selectedKeys.forEach(function(sourceKey, index) {
                            const option = contractsLookup.find(item => item.source_key === sourceKey);
                            if (!option) {
                                return;
                            }

                            body.appendChild(buildRow(option, index));
                        });

                        updateSummary();
                    }

                    function handleSelectorChange() {
                        renderRows();
                        renderSearchResults();
                    }

                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            window.clearTimeout(searchTimer);
                            searchTimer = window.setTimeout(searchSources, 250);
                        });
                        searchInput.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape') {
                                searchInput.value = '';
                                searchSources();
                            }

                            if (event.key === 'Enter') {
                                const firstResult = searchResults.querySelector('.transfer-source-result:not(:disabled)');
                                if (firstResult) {
                                    event.preventDefault();
                                    firstResult.click();
                                }
                            }
                        });
                    }

                    if (clearSearchButton) {
                        clearSearchButton.addEventListener('click', function() {
                            searchInput.value = '';
                            searchSources();
                            searchInput.focus();
                        });
                    }

                    if (initialSelectedKeys && initialSelectedKeys.length) {
                        Array.from(selector.options).forEach(function(option) {
                            option.selected = initialSelectedKeys.includes(option.value);
                        });
                    }

                    renderRows();
                    searchSources();
                })();
            </script>
        @endif
    </div>
@endsection
