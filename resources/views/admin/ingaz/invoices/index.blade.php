@extends('admin.layout.indexs.index')

@section('page-title')
    الفواتير والإشعارات المالية
@endsection

@section('style')
    <style>
        .inv-page { --inv-primary: #4f46e5; --inv-soft: #eef2ff; --inv-border: #e5e7eb; --inv-muted: #6b7280; }

        .inv-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem; border-radius: 16px; background: linear-gradient(135deg, #312e81, #4f46e5 60%, #0d9488); color: #fff; }
        .inv-hero h4 { margin: 0 0 .25rem; font-weight: 700; color: #fff; }
        .inv-hero p { margin: 0; opacity: .85; font-size: .9rem; max-width: 720px; }
        .inv-hero-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .inv-hero-actions .btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: 10px; font-weight: 600; font-size: .88rem; padding: .5rem .95rem; }
        .inv-hero-actions .btn-light { color: #312e81; }
        .inv-hero-actions .btn-outline-light:hover { color: #312e81; }

        .inv-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .inv-stat { display: flex; align-items: center; gap: .75rem; padding: .9rem 1rem; border: 1px solid var(--inv-border); border-radius: 14px; background: #fff; }
        .inv-stat-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; }
        .inv-stat small { display: block; color: var(--inv-muted); font-size: .78rem; }
        .inv-stat b { display: block; font-size: 1.12rem; color: #111827; font-variant-numeric: tabular-nums; }
        .inv-stat b .unit { font-size: .72rem; font-weight: 500; color: var(--inv-muted); }
        .inv-stats[aria-busy="true"] b { opacity: .45; }

        .inv-box { border: 1px solid var(--inv-border); border-radius: 14px; background: #fff; }
        .inv-filter { padding: 1rem 1.1rem; margin-bottom: 1rem; }
        .inv-filter-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .9rem; }
        .inv-filter-head h6 { margin: 0; font-weight: 700; color: #1f2937; }
        .inv-filter-count { margin-inline-start: .4rem; padding: .15rem .55rem; border-radius: 99px; background: var(--inv-soft); color: var(--inv-primary); font-size: .72rem; font-weight: 600; }
        .inv-search { position: relative; flex: 1 1 320px; max-width: 480px; }
        .inv-search i { position: absolute; inset-inline-start: .8rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .inv-search .form-control { padding-inline-start: 2.2rem; }
        .inv-filter .form-label { font-size: .78rem; font-weight: 600; color: #4b5563; margin-bottom: .3rem; }
        .inv-filter .form-control, .inv-filter .form-select { border-radius: 10px; font-size: .88rem; min-height: 38px; }
        .inv-filter .is-set { border-color: var(--inv-primary); background-color: #fafaff; }
        .inv-filter-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: .9rem; padding-top: .85rem; border-top: 1px dashed var(--inv-border); }
        .inv-filter-foot .inv-preset { border-radius: 99px; font-size: .78rem; padding: .25rem .8rem; }
        .inv-filter-foot .inv-reset { margin-inline-start: auto; border-radius: 10px; font-size: .84rem; font-weight: 600; }

        #dataTableObject_filter { display: none; }
        .branch-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 99px; background: #f5f3ff; color: #6d28d9; border: 1px solid #ede9fe; font-size: .74rem; font-weight: 600; white-space: nowrap; }
        .branch-badge i { font-size: .68rem; }
        .branch-badge--general { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }
        #dataTableObject { min-width: 1150px; }
        #dataTableObject thead th { white-space: nowrap; background: #f9fafb; color: #4b5563; font-size: .78rem; font-weight: 600; border-bottom: 1px solid var(--inv-border); padding: .75rem; vertical-align: middle; }
        #dataTableObject td { vertical-align: middle; padding: .7rem .75rem; font-size: .86rem; border-color: #f3f4f6; font-variant-numeric: tabular-nums; }
        #dataTableObject tbody tr:hover td { background: #fafaff; }
        #dataTableObject .badge { padding: .35rem .65rem; border-radius: 99px; font-weight: 600; font-size: .74rem; white-space: nowrap; }

        /* أزرار الإجراءات: أيقونات مربعة موحدة */
        #dataTableObject td:last-child .d-flex { gap: 4px !important; flex-wrap: nowrap; }
        #dataTableObject td:last-child form { margin: 0; }
        #dataTableObject td:last-child .btn-sm { width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; border-radius: 9px; background: #fff; border: 1px solid var(--inv-border); font-size: .8rem; transition: background .15s, border-color .15s, transform .15s; }
        #dataTableObject td:last-child .btn-sm:hover { transform: translateY(-1px); }
        #dataTableObject td:last-child .btn-outline-primary { color: var(--inv-primary); }
        #dataTableObject td:last-child .btn-outline-primary:hover { background: var(--inv-soft); border-color: #c7d2fe; }
        #dataTableObject td:last-child .btn-outline-secondary { color: #4b5563; }
        #dataTableObject td:last-child .btn-outline-secondary:hover { background: #f3f4f6; border-color: #d1d5db; }
        #dataTableObject td:last-child .btn-outline-success { color: #047857; }
        #dataTableObject td:last-child .btn-outline-success:hover { background: #ecfdf5; border-color: #a7f3d0; }
        #dataTableObject td:last-child .btn-outline-danger { color: #dc2626; }
        #dataTableObject td:last-child .btn-outline-danger:hover { background: #fef2f2; border-color: #fecaca; }
        #dataTableObject td:last-child .btn-outline-dark { color: #111827; }
        #dataTableObject td:last-child .btn-outline-dark:hover { background: #f3f4f6; border-color: #9ca3af; }

        /* ترقيم DataTables */
        .inv-page .dataTables_wrapper .row:last-child { align-items: center; padding: .75rem 1rem; margin: 0; border-top: 1px solid var(--inv-border); background: #fcfcfd; border-radius: 0 0 14px 14px; }
        .inv-page .dataTables_wrapper .row:first-child { padding: .5rem 1rem; margin: 0; }
        .inv-page .dataTables_info { color: var(--inv-muted); font-size: .82rem; padding-top: 0 !important; }
        .inv-page .dataTables_length select { border-radius: 8px; }
        .inv-page .dataTables_paginate .pagination { gap: .3rem; margin: 0; flex-wrap: wrap; }
        .inv-page .dataTables_paginate .page-link { min-width: 34px; height: 34px; display: grid; place-items: center; padding: 0 .6rem; border-radius: 10px !important; border: 1px solid var(--inv-border); color: #374151; font-size: .84rem; font-weight: 600; }
        .inv-page .dataTables_paginate .page-link:hover { background: var(--inv-soft); border-color: #c7d2fe; color: var(--inv-primary); }
        .inv-page .dataTables_paginate .page-item.active .page-link { background: var(--inv-primary); border-color: var(--inv-primary); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, .3); }
        .inv-page .dataTables_paginate .page-item.disabled .page-link { background: #f9fafb; color: #d1d5db; }
        .inv-page .dataTables_processing { border-radius: 12px; border: 1px solid #e0defc; box-shadow: 0 12px 30px rgba(49, 46, 129, .15); color: #312e81; font-weight: 600; }

        @media (max-width: 768px) {
            .inv-hero-actions, .inv-hero-actions .btn { width: 100%; justify-content: center; }
            .inv-filter-foot .inv-reset { margin-inline-start: 0; }
        }
    </style>
@endsection

@section('content')
@php
    $today = today();
    $datePresets = [
        'اليوم' => [$today->toDateString(), $today->toDateString()],
        'آخر 7 أيام' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        'هذا الشهر' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        'الشهر الماضي' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'هذه السنة' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
    ];
@endphp
    <div class="container-fluid py-3 py-lg-4 inv-page" dir="rtl">
        <header class="inv-hero">
            <div>
                <h4><i class="fas fa-file-invoice-dollar me-2"></i>الفواتير والإشعارات المالية</h4>
                <p>
                    {{ ($legacyMode ?? false) ? 'يعرض فقط فواتير الرافدين الموجودة في ملف الاستيراد، مع البحث والطباعة حسب رقم الفاتورة.' : 'متابعة الفواتير وإشعارات دائن الاستقدام، إضافة إلى إشعارات الدائن والمدين لعقود التأجير.' }}
                </p>
            </div>
            <div class="inv-hero-actions">
                @if (\App\Support\InvoiceAuthorization::canExport())
                    <a href="{{ route('invoices.export.excel') }}" class="btn btn-outline-light" id="invoiceExportExcel" title="تصدير فواتير الاستقدام حسب الفلاتر">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('invoices.export.pdf') }}" class="btn btn-outline-light" id="invoiceExportPdf" title="تصدير فواتير الاستقدام حسب الفلاتر">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                @endif
                @if (\App\Support\InvoiceAuthorization::canViewDrafts())
                    <a href="{{ route('invoice-drafts.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-clipboard-check"></i> طلبات اعتماد الفواتير
                    </a>
                @endif
                @if (\App\Support\InvoiceAuthorization::canCreate())
                    <a href="{{ route('invoices.create') }}" class="btn btn-light">
                        <i class="fas fa-plus"></i> إضافة فاتورة
                    </a>
                @endif
            </div>
        </header>

        @unless ($legacyMode ?? false)
            <div class="inv-stats" id="invoiceStats">
                <div class="inv-stat">
                    <span class="inv-stat-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-layer-group"></i></span>
                    <div><small>عدد المستندات</small><b data-stat="documents_count">—</b></div>
                </div>
                <div class="inv-stat">
                    <span class="inv-stat-icon" style="background:#ecfdf5;color:#047857"><i class="fas fa-file-invoice"></i></span>
                    <div><small>إجمالي الفواتير</small><b><span data-stat="invoices_total">—</span> <span class="unit">ر.س</span></b></div>
                </div>
                <div class="inv-stat">
                    <span class="inv-stat-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-hourglass-half"></i></span>
                    <div><small>غير مدفوع</small><b><span data-stat="unpaid_total">—</span> <span class="unit">ر.س</span></b></div>
                </div>
                <div class="inv-stat">
                    <span class="inv-stat-icon" style="background:#f0f9ff;color:#0369a1"><i class="fas fa-percent"></i></span>
                    <div><small>ضريبة الفواتير</small><b><span data-stat="vat_total">—</span> <span class="unit">ر.س</span></b></div>
                </div>
                <div class="inv-stat">
                    <span class="inv-stat-icon" style="background:#fef2f2;color:#b91c1c"><i class="fas fa-rotate-left"></i></span>
                    <div><small>إشعارات دائن للعملاء</small><b><span data-stat="credit_total">—</span> <span class="unit">ر.س</span></b></div>
                </div>
            </div>
        @endunless

        <div class="inv-box inv-filter" id="invoiceFilters">
            <div class="inv-filter-head">
                <h6><i class="fas fa-sliders me-1"></i>فلترة المستندات <span class="inv-filter-count" id="invoiceFilterCount" hidden></span></h6>
                <div class="inv-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" id="invoiceSearch" class="form-control" placeholder="رقم الفاتورة، رقم العقد أو التفويض، اسم العميل أو جواله..." aria-label="بحث">
                </div>
            </div>

            <div class="row g-3">
                @if ($legacyMode ?? false)
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="invoiceNumberFilter" class="form-label">رقم الفاتورة</label>
                        <input type="number" min="1" id="invoiceNumberFilter" class="form-control" placeholder="مثال: 8231">
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="invoiceTypeFilter" class="form-label">نوع الفاتورة</label>
                        <select id="invoiceTypeFilter" class="form-select invoice-filter">
                            <option value="">الكل</option>
                            @foreach (($invoiceTypes ?? collect()) as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="documentTypeFilter" class="form-label">نوع المستند</label>
                        <select id="documentTypeFilter" class="form-select invoice-filter">
                            <option value="">الكل</option>
                            @foreach (($documentTypes ?? collect()) as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="zatcaSharedFilter" class="form-label">المشاركة مع الهيئة</label>
                        <select id="zatcaSharedFilter" class="form-select invoice-filter">
                            <option value="">الكل</option>
                            <option value="1">مشارك سابقاً</option>
                            <option value="0">غير مشارك</option>
                        </select>
                    </div>
                @else
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="docTypeFilter" class="form-label">نوع المستند</label>
                        <select id="docTypeFilter" class="form-select invoice-filter">
                            <option value="">كل المستندات</option>
                            <optgroup label="فواتير">
                                <option value="recruitment">فواتير عقود الاستقدام</option>
                                <option value="delegation">فواتير تفاويض الإنجاز</option>
                                <option value="transfer">فواتير نقل الخدمات</option>
                                <option value="general">فواتير عامة</option>
                                <option value="rental">فواتير التأجير</option>
                            </optgroup>
                            <optgroup label="إشعارات">
                                <option value="credit_note">إشعارات دائن الاستقدام</option>
                                <option value="rental_adjustment">إشعارات التأجير (دائن/مدين)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="paymentFilter" class="form-label">حالة السداد / الرصيد</label>
                        <select id="paymentFilter" class="form-select invoice-filter">
                            <option value="">الكل</option>
                            <option value="paid">مدفوعة</option>
                            <option value="unpaid">غير مدفوعة</option>
                            <option value="customer_credit">رصيد لصالح العميل</option>
                            <option value="customer_debit">مستحق على العميل</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="zatcaFilter" class="form-label">هيئة الزكاة</label>
                        <select id="zatcaFilter" class="form-select invoice-filter">
                            <option value="">الكل</option>
                            <option value="not_queued">لم يدرج / جاهزة للمشاركة</option>
                            <option value="pending">معلق في المبيعات</option>
                            <option value="reported">مرحل أو معتمد من الهيئة</option>
                            <option value="rejected">مرفوض من الهيئة</option>
                            <option value="not_required">غير مطلوب</option>
                        </select>
                    </div>
                @endif
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="dateFromFilter" class="form-label">من تاريخ</label>
                    <input type="date" id="dateFromFilter" class="form-control invoice-filter">
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <label for="dateToFilter" class="form-label">إلى تاريخ</label>
                    <input type="date" id="dateToFilter" class="form-control invoice-filter">
                </div>
                @if ($branchesEnabled ?? false)
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="branchFilter" class="form-label">{{ __('admin.branches') }}</label>
                        <select id="branchFilter" class="form-select invoice-filter">
                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                            @foreach (($branches ?? collect()) as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @unless ($legacyMode ?? false)
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="amountMinFilter" class="form-label">المطلوب من (ر.س)</label>
                        <input type="number" step="0.01" min="0" id="amountMinFilter" class="form-control invoice-amount" placeholder="0.00">
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label for="amountMaxFilter" class="form-label">المطلوب إلى (ر.س)</label>
                        <input type="number" step="0.01" min="0" id="amountMaxFilter" class="form-control invoice-amount" placeholder="0.00">
                    </div>
                @endunless
            </div>

            <div class="inv-filter-foot">
                <span class="text-muted small me-1"><i class="far fa-calendar me-1"></i>فترة سريعة:</span>
                @foreach ($datePresets as $label => [$from, $to])
                    <button type="button" class="btn btn-outline-primary inv-preset" data-from="{{ $from }}" data-to="{{ $to }}">{{ $label }}</button>
                @endforeach
                <button type="button" class="btn btn-light border inv-reset" id="clearInvoiceFilters">
                    <i class="fas fa-rotate-left me-1"></i> مسح الفلاتر
                </button>
            </div>
        </div>

        <div class="inv-box">
            <div class="table-responsive">
                <table class="table table-clean mb-0" id="dataTableObject">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم المستند</th>
                            <th>مرتبطة بـ</th>
                            <th>اسم العميل</th>
                            @if ($branchesEnabled ?? false)
                                <th>{{ __('admin.branches') }}</th>
                            @endif
                            <th>الإجمالي</th>
                            <th>الضريبة</th>
                            <th>الخصم</th>
                            <th>المطلوب</th>
                            <th>حالة السداد / الرصيد</th>
                            <th>هيئة الزكاة</th>
                            <th>تاريخ الإنشاء</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(function() {
            const val = selector => $(selector).length ? ($(selector).val() || '') : '';

            // مصدر واحد لقيم الفلاتر يستخدمه الجدول وأزرار التصدير معاً.
            function currentFilters() {
                return {
                    branch_id: val('#branchFilter'),
                    invoice_number: val('#invoiceNumberFilter'),
                    invoice_type: val('#invoiceTypeFilter'),
                    document_type: val('#documentTypeFilter'),
                    zatca_shared: val('#zatcaSharedFilter'),
                    doc_type: val('#docTypeFilter'),
                    payment: val('#paymentFilter'),
                    zatca: val('#zatcaFilter'),
                    date_from: val('#dateFromFilter'),
                    date_to: val('#dateToFilter'),
                    amount_min: val('#amountMinFilter'),
                    amount_max: val('#amountMaxFilter')
                };
            }

            function refreshFilterState() {
                const filters = currentFilters();
                let count = Object.values(filters).filter(value => value !== '').length;
                if ($('#invoiceSearch').val()) count++;
                $('#invoiceFilters').find('select, input').each(function() {
                    $(this).toggleClass('is-set', !!$(this).val() && this.id !== 'invoiceSearch');
                });
                $('#invoiceFilterCount').text(count + ' فلتر مفعّل').prop('hidden', count === 0);
                $('.inv-preset').each(function() {
                    const active = filters.date_from === this.dataset.from && filters.date_to === this.dataset.to;
                    $(this).toggleClass('btn-primary', active).toggleClass('btn-outline-primary', !active);
                });
            }

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
                        $.extend(d, currentFilters());
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
                    @if ($branchesEnabled ?? false)
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
                        name: 'total_due',
                        className: 'fw-bold'
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
                        name: 'created_at',
                        className: 'text-nowrap'
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
                ]
            });

            table.on('preXhr.dt', () => $('#invoiceStats').attr('aria-busy', 'true'));
            table.on('xhr.dt', function(e, settings, json) {
                $('#invoiceStats').attr('aria-busy', 'false');
                const summary = json && json.summary;
                if (!summary) return;
                Object.keys(summary).forEach(key => $('[data-stat="' + key + '"]').text(summary[key]));
            });

            const reload = () => { refreshFilterState(); table.ajax.reload(); };
            const debounce = (fn, wait) => { let timer; return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), wait); }; };

            $('.invoice-filter').on('change', reload);
            $('.invoice-amount, #invoiceNumberFilter').on('input', debounce(reload, 450));
            $('#invoiceSearch').on('input', debounce(function() {
                refreshFilterState();
                table.search($('#invoiceSearch').val()).draw();
            }, 400));

            $('.inv-preset').on('click', function() {
                $('#dateFromFilter').val(this.dataset.from);
                $('#dateToFilter').val(this.dataset.to);
                reload();
            });

            $('#clearInvoiceFilters').on('click', function() {
                $('#invoiceFilters').find('select, input').val('');
                refreshFilterState();
                table.search('').ajax.reload();
            });

            $('#invoiceExportExcel, #invoiceExportPdf').on('click', function(e) {
                e.preventDefault();
                const params = $.param(Object.fromEntries(Object.entries(currentFilters()).filter(([, value]) => value !== '')));
                window.location.href = this.href + (params ? '?' + params : '');
            });

            $(document).on('submit', '.queue-zatca-form', function(e) {
                if (!confirm('سيتم إنشاء فاتورة مبيعات مالية مرتبطة بهذه الفاتورة لتظهر معلقة في هيئة الزكاة. هل تريد المتابعة؟')) {
                    e.preventDefault();
                }
            });

            refreshFilterState();
        });
    </script>
@endsection
