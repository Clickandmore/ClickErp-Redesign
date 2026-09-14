@extends('admin.layout.indexs.index')

@section('page-title')
    فاتورة إلكترونية
@endsection

@section('style')
    <style>
        .invoice-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .invoice-title {
            font-weight: 700;
            font-size: 20px;
        }

        .invoice-meta {
            color: #6c757d;
            font-size: 14px;
        }

        .invoice-table thead th {
            background: #f3f4f6;
        }

        .invoice-summary .label {
            color: #6c757d;
            font-size: 14px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        th{
            padding: 15px !important;
        }

        .table-responsive{
            margin-block: 10px !important;
        }
    </style>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice-header mb-4">
                        <div class="d-flex align-items-center gap-2 no-print">
                            @if (\App\Support\InvoiceAuthorization::canPrint())
                            <a href="{{ route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]) }}" class="btn btn-light border"
                                target="_blank" title="طباعة الفاتورة">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @endif
                            @if ($invoice->legacy_source_file && \App\Support\InvoiceAuthorization::canPrint())
                                <a href="{{ route('invoices.legacy-source-file', $invoice->id) }}" class="btn btn-light border"
                                    target="_blank" title="عرض ملف PDF الأصلي">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            @endif
                            @if (($invoice->contract_id || $invoice->ingaz_delegation_id || ($invoice->client_id && !$invoice->sponsorship_transfer_id)) && !$invoice->legacy_invoice_number && \App\Support\InvoiceAuthorization::canCreateCreditNotes())
                                <a href="{{ route('invoices.credit-notes.create', $invoice->id) }}"
                                   class="btn btn-light border text-danger" title="إنشاء إشعار دائن">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </a>
                            @endif
                            @if ($invoice->musanedTransferItem && $invoice->musanedTransferItem->transfer && AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_VIEW, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_VIEW, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                                <a href="{{ route('musaned-transfers.show', $invoice->musanedTransferItem->transfer->id) }}"
                                    class="btn btn-light border" title="عرض تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @elseif ($invoice->contract_id && AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                                <a href="{{ route('musaned-transfers.create', ['contract_ids' => [$invoice->contract_id]]) }}"
                                    class="btn btn-light border" title="تسجيل تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @elseif ($invoice->ingaz_delegation_id && AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                                <a href="{{ route('musaned-transfers.create', ['ingaz_delegation_ids' => [$invoice->ingaz_delegation_id]]) }}"
                                    class="btn btn-light border" title="تسجيل تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="invoice-title">فاتورة إلكترونية</div>
                            <div class="invoice-meta">
                                {{ $invoice->sponsorship_transfer_id ? 'طلب نقل الخدمات رقم: ' . $invoice->sponsorship_transfer_id : ($invoice->contract_id ? 'رقم العقد: ' . $invoice->contract_id : ($invoice->ingaz_delegation_id ? 'رقم التفويض: ' . $invoice->ingaz_delegation_id : 'فاتورة عامة')) }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-6 gap-4">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>{{ $invoice->sponsorship_transfer_id ? 'رقم طلب نقل الخدمات' : ($invoice->contract_id ? 'رقم العقد' : ($invoice->ingaz_delegation_id ? 'رقم التفويض' : 'نوع الفاتورة')) }}</strong>
                                <span>{{ $invoice->sponsorship_transfer_id ?: ($invoice->contract_id ?: ($invoice->ingaz_delegation_id ?: 'فاتورة عامة')) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>حالة السداد</strong>
                                <span>
                                    {{ $invoiceStatus }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>ارتباط هيئة الزكاة</strong>
                                <span>
                                    @if ($invoice->legacy_zatca_shared)
                                        مشارك سابقاً مع الهيئة (حسب ملف الرافدين)
                                    @elseif ($invoice->legacy_invoice_number && $invoice->legacy_zatca_shared === false)
                                        غير مشارك في ملف الرافدين
                                    @elseif (!$invoice->send_to_zatca)
                                        غير مطلوب
                                    @elseif (!$invoice->finance_sale_id)
                                        لم يتم إدراجها في فواتير المبيعات
                                    @elseif ($invoice->financeSale?->zatca?->zatca_status)
                                        {{ $invoice->financeSale->zatca->zatca_status }}
                                    @else
                                        معلقة في فواتير المبيعات #{{ $invoice->finance_sale_id }}
                                    @endif
                                </span>
                            </div>
                            @if ($invoice->legacy_invoice_number)
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>نوع الفاتورة الأصلي</strong>
                                    <span>{{ $invoice->legacy_invoice_type ?: '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>نوع المستند الأصلي</strong>
                                    <span>{{ $invoice->legacy_document_type ?: '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>رقم العميل / الطلب القديم</strong>
                                    <span>{{ $invoice->legacy_client_number ?: '-' }} / {{ $invoice->legacy_order_number ?: '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>مصدر الاستيراد</strong>
                                    <span>{{ $invoice->legacy_source_sheet ?: '-' }}، صف {{ $invoice->legacy_source_row ?: '-' }}</span>
                                </div>
                            @endif
                            @if ($invoice->invoice_constraint_id)
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>القيد المالي للفاتورة</strong>
                                    <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $invoice->invoice_constraint_id]) }}">
                                        #{{ $invoice->invoice_constraint_id }}
                                    </a>
                                </div>
                            @endif
                            @if ($invoice->finance_sale_id)
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>فاتورة المبيعات المرتبطة</strong>
                                    <a href="{{ route('finance.bill-sales.show', $invoice->finance_sale_id) }}">
                                        #{{ $invoice->finance_sale_id }}
                                    </a>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between mb-2">
                                <strong>وصف الخدمة</strong>
                                <span>{{ $serviceDesc }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>اسم مقدم الخدمة</strong>
                                <span>{{ $supplierName }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>عنوان مقدم الخدمة</strong>
                                <span>{{ $supplierAddress }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>اسم صاحب العمل</strong>
                                <span>{{ $invoice->contract?->user?->name ?? $invoice->ingazDelegation?->user?->name ?? $invoice->client?->name ?? '---' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>تاريخ الدفع</strong>
                                <span>{{ optional($invoice->created_at)->format('Y-m-d H:i') ?? '---' }}</span>
                            </div>
                        </div>
                    </div>


                    <div class="table-responsive my-3">
                        <table class="table table-bordered align-middle invoice-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">وصف الخدمة</th>
                                    <th style="width: 10%;">الكمية</th>
                                    <th style="width: 15%;">القيمة</th>
                                    <th style="width: 15%;">نسبة الضريبة</th>
                                    <th style="width: 15%;">قيمة الضريبة</th>
                                    <th style="width: 15%;">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $index => $item)
                                    <tr>
                                        <td>
                                            {{ $index + 1 }} - {{ $item['label'] }}
                                        </td>
                                        <td>{{ $item['qty'] }}</td>
                                        <td>{{ number_format($item['unit'], 2) }}</td>
                                        <td>{{ (int) ($item['vat_rate'] * 100) }}%</td>
                                        <td>{{ number_format($item['vat_amount'], 2) }}</td>
                                        <td>{{ number_format($item['line_grand_total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mt-3">
                        <div class="col-lg-4">
                            <div class="invoice-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="label">المجموع الفرعي</span>
                                    <strong>{{ number_format($invoice->subtotal, 2) }} ريال</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="label">مبلغ الضريبة (15%)</span>
                                    <strong>{{ number_format($invoice->vat_amount, 2) }} ريال</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="label">خصم</span>
                                    <strong>{{ number_format($invoice->discount, 2) }} ريال</strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="label">إجمالي السعر</span>
                                    <strong>{{ number_format($invoice->total_due, 2) }} ريال</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4 no-print">
                        @if (\App\Support\InvoiceAuthorization::canPrintCreditNotes())
                            @foreach ($invoice->recruitmentCreditNotes as $creditNote)
                                <a href="{{ route('recruitment-credit-notes.download', ['id' => $creditNote->id, 'autoprint' => 1]) }}"
                                   target="_blank" rel="noopener" class="btn btn-outline-danger">
                                    إشعار دائن #{{ $creditNote->id }}
                                </a>
                                @if ($creditNote->constraint_id)
                                    <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $creditNote->constraint_id]) }}"
                                       target="_blank" rel="noopener" class="btn btn-outline-secondary">
                                        قيد الإشعار #{{ $creditNote->id }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                        <a href="{{ $invoice->sponsorship_transfer_id ? route('SponsorshipTransfer.show', $invoice->sponsorship_transfer_id) : ($invoice->ingaz_delegation_id ? route('ingaz-delegations.index') : ($invoice->contract_id ? route('RecruitmentContract.index') : route('invoices.index'))) }}" class="btn btn-outline-secondary">إغلاق</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
