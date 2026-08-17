@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .finance-clients-shell {
            --fc-primary: #315a63;
            --fc-accent: #e7ca08;
            --fc-border: #dce7ea;
            --fc-muted: #6b7280;
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .finance-clients-hero,
        .finance-clients-filters,
        .finance-clients-table-card {
            border: 1px solid var(--fc-border);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(49, 90, 99, .08);
        }

        .finance-clients-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.3rem 1.4rem;
            background:
                radial-gradient(circle at top right, rgba(231, 202, 8, .18), transparent 35%),
                linear-gradient(135deg, rgba(76, 129, 139, .1), #fff);
        }

        .finance-clients-hero h2 {
            margin: 0;
            color: var(--fc-primary);
            font-size: 1.5rem;
            font-weight: 800;
        }

        .finance-clients-hero p {
            margin: .45rem 0 0;
            color: var(--fc-muted);
        }

        .finance-clients-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .85rem;
        }

        .finance-summary-card {
            padding: 1rem 1.1rem;
            border: 1px solid var(--fc-border);
            border-radius: 17px;
            background: #fff;
        }

        .finance-summary-card span,
        .finance-summary-card small {
            display: block;
            color: var(--fc-muted);
        }

        .finance-summary-card strong {
            display: block;
            margin: .3rem 0;
            color: var(--fc-primary);
            font-size: 1.35rem;
        }

        .finance-summary-card.is-debtor strong { color: #b45309; }
        .finance-summary-card.is-creditor strong { color: #047857; }
        .finance-summary-card.is-warning strong { color: #b91c1c; }

        .finance-clients-filters {
            padding: 1rem 1.1rem;
        }

        .finance-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
            align-items: end;
        }

        .finance-filter-grid label {
            display: block;
            margin-bottom: .4rem;
            color: var(--fc-primary);
            font-weight: 700;
        }

        .finance-filter-grid .form-control {
            min-height: 44px;
            border-radius: 12px;
            border-color: var(--fc-border);
        }

        .finance-clients-table-card {
            padding: 1rem;
        }

        .finance-client-name,
        .finance-account-link,
        .finance-movement,
        .finance-created-at,
        .finance-document {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .finance-client-name strong,
        .finance-account-link strong,
        .finance-movement strong,
        .finance-created-at strong {
            color: #1f2937;
        }

        .finance-client-name span,
        .finance-account-link span,
        .finance-movement span,
        .finance-created-at span,
        .finance-document small {
            color: var(--fc-muted);
            font-size: .78rem;
        }

        .finance-account-link {
            text-decoration: none;
        }

        .finance-balance {
            display: inline-flex;
            flex-direction: column;
            min-width: 90px;
        }

        .finance-balance strong { font-size: .95rem; }
        .finance-balance small { font-size: .72rem; }
        .finance-balance.is-debtor strong,
        .finance-balance.is-debtor small { color: #b45309; }
        .finance-balance.is-creditor strong,
        .finance-balance.is-creditor small { color: #047857; }
        .finance-balance.is-balanced strong,
        .finance-balance.is-balanced small { color: #6b7280; }

        #dataTableObject td {
            vertical-align: middle;
            white-space: nowrap;
        }

        #dataTableObject td:nth-child(2),
        #dataTableObject td:nth-child(3) {
            white-space: normal;
            min-width: 170px;
        }

        @media (max-width: 1199px) {
            .finance-clients-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .finance-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .finance-clients-hero { align-items: stretch; flex-direction: column; }
            .finance-clients-summary,
            .finance-filter-grid { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    @php
        $netBalance = (float) ($summary['net_balance'] ?? 0);
        $netDirection = abs($netBalance) < .01 ? 'متوازن' : ($netBalance > 0 ? 'مدين' : 'دائن');
    @endphp

    <div class="finance-clients-shell">
        <section class="finance-clients-hero">
            <div>
                <h2><i class="fa fa-users me-2"></i>المركز المالي للعملاء</h2>
                <p>بيانات العملاء الأساسية مرتبطة بحساباتهم في الشجرة المالية، مع الرصيد والحركات وكشف الحساب.</p>
            </div>

            @if (($summary['unlinked_count'] ?? 0) > 0)
                <button type="button" class="btn btn-warning" id="make-client-accounts">
                    <i class="fa fa-link me-1"></i>
                    ربط {{ number_format($summary['unlinked_count']) }} عميلًا غير مرتبط
                </button>
            @endif
        </section>

        <section class="finance-clients-summary">
            <article class="finance-summary-card">
                <span>إجمالي العملاء</span>
                <strong>{{ number_format($summary['clients_count'] ?? 0) }}</strong>
                <small>من موديل العملاء الأساسي</small>
            </article>
            <article class="finance-summary-card">
                <span>مرتبطون بالشجرة</span>
                <strong>{{ number_format($summary['linked_count'] ?? 0) }}</strong>
                <small>لديهم حساب مالي صالح</small>
            </article>
            <article class="finance-summary-card is-warning">
                <span>غير مرتبطين</span>
                <strong>{{ number_format($summary['unlinked_count'] ?? 0) }}</strong>
                <small>يحتاجون إنشاء حساب</small>
            </article>
            <article class="finance-summary-card is-debtor">
                <span>إجمالي المدين</span>
                <strong>{{ number_format($summary['total_debtor'] ?? 0, 2) }}</strong>
                <small>جميع القيود المرحلة</small>
            </article>
            <article class="finance-summary-card is-creditor">
                <span>إجمالي الدائن</span>
                <strong>{{ number_format($summary['total_creditor'] ?? 0, 2) }}</strong>
                <small>جميع القيود المرحلة</small>
            </article>
            <article class="finance-summary-card {{ $netBalance > 0 ? 'is-debtor' : ($netBalance < 0 ? 'is-creditor' : '') }}">
                <span>صافي الأرصدة</span>
                <strong>{{ number_format(abs($netBalance), 2) }}</strong>
                <small>{{ $netDirection }}</small>
            </article>
        </section>

        <section class="finance-clients-filters">
            <div class="finance-filter-grid">
                <div>
                    <label for="category_id">تصنيف العميل</label>
                    <select id="category_id" class="form-control select2 finance-filter">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="account_link">الربط المالي</label>
                    <select id="account_link" class="form-control finance-filter">
                        <option value="">الكل</option>
                        <option value="linked">مرتبط بالشجرة</option>
                        <option value="unlinked">غير مرتبط</option>
                    </select>
                </div>
                <div>
                    <label for="balance_state">حالة الرصيد</label>
                    <select id="balance_state" class="form-control finance-filter">
                        <option value="">كل الأرصدة</option>
                        <option value="debtor">مدين</option>
                        <option value="creditor">دائن</option>
                        <option value="balanced">متوازن</option>
                    </select>
                </div>
                <div>
                    <label for="client_status">حالة العميل</label>
                    <select id="client_status" class="form-control finance-filter">
                        <option value="">الكل</option>
                        <option value="active">نشط</option>
                        <option value="blocked">موقوف</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="finance-clients-table-card">
            <div class="table-responsive">
                <table id="dataTableObject" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>رقم العميل</th>
                            <th>العميل</th>
                            <th>التواصل</th>
                            <th>المستند</th>
                            <th>الحساب المالي</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>الرصيد الحالي</th>
                            <th>الحركات</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(function () {
            $('.select2').select2({ width: '100%' });

            const money = new Intl.NumberFormat('ar-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const table = $('#dataTableObject').DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                ordering: true,
                searching: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'الكل']],
                ajax: {
                    url: @json($dataTableRoute),
                    data: function (request) {
                        request.category_id = $('#category_id').val();
                        request.account_link = $('#account_link').val();
                        request.balance_state = $('#balance_state').val();
                        request.client_status = $('#client_status').val();
                    }
                },
                columns: [
                    { data: 'id', name: 'id', orderable: true, searchable: true },
                    { data: 'client', name: 'name' },
                    { data: 'contact', name: 'phone', orderable: false },
                    { data: 'document', name: 'national_num', orderable: false },
                    { data: 'account', name: 'account_code' },
                    { data: 'total_debtor', name: 'total_debtor', className: 'text-end' },
                    { data: 'total_creditor', name: 'total_creditor', className: 'text-end' },
                    {
                        data: 'current_balance',
                        name: 'current_balance',
                        className: 'text-end',
                        render: function (value, type) {
                            const balance = Number(value || 0);
                            if (type !== 'display') {
                                return balance;
                            }

                            const state = Math.abs(balance) < .01
                                ? ['is-balanced', 'متوازن']
                                : (balance > 0 ? ['is-debtor', 'مدين'] : ['is-creditor', 'دائن']);

                            return '<span class="finance-balance ' + state[0] + '">' +
                                '<strong>' + money.format(Math.abs(balance)) + '</strong>' +
                                '<small>' + state[1] + '</small></span>';
                        }
                    },
                    { data: 'movement', name: 'last_movement_at' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'status', name: 'is_blocked' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [],
                language: {
                    sProcessing: @json(trans('dataTable.sProcessing')),
                    sLengthMenu: @json(trans('dataTable.sLengthMenu')),
                    sZeroRecords: @json(trans('dataTable.sZeroRecords')),
                    sInfo: @json(trans('dataTable.sInfo')),
                    sInfoEmpty: @json(trans('dataTable.sInfoEmpty')),
                    sInfoFiltered: @json(trans('dataTable.sInfoFiltered')),
                    sSearch: @json(trans('dataTable.sSearch')) + ':',
                    oPaginate: {
                        sFirst: @json(trans('dataTable.sFirst')),
                        sPrevious: @json(trans('dataTable.sPrevious')),
                        sNext: @json(trans('dataTable.sNext')),
                        sLast: @json(trans('dataTable.sLast'))
                    }
                }
            });

            $('.finance-filter').on('change', function () {
                table.ajax.reload();
            });

            $('#make-client-accounts').on('click', function () {
                const button = $(this);
                if (!window.confirm('سيتم إنشاء حساب مالي تحت حساب العملاء لكل عميل غير مرتبط. هل تريد المتابعة؟')) {
                    return;
                }

                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> جارٍ الربط...');

                $.ajax({
                    url: @json($makeAccountsRoute),
                    type: 'POST',
                    data: { _token: @json(csrf_token()) },
                    success: function () {
                        window.location.reload();
                    },
                    error: function (xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'تعذر إنشاء حسابات العملاء.';
                        alert(message);
                        button.prop('disabled', false).html('<i class="fa fa-link me-1"></i> إعادة محاولة الربط');
                    }
                });
            });
        });
    </script>
@endsection
