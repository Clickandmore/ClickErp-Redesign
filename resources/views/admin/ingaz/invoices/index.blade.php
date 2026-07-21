@extends('admin.layout.indexs.index')

@section('page-title')
    الفواتير والإشعارات المالية
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
                        <h4 class="invoice-index-title">الفواتير والإشعارات المالية</h4>
                        <small class="text-muted">
                            {{ ($legacyMode ?? false) ? 'يعرض فقط فواتير الرافدين الموجودة في ملف الاستيراد، مع البحث والطباعة حسب رقم الفاتورة.' : 'متابعة الفواتير الأساسية وإشعارات الدائن والمدين الناتجة عن إغلاق عقود التأجير.' }}
                        </small>
                    </div>
                    <div class="invoice-index-actions">
                        @if (AdminGuardHasAnyPerm([\App\PermKeys::INVOICES_EXPORT, \App\PermKeys::INVOICES_CONTROL, \App\PermKeys::REC_CONTRACTS_EXPORT, \App\PermKeys::REC_CONTRACTS_MANAGE]))
                            <a href="{{ route('invoices.export.excel') }}" class="btn btn-outline-success" id="invoiceExportExcel">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </a>
                            <a href="{{ route('invoices.export.pdf') }}" class="btn btn-outline-danger" id="invoiceExportPdf">
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

                    @if($legacyMode ?? false)
                    <div class="row g-3 align-items-end mb-3" id="invoiceFilters">
                        <div class="col-md-3 col-lg-2">
                            <label for="invoiceNumberFilter" class="form-label fw-bold">رقم الفاتورة</label>
                            <input type="number" min="1" id="invoiceNumberFilter" class="form-control invoice-filter-input" placeholder="مثال: 8231">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="invoiceTypeFilter" class="form-label">نوع الفاتورة</label>
                            <select id="invoiceTypeFilter" class="form-select invoice-filter">
                                <option value="">الكل</option>
                                @foreach(($invoiceTypes ?? collect()) as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="documentTypeFilter" class="form-label">نوع المستند</label>
                            <select id="documentTypeFilter" class="form-select invoice-filter">
                                <option value="">الكل</option>
                                @foreach(($documentTypes ?? collect()) as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="zatcaFilter" class="form-label">المشاركة مع الهيئة</label>
                            <select id="zatcaFilter" class="form-select invoice-filter">
                                <option value="">الكل</option>
                                <option value="1">مشارك سابقاً</option>
                                <option value="0">غير مشارك</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="dateFromFilter" class="form-label">من تاريخ</label>
                            <input type="date" id="dateFromFilter" class="form-control invoice-filter">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="dateToFilter" class="form-label">إلى تاريخ</label>
                            <input type="date" id="dateToFilter" class="form-control invoice-filter">
                        </div>
                        @if($branchesEnabled ?? false)
                        <div class="col-md-3">
                            <label for="branchFilter" class="form-label">{{ __('admin.branches') }}</label>
                            <select id="branchFilter" class="form-select invoice-filter">
                                <option value="">{{ helperTrans('ingaz.all') }}</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-secondary" id="clearInvoiceFilters">
                                <i class="fas fa-rotate-left me-1"></i> مسح الفلاتر
                            </button>
                        </div>
                    </div>
                    @endif

                <div class="table-responsive">
                    <table class="table table-clean" id="dataTableObject">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم المستند</th>
                                <th>مرتبطة بـ</th>
                                <th>اسم العميل</th>
                                @if($branchesEnabled ?? false)
                                    <th>{{ __('admin.branches') }}</th>
                                @endif
                                <th>الإجمالي</th>
                                <th>الضريبة</th>
                                <th>الخصم</th>
                                <th>المطلوب</th>
                                <th>حالة السداد / الرصيد</th>
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
            const table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                searchDelay: 400,
                pageLength: 25,
                lengthMenu: [[25, 50, 100], [25, 50, 100]],
                ajax: {
                    url: "{{ route('invoices.index') }}",
                    data: function(d) {
                        d.branch_id = $('#branchFilter').length ? $('#branchFilter').val() : '';
                        d.invoice_number = $('#invoiceNumberFilter').val();
                        d.invoice_type = $('#invoiceTypeFilter').val();
                        d.document_type = $('#documentTypeFilter').val();
                        d.zatca_shared = $('#zatcaFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'invoice_number',
                        name: "{{ ($legacyMode ?? false) ? 'legacy_invoice_number' : 'invoice_sort_number' }}"
                    },
                    {
                        data: 'invoice_source',
                        name: "{{ ($legacyMode ?? false) ? 'legacy_order_number' : 'contract_id' }}",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'client_name',
                        name: 'client_name',
                        orderable: false,
                        searchable: false
                    },
                    @if($branchesEnabled ?? false)
                    {
                        data: 'branch',
                        name: 'branch',
                        orderable: false,
                        searchable: false
                    },
                    @endif
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
                    [{{ ($branchesEnabled ?? false) ? 11 : 10 }}, 'desc']
                ],
                language: {
                    search: 'بحث سريع:',
                    searchPlaceholder: 'رقم الفاتورة، الطلب، العميل...'
                }
            });

            $('.invoice-filter').on('change', function() {
                table.ajax.reload();
            });

            let invoiceNumberTimer;
            $('#invoiceNumberFilter').on('input', function() {
                clearTimeout(invoiceNumberTimer);
                invoiceNumberTimer = setTimeout(() => table.ajax.reload(), 350);
            });

            $('#clearInvoiceFilters').on('click', function() {
                $('#invoiceFilters').find('select, input').val('');
                table.search('').ajax.reload();
            });

            $('#invoiceExportExcel, #invoiceExportPdf').on('click', function(e) {
                e.preventDefault();
                window.location.href = this.href + '?' + $.param({
                    branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                    invoice_number: $('#invoiceNumberFilter').val(),
                    invoice_type: $('#invoiceTypeFilter').val(),
                    document_type: $('#documentTypeFilter').val(),
                    zatca_shared: $('#zatcaFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val()
                });
            });

            $(document).on('submit', '.queue-zatca-form', function(e) {
                if (!confirm('سيتم إنشاء فاتورة مبيعات مالية مرتبطة بهذه الفاتورة لتظهر معلقة في هيئة الزكاة. هل تريد المتابعة؟')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection
