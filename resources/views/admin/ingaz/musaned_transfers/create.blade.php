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

        @if ($eligibleInvoices->isEmpty())
            <div class="alert alert-info">
                لا توجد فواتير مؤهلة حالياً لتسجيل تحويل مساند. يجب أن تكون الفاتورة موجودة ولم يُسجل لها تحويل سابق.
            </div>
        @else
            @php
                $invoiceOptions = $eligibleInvoices
                    ->map(function ($invoice) {
                        $contract = $invoice->contract;
                        $delegation = $invoice->ingazDelegation;
                        $clientName = $contract?->user?->name ?: ($delegation?->user?->name ?: 'غير محدد');
                        $msanedNumber = $contract?->info?->msaned_number ?: ($delegation?->msaned_contract_number ?: '-');
                        $sourceType = $contract ? 'contract' : 'delegation';
                        $sourceId = $contract?->id ?: $delegation?->id;
                        $sourceLabel = $contract ? ('عقد #' . $contract->id) : ('تفويض #' . $delegation?->id);

                        return [
                            'contract_id' => $contract?->id,
                            'ingaz_delegation_id' => $delegation?->id,
                            'source_type' => $sourceType,
                            'source_id' => $sourceId,
                            'source_key' => $sourceType . ':' . $sourceId,
                            'invoice_id' => $invoice->id,
                            'label' => $sourceLabel . ' - ' . $clientName . ' - مساند ' . $msanedNumber,
                            'client_name' => $clientName,
                            'msaned_number' => $msanedNumber,
                            'invoice_total' => (float) $invoice->total_due,
                        ];
                    })
                    ->filter(fn ($item) => !empty($item['source_id']))
                    ->values();
            @endphp

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
                                <select class="form-control" id="bank_account_id" name="bank_account_id" required>
                                    <option value="">اختر الحساب البنكي</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}"
                                            @selected((string) old('bank_account_id') === (string) $account->id)>
                                            {{ $account->code }} / {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="contract_selector" class="form-label">العقود والتفاويض المشمولة في الحوالة</label>
                                <select class="form-control" id="contract_selector" multiple>
                                    @foreach ($invoiceOptions as $option)
                                        <option value="{{ $option['source_key'] }}"
                                            @selected(
                                                ($option['source_type'] === 'contract' && in_array($option['source_id'], old('selected_contract_ids', $selectedContractIds), true))
                                                || ($option['source_type'] === 'delegation' && in_array($option['source_id'], old('selected_ingaz_delegation_ids', $selectedIngazDelegationIds ?? []), true))
                                            )>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
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
                                        <th>عمولة مساند</th>
                                        <th>ضريبة المصروفات</th>
                                        <th>صافي العمولة</th>
                                    </tr>
                                </thead>
                                <tbody id="transfer_items_body">
                                    <tr id="empty_transfer_row">
                                        <td colspan="8" class="text-center py-4 text-muted">
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
                        <i class="fas fa-save me-1"></i>حفظ التحويل وإنشاء القيود
                    </button>
                    <a href="{{ route('musaned-transfers.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>

            <script>
                (function() {
                    const expenseTaxRate = {{ json_encode((float) $expenseTaxRate) }};
                    const contractsLookup = @json($invoiceOptions);
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
                    const body = document.getElementById('transfer_items_body');
                    const emptyRow = document.getElementById('empty_transfer_row');

                    const summaries = {
                        customerPaid: document.getElementById('summary_customer_paid'),
                        transferred: document.getElementById('summary_transferred'),
                        commission: document.getElementById('summary_commission'),
                        tax: document.getElementById('summary_tax'),
                    };

                    const oldTransferredMap = {};
                    selectedFromOld.forEach(function(item) {
                        const sourceKey = item.contract_id
                            ? `contract:${parseInt(item.contract_id, 10)}`
                            : (item.ingaz_delegation_id ? `delegation:${parseInt(item.ingaz_delegation_id, 10)}` : null);

                        if (sourceKey) {
                            oldTransferredMap[sourceKey] = item.transferred_amount || '';
                        }
                    });

                    function roundMoney(value) {
                        return Math.round((value + Number.EPSILON) * 100) / 100;
                    }

                    function formatMoney(value) {
                        return roundMoney(parseFloat(value) || 0).toFixed(2);
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

                    function buildRow(option, index) {
                        const row = document.createElement('tr');
                        row.className = 'transfer-item-row';
                        row.dataset.sourceKey = option.source_key;
                        row.dataset.invoiceTotal = option.invoice_total;

                        const transferredValue = oldTransferredMap[option.source_key] || '';
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
                        `;

                        row.querySelector('.transferred-amount').addEventListener('input', function() {
                            updateRowCalculations(row);
                            updateSummary();
                        });

                        updateRowCalculations(row);

                        return row;
                    }

                    function renderRows() {
                        const selectedKeys = Array.from(selector.selectedOptions).map(option => option.value);
                        body.innerHTML = '';

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

                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                        window.jQuery(selector).select2({
                            placeholder: 'اختر العقود أو التفاويض المشمولة في الحوالة'
                        });

                        window.jQuery(selector).on('change', renderRows);
                    } else {
                        selector.addEventListener('change', renderRows);
                    }

                    if (initialSelectedKeys && initialSelectedKeys.length) {
                        Array.from(selector.options).forEach(function(option) {
                            option.selected = initialSelectedKeys.includes(option.value);
                        });
                    }

                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                        window.jQuery(selector).trigger('change');
                    }

                    renderRows();
                })();
            </script>
        @endif
    </div>
@endsection
