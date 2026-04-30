@php
    $rows = collect($rows ?? []);
    $totalWithoutTax = $rows->sum('total_without_tax');
    $totalVat = $rows->sum('vat_amount');
    $totalWithTax = $rows->sum('total_with_tax');
    $generatedAt = now(config('app.timezone', 'Asia/Riyadh'));
    $periodFrom = data_get($filters ?? [], 'from_date');
    $periodTo = data_get($filters ?? [], 'to_date');
    $periodLabel = 'كل الفترات';

    if ($periodFrom && $periodTo) {
        $periodLabel = 'من ' . \Carbon\Carbon::parse($periodFrom)->format('d/m/Y') . ' إلى ' . \Carbon\Carbon::parse($periodTo)->format('d/m/Y');
    } elseif ($periodFrom) {
        $periodLabel = 'من ' . \Carbon\Carbon::parse($periodFrom)->format('d/m/Y');
    } elseif ($periodTo) {
        $periodLabel = 'حتى ' . \Carbon\Carbon::parse($periodTo)->format('d/m/Y');
    }
@endphp

<style>
    .tax-report-card {
        background: #fff;
        border: 1px solid #d7e3e7;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(49, 90, 99, 0.07);
    }

    .tax-report-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        border-bottom: 1px solid #d7e3e7;
        background: #f6fbfc;
    }

    .tax-report-head h2 {
        margin: 0;
        color: #315a63;
        font-size: 1.3rem;
        font-weight: 800;
    }

    .tax-report-head p {
        margin: 0.35rem 0 0;
        color: #6b7280;
    }

    .tax-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 0.75rem;
        padding: 1rem;
    }

    .tax-summary-card {
        border: 1px solid rgba(76, 129, 139, 0.16);
        border-radius: 8px;
        background: #fff;
        padding: 0.85rem;
    }

    .tax-summary-card span {
        display: block;
        color: #6b7280;
        font-size: 0.86rem;
    }

    .tax-summary-card strong {
        display: block;
        margin-top: 0.35rem;
        color: #315a63;
        font-size: 1.1rem;
    }

    .tax-invoice-table {
        margin: 0;
        min-width: 1280px;
    }

    .tax-invoice-table thead th {
        background: #315a63;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
    }

    .tax-invoice-table tbody td,
    .tax-invoice-table tfoot td {
        text-align: center;
        vertical-align: middle;
    }

    .tax-invoice-table tfoot td {
        background: #f6fbfc;
        font-weight: 800;
    }

    .tax-empty-state {
        padding: 2rem;
        text-align: center;
        color: #6b7280;
        background: #fff;
        border: 1px dashed #b9cbd1;
        border-radius: 8px;
    }
</style>

@if ($rows->isNotEmpty())
    <div id="print_section" class="tax-report-card">
        <div class="tax-report-head">
            <div>
                <h2>{{ __('finance::finance.tax-invoice-statement') }}</h2>
                <p>{{ $periodLabel }}</p>
            </div>
            <div class="text-left">
                <strong>{{ $supplierName }}</strong>
                <p>الرقم الضريبي: {{ $supplierTaxNumber }}</p>
                <p>تاريخ الإصدار: {{ $generatedAt->format('Y-m-d H:i') }}</p>
            </div>
        </div>

        <div class="tax-summary-grid">
            <div class="tax-summary-card">
                <span>عدد الفواتير</span>
                <strong>{{ $rows->count() }}</strong>
            </div>
            <div class="tax-summary-card">
                <span>مجموع المبالغ دون ضريبة</span>
                <strong>{{ number_format($totalWithoutTax, 2) }}</strong>
            </div>
            <div class="tax-summary-card">
                <span>مجموع ضريبة القيمة المضافة</span>
                <strong>{{ number_format($totalVat, 2) }}</strong>
            </div>
            <div class="tax-summary-card">
                <span>المبلغ شامل الضريبة</span>
                <strong>{{ number_format($totalWithTax, 2) }}</strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped tax-invoice-table" id="taxInvoiceTable">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>المصدر</th>
                        <th>النوع</th>
                        <th>بائع</th>
                        <th>رقم ضريبي للبائع</th>
                        <th>المبلغ شامل الضريبة</th>
                        <th>مجموع المبالغ دون ضريبة</th>
                        <th>مجموع ضريبة القيمة المضافة</th>
                        <th>اسم العميل</th>
                        <th>جوال العميل</th>
                        <th>رقم العميل / العقد</th>
                        <th>نوع مستند الفاتورة</th>
                        <th>حالة Zatca</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['invoice_number'] }}</td>
                            <td>{{ $row['date'] ?: '-' }}</td>
                            <td>{{ $row['source'] }}</td>
                            <td>{{ $row['type'] }}</td>
                            <td>{{ $row['seller'] }}</td>
                            <td>{{ $row['seller_tax_number'] }}</td>
                            <td>{{ number_format($row['total_with_tax'], 2) }}</td>
                            <td>{{ number_format($row['total_without_tax'], 2) }}</td>
                            <td>{{ number_format($row['vat_amount'], 2) }}</td>
                            <td>{{ $row['client_name'] }}</td>
                            <td>{{ $row['client_phone'] }}</td>
                            <td>{{ $row['reference_number'] ?: '-' }}</td>
                            <td>{{ $row['document_type'] }}</td>
                            <td>{{ $row['zatca_status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">الإجمالي</td>
                        <td>{{ number_format($totalWithTax, 2) }}</td>
                        <td>{{ number_format($totalWithoutTax, 2) }}</td>
                        <td>{{ number_format($totalVat, 2) }}</td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@else
    <div class="tax-empty-state">
        لا توجد فواتير ضريبية مطابقة للفلاتر الحالية.
    </div>
@endif
