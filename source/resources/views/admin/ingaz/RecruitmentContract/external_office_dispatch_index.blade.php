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
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-contract me-1"></i>
                    الرجوع للعقود السارية
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('bulk_dispatch_errors') && count(session('bulk_dispatch_errors')))
            <div class="alert alert-warning">
                <div class="fw-semibold mb-2">ملاحظات على الحفظ المجمع:</div>
                <ul class="mb-0 ps-3">
                    @foreach (session('bulk_dispatch_errors') as $bulkDispatchError)
                        <li>{{ $bulkDispatchError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="text-muted small">
                        تظهر العقود السارية التي لم تُنشأ لها إرسالية أولًا. يمكن جمع العقود من نفس الجنسية والمكتب في إرسالية واحدة وتحديد تكلفتها مرة واحدة.
                    </div>
                    <button type="button" id="open-bulk-dispatch-modal" class="btn btn-primary" disabled
                        data-bs-toggle="modal" data-bs-target="#bulkDispatchModal">
                        <i class="fas fa-layer-group me-1"></i>
                        إنشاء إرساليات مجمعة
                        <span class="badge bg-light text-primary ms-1" id="selected-dispatch-count">0</span>
                    </button>
                </div>
                <div id="bulk-selection-group-alert" class="alert alert-info py-2 d-none" role="status"></div>
                <div class="table-responsive">
                    <table id="external-office-dispatch-table" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 42px;">
                                    <input type="checkbox" class="form-check-input" id="select-all-dispatch-contracts">
                                </th>
                                <th>رقم العقد</th>
                                <th>العميل</th>
                                <th>العاملة</th>
                                <th>الجنسية</th>
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

    <div class="modal fade" id="bulkDispatchModal" tabindex="-1" aria-labelledby="bulkDispatchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('RecruitmentContract.externalOfficeDispatch.bulkStore') }}" class="modal-content" id="bulkDispatchForm">
                @csrf
                <div id="bulk-dispatch-contract-inputs"></div>
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkDispatchModalLabel">إنشاء إرساليات مجمعة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        سيتم إنشاء إرسالية لكل عقد محدد باستخدام نفس التاريخ والتكلفة. يجب أن تكون العقود من نفس الجنسية ونفس المكتب الخارجي.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عدد العقود المحددة</label>
                        <input type="text" class="form-control" id="bulk-dispatch-selected-summary" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المكتب والجنسية</label>
                        <input type="text" class="form-control" id="bulk-dispatch-group-summary" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الإرسالية <span class="text-danger">*</span></label>
                        <input type="date" name="dispatch_date" class="form-control"
                            value="{{ old('dispatch_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">تكلفة العقد بالدولار <span class="text-danger">*</span></label>
                            <input type="number" name="office_contract_cost_usd" id="bulk_office_contract_cost_usd"
                                class="form-control" value="{{ old('office_contract_cost_usd') }}"
                                step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المقابل بالريال</label>
                            <div class="input-group">
                                <input type="number" id="bulk_office_contract_cost_sar" class="form-control"
                                    step="0.01" min="0.01">
                                <span class="input-group-text">ريال</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">محطة الوصول</label>
                        <input type="text" name="arrival_station" class="form-control"
                            value="{{ old('arrival_station') }}"
                            placeholder="اتركها فارغة لاستخدام محطة الوصول المسجلة لكل عقد">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        حفظ الإرساليات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            const selectedDispatchContracts = new Set(
                @json(collect($selectedContractIds ?? [])->map(fn ($id) => (string) $id)->values())
            );
            const selectedDispatchGroups = new Map(
                Object.entries(@json($selectedContractGroups ?? collect())).map(([id, group]) => [String(id), group])
            );

            function checkboxGroup(checkbox) {
                return {
                    office_id: String($(checkbox).data('office-id') || ''),
                    nationality_id: String($(checkbox).data('nationality-id') || ''),
                    office_name: String($(checkbox).data('office-name') || 'غير محدد'),
                    nationality_name: String($(checkbox).data('nationality-name') || 'غير محددة'),
                };
            }

            function groupKey(group) {
                return `${group.office_id}:${group.nationality_id}`;
            }

            function activeSelectionGroup() {
                const firstGroup = selectedDispatchGroups.values().next();
                return firstGroup.done ? null : firstGroup.value;
            }

            function isCompatibleGroup(group) {
                const activeGroup = activeSelectionGroup();
                return !activeGroup || groupKey(activeGroup) === groupKey(group);
            }

            function syncBulkDispatchControls() {
                const selectedCount = selectedDispatchContracts.size;
                $('#selected-dispatch-count').text(selectedCount);
                $('#open-bulk-dispatch-modal').prop('disabled', selectedCount === 0);
                $('#bulk-dispatch-selected-summary').val(selectedCount + ' عقد');
                const activeGroup = activeSelectionGroup();
                const groupDescription = activeGroup
                    ? `${activeGroup.office_name} / ${activeGroup.nationality_name}`
                    : '';
                $('#bulk-dispatch-group-summary').val(groupDescription);
                $('#bulk-selection-group-alert')
                    .toggleClass('d-none', !activeGroup)
                    .text(activeGroup
                        ? `الاختيار الحالي: ${groupDescription}. يمكن إضافة عقود أخرى من نفس المجموعة فقط.`
                        : '');

                const inputs = Array.from(selectedDispatchContracts)
                    .map(id => `<input type="hidden" name="contract_ids[]" value="${id}">`)
                    .join('');
                $('#bulk-dispatch-contract-inputs').html(inputs);

                $('.dispatch-contract-checkbox').each(function() {
                    $(this).prop('checked', selectedDispatchContracts.has(String($(this).val())));
                });

                const visibleCheckboxes = $('.dispatch-contract-checkbox:not(:disabled)');
                const visibleCheckedCount = visibleCheckboxes.filter(':checked').length;
                $('#select-all-dispatch-contracts')
                    .prop('checked', visibleCheckboxes.length > 0 && visibleCheckedCount === visibleCheckboxes.length)
                    .prop('indeterminate', visibleCheckedCount > 0 && visibleCheckedCount < visibleCheckboxes.length);
            }

            const table = $('#external-office-dispatch-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 25,
                searching: false,
                order: [
                    [1, 'desc']
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
                        data: 'selection',
                        name: 'selection',
                        orderable: false,
                        searchable: false
                    },
                    {
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
                        data: 'nationality',
                        name: 'nationality',
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
                },
                drawCallback: syncBulkDispatchControls
            });

            $('#external-office-dispatch-filter-form').on('submit', function(event) {
                event.preventDefault();
                table.ajax.reload();
            });

            $('#reset-dispatch-filters').on('click', function() {
                $('input[name="search"]').val('');
                $('select[name="contract_status"]').val('');
                $('#dispatch_filter').val('not_sent');
                $('.dispatch-filter-chip').removeClass('active');
                $('.dispatch-filter-chip[data-dispatch-filter="not_sent"]').addClass('active');
                selectedDispatchContracts.clear();
                selectedDispatchGroups.clear();
                syncBulkDispatchControls();
                table.ajax.reload();
            });

            $('.dispatch-filter-chip').on('click', function() {
                const filter = $(this).data('dispatch-filter');
                $('#dispatch_filter').val(filter);
                $('.dispatch-filter-chip').removeClass('active');
                $(this).addClass('active');
                selectedDispatchContracts.clear();
                selectedDispatchGroups.clear();
                syncBulkDispatchControls();
                table.ajax.reload();
            });

            $(document).on('change', '.dispatch-contract-checkbox', function() {
                const contractId = String($(this).val());
                if ($(this).is(':checked')) {
                    const group = checkboxGroup(this);
                    if (!group.office_id || !group.nationality_id) {
                        $(this).prop('checked', false);
                        window.alert('يجب تحديد المكتب الخارجي والجنسية للعقد قبل إضافته إلى الإرسالية المجمعة.');
                        return;
                    }
                    if (!isCompatibleGroup(group)) {
                        $(this).prop('checked', false);
                        window.alert('يمكن جمع العقود من نفس الجنسية ونفس المكتب الخارجي فقط.');
                        return;
                    }
                    selectedDispatchContracts.add(contractId);
                    selectedDispatchGroups.set(contractId, group);
                } else {
                    selectedDispatchContracts.delete(contractId);
                    selectedDispatchGroups.delete(contractId);
                }
                syncBulkDispatchControls();
            });

            $('#select-all-dispatch-contracts').on('change', function() {
                const shouldSelect = $(this).is(':checked');
                $('.dispatch-contract-checkbox:not(:disabled)').each(function() {
                    const contractId = String($(this).val());
                    if (shouldSelect) {
                        const group = checkboxGroup(this);
                        if (group.office_id && group.nationality_id && isCompatibleGroup(group)) {
                            selectedDispatchContracts.add(contractId);
                            selectedDispatchGroups.set(contractId, group);
                        }
                    } else {
                        selectedDispatchContracts.delete(contractId);
                        selectedDispatchGroups.delete(contractId);
                    }
                });
                syncBulkDispatchControls();
            });

            const usdInput = document.getElementById('bulk_office_contract_cost_usd');
            const sarInput = document.getElementById('bulk_office_contract_cost_sar');
            const usdToSarRate = 3.75;
            let updatingCurrency = false;

            function syncCurrency(source, target, multiplier) {
                if (updatingCurrency) {
                    return;
                }

                updatingCurrency = true;
                const value = parseFloat(String(source?.value || '').replace(',', '.'));
                target.value = Number.isFinite(value) && value > 0 ? (value * multiplier).toFixed(2) : '';
                updatingCurrency = false;
            }

            usdInput?.addEventListener('input', () => syncCurrency(usdInput, sarInput, usdToSarRate));
            sarInput?.addEventListener('input', () => syncCurrency(sarInput, usdInput, 1 / usdToSarRate));

            if (usdInput?.value) {
                syncCurrency(usdInput, sarInput, usdToSarRate);
            }
        });
    </script>
@endsection
