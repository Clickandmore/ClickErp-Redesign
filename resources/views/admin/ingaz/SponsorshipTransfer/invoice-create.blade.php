@extends('admin.layout.indexs.index')

@section('page-title', 'إصدار فاتورة نقل الخدمات')

@section('style')
    <style>
        .transfer-invoice-page { direction: rtl; }
        .transfer-invoice-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px; box-shadow: 0 12px 30px rgba(15, 23, 42, .06); }
        .transfer-invoice-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        .transfer-invoice-meta div { background: #f8fafc; border-radius: 12px; padding: 12px; }
        .transfer-invoice-meta small { display: block; color: #64748b; margin-bottom: 4px; }
        .transfer-invoice-meta strong { color: #0f172a; }
        .invoice-items-table th { white-space: nowrap; background: #f1f5f9; }
        .invoice-items-table td { vertical-align: middle; }
        .tax-locked { color: #64748b; font-size: 12px; font-weight: 700; }
        .totals-panel { max-width: 440px; margin-right: auto; background: #f8fafc; border-radius: 14px; padding: 16px; }
        .totals-row { display: flex; justify-content: space-between; gap: 20px; padding: 7px 0; }
        .totals-row.grand { border-top: 1px solid #cbd5e1; margin-top: 5px; padding-top: 12px; font-size: 18px; font-weight: 900; color: #0f766e; }
        @media (max-width: 900px) { .transfer-invoice-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .transfer-invoice-meta { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('content')
    <div class="container-fluid transfer-invoice-page py-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h3 class="mb-1">إصدار فاتورة نقل الخدمات #{{ $transfer->id }}</h3>
                <p class="text-muted mb-0">الضريبة ثابتة على تكاليف المرخص له فقط، ويمكن تحديدها لكل بند إضافي.</p>
            </div>
            <a href="{{ route('SponsorshipTransfer.show', $transfer->id) }}" class="btn btn-outline-secondary">العودة إلى الطلب</a>
        </div>

        <form id="transfer-invoice-form" action="{{ route('invoices.store') }}" method="post">
            @csrf
            <input type="hidden" name="sponsorship_transfer_id" value="{{ $transfer->id }}">

            <div class="transfer-invoice-card">
                <div class="transfer-invoice-meta">
                    <div><small>رقم الفاتورة المتوقع</small><strong>#{{ $nextInvoiceNumber }}</strong></div>
                    <div><small>العميل الجديد</small><strong>{{ $transfer->newSponsor?->name ?: 'غير محدد' }}</strong></div>
                    <div><small>العاملة</small><strong>{{ $transfer->contract?->maid?->name ?: 'غير محدد' }}</strong></div>
                    <div><small>قيمة طلب النقل</small><strong>{{ number_format($grossContractValue, 2) }} ريال</strong></div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">تاريخ الفاتورة</label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoiceIssuedAt->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">وصف الفاتورة</label>
                        <input type="text" name="general_description" class="form-control" maxlength="2000"
                               value="{{ old('general_description', 'عملية نقل الخدمات رقم ' . $transfer->id . ' - العاملة ' . ($transfer->contract?->maid?->name ?: 'غير محدد')) }}">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered invoice-items-table" id="invoice-items">
                        <thead>
                            <tr>
                                <th style="min-width:260px">البند</th>
                                <th style="width:120px">الكمية</th>
                                <th style="width:170px">السعر</th>
                                <th style="width:150px">حالة الضريبة</th>
                                <th style="width:150px">الإجمالي</th>
                                <th style="width:70px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $coreItems = [
                                    ['key' => 'licensee_cost', 'label' => 'تكاليف المرخص له', 'price' => $defaultLicenseeCost, 'taxable' => true],
                                    ['key' => 'external_recruitment', 'label' => 'تكاليف الاستقدام الخارجي', 'price' => 0, 'taxable' => false],
                                    ['key' => 'government_cost', 'label' => 'تكاليف حكومية', 'price' => 0, 'taxable' => false],
                                    ['key' => 'worker_salary', 'label' => 'الأجر الشهري للعاملة', 'price' => 0, 'taxable' => false],
                                ];
                            @endphp
                            @foreach($coreItems as $index => $item)
                                <tr class="invoice-line" data-core="1">
                                    <td>
                                        <input type="hidden" name="line_items[{{ $index }}][key]" value="{{ $item['key'] }}">
                                        <input type="text" name="line_items[{{ $index }}][label]" class="form-control line-label" value="{{ $item['label'] }}" required>
                                    </td>
                                    <td><input type="number" name="line_items[{{ $index }}][quantity]" class="form-control line-quantity" value="1" min="0.01" step="0.01" required></td>
                                    <td><input type="number" name="line_items[{{ $index }}][unit_price]" class="form-control line-price" value="{{ number_format($item['price'], 2, '.', '') }}" min="0" step="0.01" required></td>
                                    <td>
                                        <input type="hidden" name="line_items[{{ $index }}][taxable]" value="{{ $item['taxable'] ? 1 : 0 }}">
                                        <span class="tax-locked">{{ $item['taxable'] ? 'خاضع 15%' : 'غير خاضع' }}</span>
                                    </td>
                                    <td class="line-total fw-bold">0.00</td>
                                    <td><span class="text-muted">أساسي</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary mb-4" id="add-invoice-item">+ إضافة بند</button>

                <div class="row g-3 align-items-start">
                    <div class="col-lg-6">
                        <label class="form-label fw-bold">الخصم</label>
                        <input type="number" name="discount" id="invoice-discount" class="form-control" value="0.00" min="0" step="0.01">
                        <div class="form-check mt-3">
                            <input type="hidden" name="send_to_zatca" value="0">
                            <input class="form-check-input" type="checkbox" name="send_to_zatca" value="1" id="send-to-zatca" checked>
                            <label class="form-check-label" for="send-to-zatca">إدراج الفاتورة ضمن فواتير هيئة الزكاة</label>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            سند القبض لا يُنشأ مرة أخرى من هذه الشاشة؛ يستخدم النظام سند القبض المرتبط بطلب النقل.
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="totals-panel">
                            <div class="totals-row"><span>الإجمالي الفرعي</span><strong id="subtotal-value">0.00 ريال</strong></div>
                            <div class="totals-row"><span>الضريبة</span><strong id="vat-value">0.00 ريال</strong></div>
                            <div class="totals-row"><span>الخصم</span><strong id="discount-value">0.00 ريال</strong></div>
                            <div class="totals-row grand"><span>الإجمالي المستحق</span><strong id="grand-total-value">0.00 ريال</strong></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('SponsorshipTransfer.show', $transfer->id) }}" class="btn btn-light">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4" id="issue-invoice">إصدار الفاتورة</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('transfer-invoice-form');
            const tbody = document.querySelector('#invoice-items tbody');
            const addButton = document.getElementById('add-invoice-item');
            const submitButton = document.getElementById('issue-invoice');
            let nextIndex = {{ count($coreItems) }};

            function money(value) {
                return (Number(value) || 0).toFixed(2);
            }

            function recalculate() {
                let subtotal = 0;
                let vat = 0;
                tbody.querySelectorAll('.invoice-line').forEach(function (row) {
                    const quantity = Number(row.querySelector('.line-quantity').value) || 0;
                    const price = Number(row.querySelector('.line-price').value) || 0;
                    const lineSubtotal = quantity * price;
                    const taxableControl = row.querySelector('.line-taxable');
                    const taxable = taxableControl
                        ? taxableControl.value === '1'
                        : row.querySelector('input[name$="[taxable]"]').value === '1';
                    const lineVat = taxable ? lineSubtotal * .15 : 0;
                    subtotal += lineSubtotal;
                    vat += lineVat;
                    row.querySelector('.line-total').textContent = money(lineSubtotal + lineVat);
                });

                const discount = Math.min(Number(document.getElementById('invoice-discount').value) || 0, subtotal);
                document.getElementById('subtotal-value').textContent = money(subtotal) + ' ريال';
                document.getElementById('vat-value').textContent = money(vat) + ' ريال';
                document.getElementById('discount-value').textContent = money(discount) + ' ريال';
                document.getElementById('grand-total-value').textContent = money(Math.max(0, subtotal + vat - discount)) + ' ريال';
            }

            addButton.addEventListener('click', function () {
                const index = nextIndex++;
                const row = document.createElement('tr');
                row.className = 'invoice-line';
                row.innerHTML = `
                    <td>
                        <input type="hidden" name="line_items[${index}][key]" value="additional">
                        <input type="text" name="line_items[${index}][label]" class="form-control line-label" placeholder="اسم البند الإضافي" required>
                    </td>
                    <td><input type="number" name="line_items[${index}][quantity]" class="form-control line-quantity" value="1" min="0.01" step="0.01" required></td>
                    <td><input type="number" name="line_items[${index}][unit_price]" class="form-control line-price" value="0.00" min="0" step="0.01" required></td>
                    <td>
                        <select name="line_items[${index}][taxable]" class="form-select line-taxable">
                            <option value="0">غير خاضع</option>
                            <option value="1">خاضع 15%</option>
                        </select>
                    </td>
                    <td class="line-total fw-bold">0.00</td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">حذف</button></td>
                `;
                tbody.appendChild(row);
                row.querySelector('.line-label').focus();
            });

            tbody.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-line')) {
                    event.target.closest('tr').remove();
                    recalculate();
                }
            });
            form.addEventListener('input', recalculate);
            form.addEventListener('change', recalculate);

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                submitButton.disabled = true;
                const originalText = submitButton.textContent;
                submitButton.textContent = 'جارٍ إصدار الفاتورة...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'تعذر إصدار الفاتورة.');
                    }
                    window.location.href = result.redirect || result.view_url;
                } catch (error) {
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'تعذر إصدار الفاتورة', text: error.message });
                    } else {
                        alert(error.message);
                    }
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            });

            recalculate();
        });
    </script>
@endsection
