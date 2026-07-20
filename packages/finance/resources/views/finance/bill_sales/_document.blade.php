@php
    $invoiceNumber = $obj->sourceInvoice?->legacy_invoice_number ?: $obj->code;
    $supplierName = $zatcaSetting?->VAT_name
        ?: (isset($setting_web) ? $setting_web->translate('ar')->website_name : '');
    $supplierNameEn = isset($setting_web) ? $setting_web->translate('en')->website_name : '';
    $supplierVat = $zatcaSetting?->VAT_number ?: ($setting_web->tax_number ?? '');
    $supplierCrn = $zatcaSetting?->CRN_number ?: ($setting_web->commercial_register ?? '');
    $supplierAddress = collect([
        $zatcaSetting?->street,
        $zatcaSetting?->building ? 'مبنى ' . $zatcaSetting->building : null,
        $zatcaSetting?->city_subdivision,
        $zatcaSetting?->city,
        $zatcaSetting?->postal_zone,
    ])->filter()->implode('، ');
    $clientAddress = collect([$obj->client?->address, $obj->client?->city, $obj->client?->postal_zone])
        ->filter()
        ->implode('، ');
    $logo = isset($setting_web) && $setting_web->logo_header ? showFile($setting_web->logo_header) : null;
    if (!$logo && function_exists('documentLogoUrl')) {
        $logo = documentLogoUrl();
    }
    $zatcaStatus = $obj->zatca?->zatca_status;
@endphp

