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
                            <a href="{{ route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]) }}" class="btn btn-light border"
                                target="_blank" title="طباعة الفاتورة">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @if ($invoice->musanedTransferItem && $invoice->musanedTransferItem->transfer)
                                <a href="{{ route('musaned-transfers.show', $invoice->musanedTransferItem->transfer->id) }}"
                                    class="btn btn-light border" title="عرض تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @elseif ($invoice->contract_id)
                                <a href="{{ route('musaned-transfers.create', ['contract_ids' => [$invoice->contract_id]]) }}"
                                    class="btn btn-light border" title="تسجيل تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @elseif ($invoice->ingaz_delegation_id)
                                <a href="{{ route('musaned-transfers.create', ['ingaz_delegation_ids' => [$invoice->ingaz_delegation_id]]) }}"
                                    class="btn btn-light border" title="تسجيل تحويل مساند">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </a>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="invoice-title">فاتورة إلكترونية</div>
                            <div class="invoice-meta">
                                {{ $invoice->contract_id ? 'رقم العقد: ' . $invoice->contract_id : 'رقم التفويض: ' . $invoice->ingaz_delegation_id }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-6 gap-4">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>{{ $invoice->contract_id ? 'رقم العقد' : 'رقم التفويض' }}</strong>
                                <span>{{ $invoice->contract_id ?: $invoice->ingaz_delegation_id }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>حالة الفاتورة</strong>
                                <span>{{ $invoiceStatus }}</span>
                            </div>
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
                                <span>{{ $invoice->contract?->user?->name ?? $invoice->ingazDelegation?->user?->name ?? '---' }}</span>
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
                        <a href="{{ $invoice->ingaz_delegation_id ? route('ingaz-delegations.index') : route('RecruitmentContract.index') }}" class="btn btn-outline-secondary">إغلاق</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
