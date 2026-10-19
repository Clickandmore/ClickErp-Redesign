@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('delegations.title') }}
@endsection

@section('css')
    <style>
        .ingaz-delegations-page { --dl-primary: #4f46e5; --dl-soft: #eef2ff; --dl-border: #e5e7eb; --dl-muted: #6b7280; }

        /* الرأس */
        .dl-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem; border-radius: 16px; background: linear-gradient(135deg, #312e81, #4f46e5 60%, #0d9488); color: #fff; }
        .dl-hero h3 { margin: 0 0 .25rem; font-size: 1.35rem; font-weight: 700; color: #fff; }
        .dl-hero p { margin: 0; opacity: .85; font-size: .9rem; }
        .dl-hero-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .dl-hero-actions .btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: 10px; font-weight: 600; font-size: .88rem; padding: .5rem .95rem; }
        .dl-hero-actions .btn-light { color: #312e81; }
        .dl-hero-actions .btn-outline-light:hover { color: #312e81; }

        /* الفلاتر */
        .dl-box { border: 1px solid var(--dl-border); border-radius: 14px; background: #fff; }
        .dl-filter { padding: 1rem 1.1rem; margin-bottom: 1rem; }
        .dl-filter-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .9rem; }
        .dl-filter-head h6 { margin: 0; font-weight: 700; color: #1f2937; }
        .dl-search { position: relative; flex: 1 1 320px; max-width: 480px; }
        .dl-search i { position: absolute; inset-inline-start: .8rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .dl-search .form-control { padding-inline-start: 2.2rem; }
        .ingaz-delegations-page .form-label { font-size: .78rem; font-weight: 600; color: #4b5563; margin-bottom: .3rem; }
        .ingaz-delegations-page .dl-filter .form-control,
        .ingaz-delegations-page .dl-filter .form-select { border-radius: 10px; font-size: .88rem; min-height: 38px; }
        .ingaz-delegations-page .dl-filter .select2-container .select2-selection { border-radius: 10px; min-height: 38px; }
        .dl-filter-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: 1rem; padding-top: .9rem; border-top: 1px dashed var(--dl-border); }
        .dl-filter-actions .btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: 10px; font-size: .86rem; font-weight: 600; padding: .45rem 1rem; }
        .dl-export { display: inline-flex; margin-inline-start: auto; border: 1px solid var(--dl-border); border-radius: 10px; overflow: hidden; }
        .dl-export .btn { border: 0; border-radius: 0; background: #fff; }
        .dl-export .btn + .btn { border-inline-start: 1px solid var(--dl-border); }
        .dl-export .btn-excel { color: #047857; }
        .dl-export .btn-excel:hover { background: #ecfdf5; color: #047857; }
        .dl-export .btn-pdf { color: #b91c1c; }
        .dl-export .btn-pdf:hover { background: #fef2f2; color: #b91c1c; }

        /* tables.css يفرض overflow: visible !important على .table-responsive
           ويُحمّل بعد @yield('css')، لذلك نحتاج محدداً أقوى لإعادة تفعيل التمرير الأفقي */
        .ingaz-delegations-page .table-responsive,
        .ingaz-delegations-page .dataTables_wrapper .table-responsive {
            overflow-x: auto !important;
            overflow-y: hidden !important;
            width: 100%;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        .ingaz-delegations-page .dataTables_wrapper { max-width: 100%; }
        .ingaz-delegations-page .table-card { padding: .5rem 0 0; }
        .ingaz-delegations-page .table-card .card-body { min-width: 0; }

        /* الجدول */
        #ingaz-delegations-table { min-width: 1180px; }
        #ingaz-delegations-table thead th { vertical-align: middle; background: #f9fafb; color: #4b5563; font-size: .78rem; font-weight: 600; white-space: nowrap; border-bottom: 1px solid var(--dl-border); padding: .75rem; }
        #ingaz-delegations-table td { padding: .7rem .75rem; font-size: .86rem; border-color: #f3f4f6; vertical-align: middle; }
        #ingaz-delegations-table tbody tr:hover td { background: #fafaff; }
        #ingaz-delegations-table td:first-child { color: var(--dl-primary); }

        .delegation-date-stack,
        .delegation-person-cell { display: flex; flex-direction: column; gap: 2px; min-width: 112px; }
        .delegation-date-main,
        .delegation-person-name { color: #1f2937; font-weight: 700; white-space: nowrap; }
        .delegation-date-stack small,
        .delegation-person-cell small { font-size: .74rem; }
        #ingaz-delegations-table .badge { padding: .35rem .65rem; border-radius: 99px; font-weight: 600; font-size: .74rem; }

        .delegation-status-select { min-width: 150px; border-radius: 99px !important; font-size: .8rem !important; font-weight: 600; background-color: #f9fafb; }

        /* شرائح الفاتورة والتحويل */
        .delegation-inline-actions { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; max-width: 300px; }
        .dl-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 99px; border: 1px solid transparent; font-size: .74rem; font-weight: 600; line-height: 1.4; white-space: nowrap; text-decoration: none; transition: background .15s, border-color .15s; }
        .dl-chip i { font-size: .7rem; }
        .dl-chip--success { background: #ecfdf5; color: #047857; border-color: #d1fae5; }
        .dl-chip--success:hover { background: #d1fae5; color: #065f46; }
        .dl-chip--info { background: #f0f9ff; color: #0369a1; border-color: #e0f2fe; }
        .dl-chip--info:hover { background: #e0f2fe; color: #075985; }
        .dl-chip--danger { background: #fef2f2; color: #b91c1c; border-color: #fee2e2; }
        .dl-chip--danger:hover { background: #fee2e2; color: #991b1b; }
        .dl-chip--add { background: #fff; color: var(--dl-primary); border: 1px dashed #c7d2fe; }
        .dl-chip--add:hover { background: var(--dl-soft); border-style: solid; color: #3730a3; }

        /* أزرار الصف: أيقونات مربعة موحدة */
        .delegation-row-actions { display: flex; align-items: center; gap: 4px; }
        .delegation-row-actions form { display: inline-flex; margin: 0; }
        .dl-act { width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; border-radius: 9px; border: 1px solid var(--dl-border); background: #fff; color: #4b5563; font-size: .8rem; text-decoration: none; cursor: pointer; transition: background .15s, color .15s, border-color .15s, transform .15s; }
        .dl-act:hover { transform: translateY(-1px); }
        .dl-act--view:hover { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
        .dl-act--edit:hover { background: var(--dl-soft); border-color: #c7d2fe; color: var(--dl-primary); }
        .dl-act--print:hover { background: #f3f4f6; border-color: #d1d5db; color: #111827; }
        .dl-act--delete { color: #dc2626; }
        .dl-act--delete:hover { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

        /* ترقيم DataTables */
        .ingaz-delegations-page .dataTables_wrapper .row:last-child { align-items: center; padding: .75rem 1rem; margin: 0; border-top: 1px solid var(--dl-border); background: #fcfcfd; border-radius: 0 0 14px 14px; }
        .ingaz-delegations-page .dataTables_wrapper .row:first-child { padding: .25rem 1rem .5rem; margin: 0; }
        .ingaz-delegations-page .dataTables_info { color: var(--dl-muted); font-size: .82rem; padding-top: 0 !important; }
        .ingaz-delegations-page .dataTables_length select { border-radius: 8px; }
        .ingaz-delegations-page .dataTables_paginate .pagination { gap: .3rem; margin: 0; flex-wrap: wrap; }
        .ingaz-delegations-page .dataTables_paginate .page-link { min-width: 34px; height: 34px; display: grid; place-items: center; padding: 0 .6rem; border-radius: 10px !important; border: 1px solid var(--dl-border); color: #374151; font-size: .84rem; font-weight: 600; }
        .ingaz-delegations-page .dataTables_paginate .page-link:hover { background: var(--dl-soft); border-color: #c7d2fe; color: var(--dl-primary); }
        .ingaz-delegations-page .dataTables_paginate .page-item.active .page-link { background: var(--dl-primary); border-color: var(--dl-primary); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, .3); }
        .ingaz-delegations-page .dataTables_paginate .page-item.disabled .page-link { background: #f9fafb; color: #d1d5db; }
        .ingaz-delegations-page .dataTables_processing { border-radius: 12px; border: 1px solid #e0defc; box-shadow: 0 12px 30px rgba(49, 46, 129, .15); color: #312e81; font-weight: 600; }

        @media (max-width: 768px) {
            .dl-hero-actions, .dl-hero-actions .btn { width: 100%; justify-content: center; }
            .dl-export { margin-inline-start: 0; width: 100%; }
            .dl-export .btn { flex: 1; justify-content: center; }
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid ingaz-delegations-page">
            <header class="dl-hero">
                <div>
                    <h3><i class="fa fa-file-signature me-2"></i>{{ __('delegations.title') }}</h3>
                    <p>{{ __('delegations.description') }}</p>
                </div>
                @if ($canCreateCosts || $canCreateDelegation)
                    <div class="dl-hero-actions">
                        @if ($canCreateCosts)
                            <a href="{{ route('delegation-costs.create') }}" class="btn btn-outline-light">
                                <i class="fa fa-coins"></i>{{ __('delegations.record_cost') }}
                            </a>
                        @endif
                        @if ($canCreateDelegation)
                            <a href="{{ route('reports.import.index', ['type' => 'ingaz_delegations']) }}" class="btn btn-outline-light">
                                <i class="fa fa-file-import"></i>{{ __('delegations.import_excel') }}
                            </a>
                            <a href="{{ route('ingaz-delegations.create') }}" class="btn btn-light">
                                <i class="fa fa-plus"></i>{{ __('delegations.add') }}
                            </a>
                        @endif
                    </div>
                @endif
            </header>

            <div class="dl-box dl-filter filter-card">
                <form method="GET" id="delegations-filter-form">
                    <div class="dl-filter-head">
                        <h6><i class="fa fa-sliders me-1"></i>{{ __('delegations.filter') }}</h6>
                        <div class="dl-search">
                            <i class="fa fa-magnifying-glass"></i>
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control"
                                   placeholder="{{ __('delegations.search_placeholder') }}" aria-label="{{ __('delegations.search') }}">
                        </div>
                    </div>

                    <div class="row g-3">
                        @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-6 col-md-4 col-xl-2'])
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.country') }}</label>
                            <select name="country_id" class="form-select select2" data-placeholder="{{ __('delegations.all') }}">
                                <option value="">{{ __('delegations.all') }}</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                        {{ $country->title ?: ($country->name_ar ?: $country->name_en) }}{{ $country->code ? ' - ' . $country->code : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.client_type') }}</label>
                            <select name="client_source_type" class="form-select">
                                <option value="">{{ __('delegations.all') }}</option>
                                <option value="existing_in_program" {{ request('client_source_type') === 'existing_in_program' ? 'selected' : '' }}>{{ __('delegations.existing_client') }}</option>
                                <option value="separate_client" {{ request('client_source_type') === 'separate_client' ? 'selected' : '' }}>{{ __('delegations.separate_client') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.marketer') }}</label>
                            <select name="marketer_id" class="form-select select2-marketer" data-placeholder="{{ __('delegations.all') }}">
                                <option value="">{{ __('delegations.all') }}</option>
                                @foreach ($marketers as $marketer)
                                    <option value="{{ $marketer->id }}" {{ request('marketer_id') == $marketer->id ? 'selected' : '' }}>{{ $marketer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.stage') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('delegations.all') }}</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.invoice') }}</label>
                            <select name="has_invoice" class="form-select">
                                <option value="">{{ __('delegations.all') }}</option>
                                <option value="1" {{ request('has_invoice') === '1' ? 'selected' : '' }}>{{ __('delegations.with_invoice') }}</option>
                                <option value="0" {{ request('has_invoice') === '0' ? 'selected' : '' }}>{{ __('delegations.without_invoice') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.musaned_transfer') }}</label>
                            <select name="has_musaned_transfer" class="form-select">
                                <option value="">{{ __('delegations.all') }}</option>
                                <option value="1" {{ request('has_musaned_transfer') === '1' ? 'selected' : '' }}>{{ __('delegations.transfer_recorded') }}</option>
                                <option value="0" {{ request('has_musaned_transfer') === '0' ? 'selected' : '' }}>{{ __('delegations.transfer_not_recorded') }}</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.date_from') }}</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                        </div>
                        <div class="col-6 col-md-4 col-xl-2">
                            <label class="form-label">{{ __('delegations.date_to') }}</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                        </div>
                    </div>

                    <div class="dl-filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-filter"></i>{{ __('delegations.filter') }}
                        </button>
                        <a href="{{ route('ingaz-delegations.index') }}" class="btn btn-light border">
                            <i class="fa fa-rotate-right"></i>{{ __('delegations.reset') }}
                        </a>
                        @if ($canExportDelegations)
                            <div class="dl-export" role="group" aria-label="{{ __('delegations.export_excel') }}">
                                <button type="button" class="btn btn-excel" id="export-excel-btn">
                                    <i class="fa fa-file-excel"></i>{{ __('delegations.export_excel') }}
                                </button>
                                <button type="button" class="btn btn-pdf" id="export-pdf-btn">
                                    <i class="fa fa-file-pdf"></i>{{ __('delegations.export_pdf') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            <div class="dl-box table-card">
                <div class="table-responsive">
                    <table id="ingaz-delegations-table" class="table align-middle mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('delegations.date') }}</th>
                                <th>{{ __('delegations.created_at') }}</th>
                                <th>{{ __('delegations.client') }}</th>
                                <th>{{ __('delegations.marketer') }}</th>
                                <th>{{ __('delegations.musaned_contract') }}</th>
                                <th>{{ __('delegations.country') }}</th>
                                <th>{{ __('delegations.profession') }}</th>
                                <th>{{ __('delegations.invoice_transfer') }}</th>
                                <th>{{ __('delegations.status') }}</th>
                                <th>{{ __('delegations.actions') }}</th>
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
        $(document).ready(function () {
            if ($.fn.select2) {
                $('select[name="country_id"], select[name="marketer_id"]').each(function () {
                    $(this).select2({
                        width: '100%',
                        allowClear: true,
                        placeholder: $(this).data('placeholder') || @json(__('delegations.all'))
                    });
                });
            }

            // مصدر واحد لقيم المرشحات يستخدمه الجدول وأزرار التصدير معاً.
            function currentFilters() {
                return {
                    branch_id: $('#branchFilter').val(),
                    global_search: $('input[name="search"]').val(),
                    country_id: $('select[name="country_id"]').val(),
                    marketer_id: $('select[name="marketer_id"]').val(),
                    client_source_type: $('select[name="client_source_type"]').val(),
                    status: $('select[name="status"]').val(),
                    has_invoice: $('select[name="has_invoice"]').val(),
                    has_musaned_transfer: $('select[name="has_musaned_transfer"]').val(),
                    from_date: $('input[name="from_date"]').val(),
                    to_date: $('input[name="to_date"]').val()
                };
            }

            const table = $('#ingaz-delegations-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                pageLength: 25,
                searching: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ route('ingaz-delegations.index') }}",
                    type: 'GET',
                    data: function (d) {
                        $.extend(d, currentFilters());
                    }
                },
                columns: [
                    { data: 'id', name: 'id', className: 'text-nowrap fw-bold' },
                    { data: 'delegation_date_gregorian', name: 'delegation_date_gregorian', className: 'text-nowrap' },
                    { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
                    { data: 'client', name: 'client', orderable: false, searchable: false },
                    { data: 'marketer_name', name: 'marketer_name', orderable: false, searchable: false },
                    { data: 'msaned_transfer', name: 'msaned_contract_number' },
                    { data: 'country_name', name: 'country_name', orderable: false, searchable: false },
                    { data: 'profession_name', name: 'profession_name', orderable: false, searchable: false },
                    { data: 'invoice', name: 'invoice', orderable: false, searchable: false },
                    { data: 'status_control', name: 'status', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-nowrap' }
                ],
                columnDefs: [
                    { targets: 0, width: '60px' }
                ],
                language: {
                    url: @json('//cdn.datatables.net/plug-ins/1.13.6/i18n/' . (app()->getLocale() === 'ar' ? 'ar' : 'en-GB') . '.json')
                }
            });

            $('#delegations-filter-form').on('submit', function (event) {
                event.preventDefault();
                table.ajax.reload();
            });

            function exportWith(baseUrl, newTab) {
                const params = $.param(currentFilters());
                const url = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + params;

                if (newTab) {
                    window.open(url, '_blank');
                } else {
                    window.location.href = url;
                }
            }

            $('#export-excel-btn').on('click', function () {
                exportWith("{{ route('ingaz-delegations.export.excel') }}", false);
            });

            $('#export-pdf-btn').on('click', function () {
                exportWith("{{ route('ingaz-delegations.export.pdf') }}", true);
            });

            $(document).on('focus', '.delegation-status-select', function () {
                $(this).data('previous', $(this).val());
            });

            $(document).on('change', '.delegation-status-select', function () {
                const $select = $(this);
                const previousValue = $select.data('previous') ?? $select.val();

                $.ajax({
                    url: $select.data('url'),
                    type: 'PATCH',
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: $select.val()
                    },
                    success: function (response) {
                        $select.data('previous', $select.val());

                        if (typeof successToster === 'function') {
                            successToster(@json(__('delegations.success')), response.message || @json(__('delegations.status_updated')));
                        }

                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        $select.val(previousValue);

                        if (typeof errorToster === 'function') {
                            errorToster(@json(__('delegations.error')), xhr.responseJSON?.message || @json(__('delegations.status_failed')));
                        }
                    }
                });
            });
        });
    </script>
@endsection