<article class="invoice-document">
    <header class="invoice-document__brand">
        @if ($logo)
            <img src="{{ $logo }}" alt="{{ $supplierName }}">
        @else
            <div class="invoice-document__brand-name">
                {{ $supplierName ?: 'فاتورة مبيعات' }}
                @if ($supplierNameEn)
                    <small dir="ltr" class="d-block">{{ $supplierNameEn }}</small>
                @endif
            </div>
        @endif
    </header>

    <div class="invoice-document__title">
        <h1>فاتورة ضريبية</h1>
        <p dir="ltr">Tax Invoice</p>
    </div>

    <section class="invoice-meta">
        <div class="invoice-meta__column invoice-meta__column--en">
            <div class="invoice-meta__item">
                <strong>Invoice number</strong>
                <span>{{ $invoiceNumber }}</span>
            </div>
            <div class="invoice-meta__item">
                <strong>Invoice type</strong>
                <span>Tax invoice</span>
            </div>
            <div class="invoice-meta__item">
                <strong>Issue date</strong>
                <span>{{ $obj->date }}</span>
            </div>
            <div class="invoice-meta__item">
                <strong>Supply date</strong>
                <span>{{ $obj->date }}</span>
            </div>
        </div>

        <div class="invoice-meta__qr">
            @if ($obj->zatca?->qr)
                <img src="{{ (new \chillerlan\QRCode\QRCode())->render($obj->zatca->qr) }}"
                    alt="ZATCA QR code">
            @else
                <div class="invoice-meta__qr-placeholder">
                    لم تتم مشاركة الفاتورة<br>مع هيئة الزكاة
                </div>
            @endif
        </div>

        <div class="invoice-meta__column">
            <div class="invoice-meta__item">
                <strong>رقم الفاتورة</strong>
                <span>{{ $invoiceNumber }}</span>
            </div>
            <div class="invoice-meta__item">
                <strong>نوع الفاتورة</strong>
                <span>فاتورة ضريبية</span>
            </div>
            <div class="invoice-meta__item">
                <strong>تاريخ إصدار الفاتورة</strong>
                <span>{{ $obj->date }}</span>
            </div>
            <div class="invoice-meta__item">
                <strong>حالة الهيئة</strong>
                <span>{{ $zatcaStatus ?: 'غير مشاركة' }}</span>
            </div>
        </div>
    </section>

    <section class="invoice-section">
        <h2 class="invoice-section__title">
            <span>هوية المورد</span>
            <span dir="ltr">Seller Identification</span>
        </h2>
        <div class="invoice-fields">
            <div class="invoice-field invoice-field--wide">
                <div class="invoice-field__label">
                    <span>اسم المورد</span><span dir="ltr">Seller name</span>
                </div>
                <div class="invoice-field__value">{{ $supplierName ?: '—' }}</div>
            </div>
            <div class="invoice-field">
                <div class="invoice-field__label">
                    <span>الرقم الضريبي</span><span dir="ltr">VAT number</span>
                </div>
                <div class="invoice-field__value">{{ $supplierVat ?: '—' }}</div>
            </div>
            <div class="invoice-field">
                <div class="invoice-field__label">
                    <span>رقم السجل التجاري</span><span dir="ltr">Commercial registration</span>
                </div>
                <div class="invoice-field__value">{{ $supplierCrn ?: '—' }}</div>
            </div>
            <div class="invoice-field invoice-field--wide">
                <div class="invoice-field__label">
                    <span>العنوان</span><span dir="ltr">Address</span>
                </div>
                <div class="invoice-field__value">{{ $supplierAddress ?: '—' }}</div>
            </div>
        </div>
    </section>

    <section class="invoice-section">
        <h2 class="invoice-section__title">
            <span>هوية العميل</span>
            <span dir="ltr">Customer Identification</span>
        </h2>
        <div class="invoice-fields">
            <div class="invoice-field invoice-field--wide">
                <div class="invoice-field__label">
                    <span>اسم العميل</span><span dir="ltr">Customer name</span>
                </div>
                <div class="invoice-field__value">{{ $obj->client?->name ?: '—' }}</div>
            </div>
            <div class="invoice-field">
                <div class="invoice-field__label">
                    <span>رقم العميل</span><span dir="ltr">Customer number</span>
                </div>
                <div class="invoice-field__value">{{ $obj->client?->code ?: '—' }}</div>
            </div>
            <div class="invoice-field">
                <div class="invoice-field__label">
                    <span>الرقم الضريبي</span><span dir="ltr">VAT number</span>
                </div>
                <div class="invoice-field__value">{{ $obj->client?->tax_number ?: '—' }}</div>
            </div>
            <div class="invoice-field invoice-field--wide">
                <div class="invoice-field__label">
                    <span>العنوان</span><span dir="ltr">Address</span>
                </div>
                <div class="invoice-field__value">{{ $clientAddress ?: '—' }}</div>
            </div>
        </div>
    </section>

    <section class="invoice-lines">
        <div class="invoice-lines__title">
            <span>معلومات وبيانات السلعة أو الخدمة</span>
            <span dir="ltr">Line items</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="description">وصف السلعة أو الخدمة<br><span dir="ltr">Description</span></th>
                    <th>سعر الوحدة<br><span dir="ltr">Unit price</span></th>
                    <th>الكمية<br><span dir="ltr">Quantity</span></th>
                    <th>الخصم<br><span dir="ltr">Discount</span></th>
                    <th>نسبة الضريبة<br><span dir="ltr">VAT rate</span></th>
                    <th>مبلغ الضريبة<br><span dir="ltr">VAT amount</span></th>
                    <th>الإجمالي قبل الضريبة<br><span dir="ltr">Exclusive of VAT</span></th>
                    <th>الإجمالي شامل الضريبة<br><span dir="ltr">Inclusive of VAT</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoiceLines as $line)
                    <tr>
                        <td class="description">
                            {{ $line['description'] }}
                            <small class="d-block text-muted">#{{ $line['code'] }}</small>
                        </td>
                        <td>{{ number_format($line['unit_price'], 2) }}</td>
                        <td>{{ number_format($line['quantity'], 2) }}</td>
                        <td>{{ number_format($line['discount'], 2) }}</td>
                        <td>{{ number_format($line['tax_rate'], 2) }}%</td>
                        <td>{{ number_format($line['tax_amount'], 2) }}</td>
                        <td>{{ number_format($line['exclusive_amount'], 2) }}</td>
                        <td>{{ number_format($line['inclusive_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">لا توجد بنود مسجلة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="invoice-summary-wrap">
        <table class="invoice-summary-table">
            <tbody>
                <tr>
                    <th>الإجمالي قبل الخصم<br><span dir="ltr">Subtotal before discount</span></th>
                    <td>SAR {{ number_format($invoiceTotals['subtotal'], 2) }}</td>
                </tr>
                <tr>
                    <th>الخصم على مستوى الفاتورة<br><span dir="ltr">Invoice discount</span></th>
                    <td>SAR {{ number_format($invoiceTotals['discount'], 2) }}</td>
                </tr>
                <tr>
                    <th>المبلغ غير شامل ضريبة القيمة المضافة<br><span dir="ltr">Tax exclusive amount</span></th>
                    <td>SAR {{ number_format($invoiceTotals['tax_exclusive'], 2) }}</td>
                </tr>
                <tr>
                    <th>إجمالي ضريبة القيمة المضافة<br><span dir="ltr">VAT total amount</span></th>
                    <td>SAR {{ number_format($invoiceTotals['tax'], 2) }}</td>
                </tr>
                <tr>
                    <th>إجمالي المبلغ المستحق<br><span dir="ltr">Total payable amount</span></th>
                    <td>SAR {{ number_format($invoiceTotals['payable'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($obj->notes && $obj->notes !== '-')
        <div class="invoice-document__notes">
            <strong>ملاحظات / Notes</strong>
            <div>{{ $obj->notes }}</div>
        </div>
    @endif

    <footer class="invoice-document__footer">
        <span>UUID: {{ $obj->zatca?->uuid ?: '—' }}</span>
        <span>تم إنشاء المستند من النظام بتاريخ {{ now()->format('Y-m-d H:i') }}</span>
    </footer>
</article>
