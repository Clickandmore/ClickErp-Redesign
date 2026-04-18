@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .dispatch-stat-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            padding: 18px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            height: 100%;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .dispatch-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .dispatch-stat-card.active {
            border-color: rgba(13, 110, 253, 0.45);
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.14);
        }

        .dispatch-stat-label {
            color: #64748b;
            font-size: 0.92rem;
            margin-bottom: 6px;
        }

        .dispatch-stat-value {
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
        }

        .dispatch-filter-chip {
            min-width: 150px;
        }
    </style>
@endsection

@section('page-title')
    إرسالات المكتب الخارجي
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h3 class="mb-1">إرسالات المكتب الخارجي</h3>
                <p class="text-muted mb-0">عرض العقود التي تم إرسالها للمكتب الخارجي مع إمكانية فلترة العقود غير المرسلة.</p>
            </div>
            <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-file-contract me-1"></i>
                عقود الاستقدام
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <button type="button"
                    class="dispatch-stat-card w-100 text-start border-0 dispatch-filter-chip {{ $dispatchFilter === 'all' ? 'active' : '' }}"
                    data-dispatch-filter="all">
                    <div class="dispatch-stat-label">كل العقود المرتبطة بالإرسالات</div>
                    <div class="dispatch-stat-value">{{ $stats['all'] ?? 0 }}</div>
                </button>
            </div>
            <div class="col-md-4">
                <button type="button"
                    class="dispatch-stat-card w-100 text-start border-0 dispatch-filter-chip {{ $dispatchFilter === 'sent' ? 'active' : '' }}"
                    data-dispatch-filter="sent">
                    <div class="dispatch-stat-label">العقود المرسلة</div>
                    <div class="dispatch-stat-value">{{ $stats['sent'] ?? 0 }}</div>
                </button>
            </div>
            <div class="col-md-4">
                <button type="button"
                    class="dispatch-stat-card w-100 text-start border-0 dispatch-filter-chip {{ $dispatchFilter === 'not_sent' ? 'active' : '' }}"
                    data-dispatch-filter="not_sent">
                    <div class="dispatch-stat-label">العقود غير المرسلة</div>
                    <div class="dispatch-stat-value">{{ $stats['not_sent'] ?? 0 }}</div>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form id="external-office-dispatch-filter-form" class="row g-3">
                    <input type="hidden" name="dispatch_filter" id="dispatch_filter" value="{{ $dispatchFilter }}">

                    <div class="col-md-6">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="رقم العقد، التأشيرة، العميل، الجواز، العاملة">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">حالة العقد</label>
                        <select name="contract_status" class="form-select">
                            <option value="">الكل</option>
                            <option value="pending_maid_selection">بانتظار اختيار العاملة</option>
                            <option value="new">جديد</option>
                            <option value="msaned">مساند</option>
                            <option value="tafwed">تفويض</option>
                            <option value="taffez">تفييز</option>
                            <option value="tazker">تذكرة</option>
                            <option value="arrival">وصول</option>
                            <option value="completed">مكتمل</option>
                            <option value="back">مرتجع</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">تصفية</button>
                        <button type="button" id="reset-dispatch-filters" class="btn btn-light">إعادة</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="external-office-dispatch-table" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>رقم العقد</th>
                                <th>العميل</th>
                                <th>العاملة</th>
                                <th>المكتب</th>
                                <th>حالة العقد</th>
                                <th>حالة الإرسالية</th>
                                <th>تاريخ الإرسالية</th>
                                <th>محطة الوصول</th>
                                <th>الإجراءات</th>
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
    <script>
        $(document).ready(function() {
            const table = $('#external-office-dispatch-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                searching: false,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('RecruitmentContract.externalOfficeDispatch.index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.dispatch_filter = $('#dispatch_filter').val();
                        d.global_search = $('input[name="search"]').val();
                        d.contract_status = $('select[name="contract_status"]').val();
                    }
                },
                columns: [{
                        data: 'contract_number',
                        name: 'id'
                    },
                    {
                        data: 'client',
                        name: 'client',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'worker',
                        name: 'worker',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'office',
                        name: 'office',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'contract_status',
                        name: 'status'
                    },
                    {
                        data: 'dispatch_status',
                        name: 'dispatch_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'dispatch_date',
                        name: 'externalOfficeDispatch.dispatch_date',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'arrival_station',
                        name: 'arrival_station',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
                }
            });

            $('#external-office-dispatch-filter-form').on('submit', function(event) {
                event.preventDefault();
                table.ajax.reload();
            });

            $('#reset-dispatch-filters').on('click', function() {
                $('input[name="search"]').val('');
                $('select[name="contract_status"]').val('');
                $('#dispatch_filter').val('sent');
                $('.dispatch-filter-chip').removeClass('active');
                $('.dispatch-filter-chip[data-dispatch-filter="sent"]').addClass('active');
                table.ajax.reload();
            });

            $('.dispatch-filter-chip').on('click', function() {
                const filter = $(this).data('dispatch-filter');
                $('#dispatch_filter').val(filter);
                $('.dispatch-filter-chip').removeClass('active');
                $(this).addClass('active');
                table.ajax.reload();
            });
        });
    </script>
@endsection
