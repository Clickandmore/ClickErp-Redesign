@extends('admin.layout.indexs.index')

@section('page-title')
    جميع الفواتير
@endsection

@section('style')
    <style>
        .invoice-index-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .invoice-index-title {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
        }

        .invoice-index-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .invoice-index-actions .btn {
            font-weight: 700;
        }

        #dataTableObject {
            vertical-align: middle;
        }

        #dataTableObject th {
            white-space: nowrap;
            font-size: 12px;
        }

        #dataTableObject td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3 py-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="invoice-index-toolbar">
                    <div>
                        <h4 class="invoice-index-title">جميع الفواتير</h4>
                        <small class="text-muted">متابعة فواتير إنجاز وحالة الإدراج في فواتير هيئة الزكاة.</small>
                    </div>
                    <div class="invoice-index-actions">
                        @if (AdminGuardHasAnyPerm([\App\PermKeys::INVOICES_EXPORT, \App\PermKeys::INVOICES_CONTROL, \App\PermKeys::REC_CONTRACTS_EXPORT, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                            <a href="{{ route('invoices.export.excel') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </a>
                            <a href="{{ route('invoices.export.pdf') }}" class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                        @endif
                        @if (AdminGuardHasAnyPerm([\App\PermKeys::INVOICES_CREATE, \App\PermKeys::INVOICES_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> إضافة فاتورة
                            </a>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-clean" id="dataTableObject">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>مرتبطة بـ</th>
                                <th>اسم العميل</th>
                                <th>الإجمالي</th>
                                <th>الضريبة</th>
                                <th>الخصم</th>
                                <th>المطلوب</th>
                                <th>الحالة</th>
                                <th>هيئة الزكاة</th>
                                <th>تاريخ الإنشاء</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(function() {
            $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('invoices.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'invoice_number',
                        name: 'id'
                    },
                    {
                        data: 'invoice_source',
                        name: 'contract_id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'client_name',
                        name: 'client_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'subtotal',
                        name: 'subtotal'
                    },
                    {
                        data: 'vat_amount',
                        name: 'vat_amount'
                    },
                    {
                        data: 'discount',
                        name: 'discount'
                    },
                    {
                        data: 'total_due',
                        name: 'total_due'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'zatca_status',
                        name: 'zatca_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            $(document).on('submit', '.queue-zatca-form', function(e) {
                if (!confirm('سيتم إنشاء فاتورة مبيعات مالية مرتبطة بهذه الفاتورة لتظهر معلقة في هيئة الزكاة. هل تريد المتابعة؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
