@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stats-grid-caption {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stats-grid-caption p {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .statistics-card-link {
            display: block;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }

        .statistics-card-link:hover,
        .statistics-card-link:focus {
            color: inherit;
            text-decoration: none;
        }

        .statistics-card {
            --stat-accent: #0d6efd;
            --stat-soft: rgba(13, 110, 253, 0.12);
            border: 1px solid #e7edf5;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            border-inline-start: 4px solid var(--stat-accent);
            background: #fff;
        }

        .statistics-card .card-body {
            padding: 1.25rem;
            background: linear-gradient(180deg, #ffffff 0%, var(--stat-soft) 170%);
        }

        .statistics-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
        }

        .statistics-card.is-active {
            border-color: var(--stat-accent);
            box-shadow: 0 0 0 2px var(--stat-soft), 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        .statistics-card.stats-theme-primary {
            --stat-accent: #0d6efd;
            --stat-soft: rgba(13, 110, 253, 0.12);
        }

        .statistics-card.stats-theme-warning {
            --stat-accent: #f59e0b;
            --stat-soft: rgba(245, 158, 11, 0.14);
        }

        .statistics-card.stats-theme-info {
            --stat-accent: #0dcaf0;
            --stat-soft: rgba(13, 202, 240, 0.14);
        }

        .statistics-card.stats-theme-success {
            --stat-accent: #198754;
            --stat-soft: rgba(25, 135, 84, 0.12);
        }

        .statistics-card.stats-theme-danger {
            --stat-accent: #dc3545;
            --stat-soft: rgba(220, 53, 69, 0.12);
        }

        .stat-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.04em;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .stat-hint {
            color: #64748b;
            font-size: 0.85rem;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--stat-accent);
            background: var(--stat-soft);
            flex-shrink: 0;
        }

        .stat-arrow {
            color: var(--stat-accent);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .filter-card {
            border: 1px solid #e7edf5;
            border-radius: 18px;
            overflow: hidden;
        }

        .filter-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
        }

        @media (max-width: 576px) {
            .statistics-card .card-body {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
@endsection

@section('content')
@if(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_VIEW) || @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL))
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-exchange-alt text-primary me-2"></i>
                {{helperTrans("ingaz.sponsorship_transfer_requests")}}
            </h1>
            <div class="d-flex gap-2">
                @if(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW) || @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL))
                    <a href="{{ route('SponsorshipTransfer.webRequests') }}" class="btn btn-info">
                        <i class="fas fa-globe me-2"></i>
                        طلبات الموقع الخارجي
                    </a>
                @endif
                @if(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CREATE) || @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL))
                    <a href="{{ route('SponsorshipTransfer.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        {{helperTrans("ingaz.add_sponsorship_transfer_request")}}
                    </a>
                @endif
            </div>
        </div>

        @php
            $currentStatus = request('status', '');
            $statsCards = [
                [
                    'label' => helperTrans('ingaz.total_requests'),
                    'value' => $totalCount ?? 0,
                    'icon' => 'fas fa-layer-group',
                    'theme' => 'primary',
                    'status' => '',
                    'hint' => 'عرض كل الطلبات',
                    'url' => route('SponsorshipTransfer.index'),
                ],
                [
                    'label' => helperTrans('ingaz.pending_requests'),
                    'value' => $pendingCount ?? 0,
                    'icon' => 'fas fa-clock',
                    'theme' => 'warning',
                    'status' => 'pending',
                    'hint' => 'فلترة الطلبات المنتظرة',
                    'url' => route('SponsorshipTransfer.index', ['status' => 'pending']),
                ],
                [
                    'label' => helperTrans('ingaz.trial_period'),
                    'value' => $trialCount ?? 0,
                    'icon' => 'fas fa-user-clock',
                    'theme' => 'info',
                    'status' => 'trial_period',
                    'hint' => 'عرض طلبات التجربة',
                    'url' => route('SponsorshipTransfer.index', ['status' => 'trial_period']),
                ],
                [
                    'label' => helperTrans('ingaz.transferred_successfully'),
                    'value' => $transferredCount ?? 0,
                    'icon' => 'fas fa-check-circle',
                    'theme' => 'success',
                    'status' => 'transferred',
                    'hint' => 'عرض الطلبات المنقولة',
                    'url' => route('SponsorshipTransfer.index', ['status' => 'transferred']),
                ],
                [
                    'label' => helperTrans('ingaz.failed_trial'),
                    'value' => $failedCount ?? 0,
                    'icon' => 'fas fa-times-circle',
                    'theme' => 'danger',
                    'status' => 'failed',
                    'hint' => 'عرض الطلبات المتعثرة',
                    'url' => route('SponsorshipTransfer.index', ['status' => 'failed']),
                ],
            ];
        @endphp

        <div class="stats-grid-caption mb-3">
            <h5 class="mb-0 fw-bold">الإحصائيات السريعة</h5>
            <p>اضغط على أي بطاقة لتصفية الجدول مباشرة حسب الحالة.</p>
        </div>

        <div class="stats-grid">
            @foreach($statsCards as $card)
                <a href="{{ $card['url'] }}" class="statistics-card-link">
                    <div class="card statistics-card h-100 stats-theme-{{ $card['theme'] }} {{ $currentStatus === $card['status'] ? 'is-active' : '' }}"
                         data-status-target="{{ $card['status'] }}">
                        <div class="card-body d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="stat-label mb-2">{{ $card['label'] }}</div>
                                <div class="stat-value mb-2">{{ number_format((int) $card['value']) }}</div>
                                <div class="stat-hint">{{ $card['hint'] }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-3">
                                <span class="stat-icon">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>
                                <span class="stat-arrow">اضغط للعرض</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Filters Section -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter me-2"></i>
                    {{helperTrans("ingaz.filters")}}
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="collapse" id="filtersCollapse">
                <div class="card-body">
                    <div class="row g-3">
                        @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-3'])
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">{{helperTrans("ingaz.status")}}</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">{{helperTrans("ingaz.all_statuses")}}</option>
                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>{{helperTrans("ingaz.pending")}}</option>
                                <option value="waiting_contract_send" {{ $currentStatus === 'waiting_contract_send' ? 'selected' : '' }}>بانتظار تسجيل الاستلام</option>
                                <option value="contract_sent" {{ $currentStatus === 'contract_sent' ? 'selected' : '' }}>تم إرسال العقد</option>
                                <option value="contract_signed" {{ $currentStatus === 'contract_signed' ? 'selected' : '' }}>تم توقيع العقد</option>
                                <option value="maid_delivered" {{ $currentStatus === 'maid_delivered' ? 'selected' : '' }}>تم استلام العاملة</option>
                                <option value="trial_period" {{ $currentStatus === 'trial_period' ? 'selected' : '' }}>فترة التجربة</option>
                                <option value="trial_started" {{ $currentStatus === 'trial_started' ? 'selected' : '' }}>بدأت التجربة</option>
                                <option value="partially_paid" {{ $currentStatus === 'partially_paid' ? 'selected' : '' }}>مدفوع جزئي</option>
                                <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>{{helperTrans("ingaz.approved")}}</option>
                                <option value="transferred" {{ $currentStatus === 'transferred' ? 'selected' : '' }}>تم النقل</option>
                                <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>{{helperTrans("ingaz.rejected")}}</option>
                                <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>{{helperTrans("ingaz.completed")}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="maidPassportFilter" class="form-label">رقم جواز العاملة</label>
                            <input type="text" class="form-control" id="maidPassportFilter"
                                   value="{{ request('maid_passport') }}" placeholder="ابحث برقم الجواز">
                        </div>
                        <div class="col-md-3">
                            <label for="maidNationalityFilter" class="form-label">جنسية العاملة</label>
                            <select class="form-select" id="maidNationalityFilter">
                                <option value="">كل الجنسيات</option>
                                @foreach($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}" {{ (string) request('maid_nationality_id') === (string) $nationality->id ? 'selected' : '' }}>
                                        {{ $nationality->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFromFilter" class="form-label">{{helperTrans("ingaz.date_from")}}</label>
                            <input type="date" class="form-control" id="dateFromFilter">
                        </div>
                        <div class="col-md-3">
                            <label for="dateToFilter" class="form-label">{{helperTrans("ingaz.date_to")}}</label>
                            <input type="date" class="form-control" id="dateToFilter">
                        </div>
                        <div class="col-md-3">
                            <label for="contractNumberFilter" class="form-label">{{helperTrans("ingaz.contract_number")}}</label>
                            <input type="text" class="form-control" id="contractNumberFilter" placeholder="{{helperTrans("ingaz.search_contract_number")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="oldSponsorFilter" class="form-label">{{helperTrans("ingaz.old_sponsor")}}</label>
                            <input type="text" class="form-control" id="oldSponsorFilter" placeholder="{{helperTrans("ingaz.search_old_sponsor")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="newSponsorFilter" class="form-label">{{helperTrans("ingaz.new_sponsor")}}</label>
                            <input type="text" class="form-control" id="newSponsorFilter" placeholder="{{helperTrans("ingaz.search_new_sponsor")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="trialDaysFilter" class="form-label">{{helperTrans("ingaz.trial_days")}}</label>
                            <select class="form-select" id="trialDaysFilter">
                                <option value="">{{helperTrans("ingaz.all_trial_days")}}</option>
                                <option value="30">30 {{helperTrans("ingaz.days")}}</option>
                                <option value="60">60 {{helperTrans("ingaz.days")}}</option>
                                <option value="90">90 {{helperTrans("ingaz.days")}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="amountFilter" class="form-label">{{helperTrans("ingaz.contract_value")}}</label>
                            <select class="form-select" id="amountFilter">
                                <option value="">{{helperTrans("ingaz.all_amounts")}}</option>
                                <option value="0-5000">0 - 5,000 ريال</option>
                                <option value="5000-10000">5,000 - 10,000 ريال</option>
                                <option value="10000-15000">10,000 - 15,000 ريال</option>
                                <option value="15000+">15,000+ ريال</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="marketerFilter" class="form-label">المسوق</label>
                            <select class="form-select" id="marketerFilter">
                                <option value="">كل المسوقين</option>
                                @foreach($marketers as $marketer)
                                    <option value="{{ $marketer->id }}" {{ (string) request('marketer_id') === (string) $marketer->id ? 'selected' : '' }}>{{ $marketer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="officeFilter" class="form-label">المكتب الخارجي</label>
                            <select class="form-select" id="officeFilter">
                                <option value="">كل المكاتب</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office['id'] }}" {{ (string) request('office_id') === (string) $office['id'] ? 'selected' : '' }}>{{ $office['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" id="clearFiltersBtn">
                                <i class="fas fa-times me-2"></i>
                                {{helperTrans("ingaz.clear_filters")}}
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" id="exportExcelBtn">
                                    <i class="fas fa-file-excel me-2"></i>
                                    تصدير Excel
                                </button>
                                <button type="button" class="btn btn-primary" id="searchBtn">
                                    <i class="fas fa-search me-2"></i>
                                    {{helperTrans("ingaz.search")}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    {{helperTrans("ingaz.sponsorship_transfer_requests_list")}}
                </h6>
            </div>
            <div class="card-body">

                    <table id="sponsorshipTransferTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{helperTrans("ingaz.contract_number")}}</th>
                                <th>{{helperTrans("ingaz.old_sponsor")}}</th>
                                <th>{{helperTrans("ingaz.new_sponsor")}}</th>
                                <th>بيانات العاملة</th>
                                <th>{{helperTrans("ingaz.trial_days")}}</th>
                                <th>{{helperTrans("ingaz.trial_start_date")}}</th>
                                <th>نهاية الفترة التجريبية</th>
                                <th>{{helperTrans("ingaz.contract_value")}}</th>
                                <th>{{helperTrans("ingaz.paid_amount")}}</th>
                                <th>{{helperTrans("ingaz.remaining_amount")}}</th>
                                <th>{{helperTrans("ingaz.status")}}</th>
                                <th>{{helperTrans("ingaz.created_at")}}</th>
                                <th>{{helperTrans("ingaz.actions")}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
            </div>
        </div>
    </div>

    <!-- Modal تأكيد الإجراءات -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionModalLabel">تأكيد الإجراء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="confirmMessage">هل أنت متأكد من تنفيذ هذا الإجراء؟</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">
                        <i class="fas fa-check me-1"></i>تأكيد
                    </button>
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script>
        $(document).ready(function() {
            function syncStatisticsCards() {
                const activeStatus = $('#statusFilter').val() || '';
                $('.statistics-card').removeClass('is-active');
                $('.statistics-card').filter(function() {
                    return ($(this).data('status-target') || '') === activeStatus;
                }).addClass('is-active');
            }

            function currentFilters() {
                return {
                    branch_id: $('#branchFilter').val(),
                    status: $('#statusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_nationality_id: $('#maidNationalityFilter').val(),
                    old_sponsor: $('#oldSponsorFilter').val(),
                    new_sponsor: $('#newSponsorFilter').val(),
                    trial_days: $('#trialDaysFilter').val(),
                    amount: $('#amountFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    office_id: $('#officeFilter').val()
                };
            }

            // تصدير نفس النتائج المفلترة في الجدول
            $('#exportExcelBtn').click(function() {
                var params = {export: 'xlsx'};
                $.each(currentFilters(), function(key, value) {
                    if (value) {
                        params[key] = value;
                    }
                });
                window.location.href = '{{ route("SponsorshipTransfer.index") }}?' + $.param(params);
            });

            // إعداد DataTable
            var dataTable = $('#sponsorshipTransferTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: '{{ route("SponsorshipTransfer.index") }}',
                    data: function(d) {
                        Object.assign(d, currentFilters());
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'contract_number', name: 'contract_number', orderable: false, searchable: false},
                    {data: 'old_sponsor', name: 'old_sponsor', orderable: false, searchable: false},
                    {data: 'new_sponsor', name: 'new_sponsor', orderable: false, searchable: false},
                    {data: 'maid_info', name: 'maid_info', orderable: false, searchable: false},
                    {data: 'trial_days', name: 'trial_days', orderable: true, searchable: false},
                    {data: 'trial_start_date', name: 'trial_start_date', orderable: true, searchable: false},
                    {data: 'trial_end_info', name: 'trial_end_info', orderable: false, searchable: false},
                    {data: 'contract_value', name: 'contract_value', orderable: true, searchable: false},
                    {data: 'paid_amount', name: 'paid_amount', orderable: true, searchable: false},
                    {data: 'remaining_amount', name: 'remaining_amount', orderable: true, searchable: false},
                    {data: 'status', name: 'status', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at', orderable: true, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
                },
                responsive: true,
                order: [[12, 'desc']] // ترتيب حسب تاريخ الإنشاء
            });

            syncStatisticsCards();

            $('#statusFilter').on('change', function() {
                syncStatisticsCards();
            });

            // البحث عند الضغط على زر البحث
            $('#searchBtn').click(function() {
                syncStatisticsCards();
                dataTable.draw();
            });

            // مسح الفلاتر
            $('#clearFiltersBtn').click(function() {
                $('#branchFilter').val('');
                $('#statusFilter').val('');
                $('#dateFromFilter').val('');
                $('#dateToFilter').val('');
                $('#contractNumberFilter').val('');
                $('#maidPassportFilter').val('');
                $('#maidNationalityFilter').val('');
                $('#oldSponsorFilter').val('');
                $('#newSponsorFilter').val('');
                $('#trialDaysFilter').val('');
                $('#amountFilter').val('');
                $('#marketerFilter').val('');
                $('#officeFilter').val('');
                syncStatisticsCards();
                dataTable.draw();
            });

            // Handle confirm actions (using SweetAlert2)
            $(document).on('click', '.confirm-action', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const id = $(this).data('id');
                const text = $(this).data('text');

                Swal.fire({
                    title: 'تأكيد العملية',
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAction(action, id);
                    }
                });
            });

            $(document).on('click', '.cancel-transfer-action', function(e) {
                e.preventDefault();
                const button = $(this);
                const id = button.data('id');
                const contractValue = Number(button.data('contract-value')) || 0;
                const paidAmount = Number(button.data('paid-amount')) || 0;
                const remainingAmount = Number(button.data('remaining-amount')) || 0;
                const money = (value) => Number(value || 0).toLocaleString('ar-SA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                Swal.fire({
                    title: 'تأكيد فشل التجربة',
                    html: `
                        <div class="text-start" dir="rtl">
                            <div class="alert alert-light border mb-3">
                                <div>قيمة العقد: <strong>${money(contractValue)} ريال</strong></div>
                                <div>الدائن (المدفوع): <strong>${money(paidAmount)} ريال</strong></div>
                                <div>المدين (المتبقي): <strong>${money(remainingAmount)} ريال</strong></div>
                                <div>إجمالي الخصومات: <strong>0.00 ريال</strong></div>
                            </div>
                            <label class="form-label">نوع التسوية</label>
                            <select id="failureSettlementType" class="form-select mb-2">
                                <option value="refund" selected>استرجاع للعميل (يُسجل كمستحق للعميل)</option>
                            </select>
                            <label class="form-label">مبلغ التسوية</label>
                            <input type="number" id="failureSettlementAmount" class="form-control mb-1" min="0" step="0.01" value="${paidAmount.toFixed(2)}">
                            <div class="form-text mb-2">يتم احتساب الاسترجاع تلقائيا من المبلغ المدفوع ويمكن تعديله</div>
                            <label class="form-label">طريقة السداد</label>
                            <select id="failureRefundMethod" class="form-select mb-2">
                                <option value="bank" selected>تحويل بنكي</option>
                                <option value="cash">نقدي</option>
                            </select>
                            <label class="form-label" id="failureRefundAccountLabel">البنك / حساب الصرف <span class="text-danger">*</span></label>
                            <select id="failureRefundAccount" class="form-select mb-2">
                                <option value="">اختر الحساب الذي سيصدر عليه سند الصرف</option>
                                @foreach($receiptAccounts as $account)
                                    <option value="{{ $account->id }}" data-cash="{{ (int) $account->getRawOriginal('type') === \Nami\Finance\Enums\AccountTypesEnum::Box->value ? 1 : 0 }}">{{ e($account->code . ' - ' . $account->name) }}</option>
                                @endforeach
                            </select>
                            <div id="failureBankRefundFields">
                                <label class="form-label">آيبان العميل <span class="text-danger">*</span></label>
                                <input type="text" id="failureClientIban" class="form-control mb-2" dir="ltr" maxlength="34" placeholder="SA0000000000000000000000">
                                <label class="form-label">مرفق الآيبان <span class="text-danger">*</span></label>
                                <input type="file" id="failureClientIbanAttachment" class="form-control mb-2" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                            <div class="alert alert-warning py-2 mb-2">فشل النقل لا يُعد سداداً؛ سيبقى المتبقي كما هو ما لم تسجل مبلغ شطب صراحةً أدناه.</div>
                            <label class="form-label">مبلغ شطب المتبقي</label>
                            <input type="number" id="failureClearRemainingAmount" class="form-control mb-1" min="0" step="0.01" value="0.00">
                            <div class="form-text mb-2">اتركه صفراً إذا لم يحدث سداد أو شطب فعلي</div>
                            <div class="alert alert-info mb-2" id="failureSettlementPreview"></div>
                            <label class="form-label">سبب الفشل</label>
                            <textarea id="failureReason" class="form-control" rows="3" placeholder="اكتب سبب الفشل أو الإلغاء"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'حفظ وإلغاء النقل',
                    cancelButtonText: 'إلغاء',
                    didOpen: () => {
                        const updatePreview = () => {
                            const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                            const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                            $('#failureSettlementPreview').html(`
                                الدائن بعد التسوية: <strong>${money(paidAmount)} ريال</strong><br>
                                المدين بعد التسوية: <strong>${money(Math.max(0, remainingAmount - clearRemainingAmount))} ريال</strong><br>
                                المستحق للعميل بعد التسوية: <strong>${money(settlementAmount)} ريال</strong>
                            `);
                        };

                        $('#failureSettlementAmount, #failureClearRemainingAmount').on('input', updatePreview);
                        updatePreview();

                        // السداد النقدي: صناديق فقط وبدون آيبان؛ البنكي (الافتراضي): حسابات بنكية مع الآيبان
                        const applyRefundMethod = () => {
                            @include('admin.ingaz.SponsorshipTransfer.partials.refund-account-help')
                        };

                        $('#failureRefundMethod').on('change', applyRefundMethod);
                        applyRefundMethod();
                    },
                    preConfirm: () => {
                        const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                        const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                        const iban = ($('#failureClientIban').val() || '').trim();
                        const attachment = $('#failureClientIbanAttachment')[0]?.files?.[0] || null;
                        const refundAccountId = $('#failureRefundAccount').val();
                        const refundMethod = $('#failureRefundMethod').val() || 'bank';

                        if (settlementAmount > paidAmount) {
                            Swal.showValidationMessage('مبلغ الاسترجاع لا يمكن أن يكون أكبر من المبلغ المدفوع.');
                            return false;
                        }

                        if (clearRemainingAmount > remainingAmount) {
                            Swal.showValidationMessage('مبلغ تصفير المتبقي لا يمكن أن يكون أكبر من المبلغ المتبقي.');
                            return false;
                        }

                        if (refundMethod === 'bank' && settlementAmount > 0 && !iban && !attachment) {
                            Swal.showValidationMessage('يرجى إدخال آيبان العميل أو إرفاق مرفق الآيبان.');
                            return false;
                        }

                        if (settlementAmount > 0 && !refundAccountId) {
                            Swal.showValidationMessage(refundMethod === 'cash'
                                ? 'يرجى اختيار الصندوق لإصدار سند الصرف.'
                                : 'يرجى اختيار البنك أو حساب الصرف لإصدار سند الصرف.');
                            return false;
                        }

                        return {
                            failure_reason: ($('#failureReason').val() || '').trim(),
                            failure_settlement_type: $('#failureSettlementType').val(),
                            failure_refund_method: refundMethod,
                            failure_settlement_amount: settlementAmount,
                            failure_clear_remaining_amount: clearRemainingAmount,
                            failure_client_iban: refundMethod === 'bank' ? iban : '',
                            refund_account_id: refundAccountId,
                            failure_client_iban_attachment: refundMethod === 'bank' ? attachment : null
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        performCancelTransfer(id, result.value);
                    }
                });
            });

            function performCancelTransfer(id, payload) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('failure_reason', payload.failure_reason || '');
                formData.append('failure_settlement_type', payload.failure_settlement_type || 'refund');
                formData.append('failure_refund_method', payload.failure_refund_method || 'bank');
                formData.append('failure_settlement_amount', payload.failure_settlement_amount || 0);
                formData.append('failure_clear_remaining_amount', payload.failure_clear_remaining_amount || 0);
                formData.append('failure_client_iban', payload.failure_client_iban || '');
                formData.append('refund_account_id', payload.refund_account_id || '');

                if (payload.failure_client_iban_attachment) {
                    formData.append('failure_client_iban_attachment', payload.failure_client_iban_attachment);
                }

                $.ajax({
                    url: `/admin/SponsorshipTransfer/${id}/cancel-transfer`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                        success: function(response) {
                            if (response.success) {
                                const hasAccountingWarning = Boolean(response.accounting_warning);
                                Swal.fire(
                                    hasAccountingWarning ? 'تمت العملية مع تنبيه محاسبي' : 'نجح!',
                                    response.message,
                                    hasAccountingWarning ? 'warning' : 'success'
                                ).then(() => {
                                    dataTable.ajax.reload();
                                });
                        } else {
                            Swal.fire('خطأ!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
                        Swal.fire('خطأ!', message, 'error');
                    }
                });
            }

            function performAction(action, id) {
                let url = `/admin/SponsorshipTransfer/${id}/${action}`;
                let method = 'POST';

                if (action === 'destroy') {
                    method = 'DELETE';
                    url = `/admin/SponsorshipTransfer/${id}`;
                }

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            const hasAccountingWarning = Boolean(response.accounting_warning);
                            Swal.fire(
                                hasAccountingWarning ? 'تمت العملية مع تنبيه محاسبي' : 'نجح!',
                                response.message,
                                hasAccountingWarning ? 'warning' : 'success'
                            ).then(() => {
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire('خطأ!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ غير متوقع', 'error');
                    }
                });
            }
        });
    </script>
    @include('admin.ingaz.payment-link-modal')
@endsection
