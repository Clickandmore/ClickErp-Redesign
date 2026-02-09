@extends('admin.layout.indexs.index')

@section('page-title')
    جميع الفواتير
@endsection

@section('content')
    <div class="container-fluid py-3 py-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h4 class="mb-0">جميع الفواتير</h4>
                    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> إضافة فاتورة
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-clean" id="dataTableObject">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>رقم العقد</th>
                                <th>اسم العميل</th>
                                <th>الإجمالي</th>
                                <th>الضريبة</th>
                                <th>الخصم</th>
                                <th>المطلوب</th>
                                <th>الحالة</th>
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
                        data: 'contract_number',
                        name: 'contract_id'
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
        });
    </script>
@endsection
