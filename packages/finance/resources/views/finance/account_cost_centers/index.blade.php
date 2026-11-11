@extends('click.layout.indexs.index')
@section('style')
    <style>
        .cost-center-report {
            --cc-primary: #1f6f78;
            --cc-primary-soft: #e9f5f6;
            --cc-border: #dfe7ea;
            --cc-muted: #6c7a86;
            --cc-surface: #ffffff;
            --cc-surface-soft: #f7fafb;
        }

        .cost-center-report .filter-panel,
        .cost-center-report .result-panel {
            background: var(--cc-surface);
            border: 1px solid var(--cc-border);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(31, 62, 71, 0.06);
        }

        .cost-center-report .filter-panel {
            padding: 18px;
            margin-block: 14px 18px;
        }

        .cost-center-report .filter-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--cc-primary);
            font-weight: 700;
            margin-bottom: 14px;
        }

        .cost-center-report .form-label {
            color: #263b44;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .cost-center-report .form-control,
        .cost-center-report .select2-container .select2-selection--single {
            min-height: 38px;
            border-color: var(--cc-border);
            border-radius: 6px;
        }

        .cost-center-report .select2-container {
            width: 100% !important;
        }

        .cost-center-report .filter-actions {
            display: flex;
            align-items: end;
            gap: 8px;
            height: 100%;
        }

        .cost-center-report .btn-search-report {
            min-height: 38px;
            padding-inline: 18px;
            font-weight: 700;
            background: var(--cc-primary);
            border-color: var(--cc-primary);
        }

        .cost-center-report .result-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: var(--cc-surface-soft);
            border: 1px solid var(--cc-border);
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .cost-center-report .result-toolbar-title {
            color: #263b44;
            font-weight: 700;
        }

        .cost-center-report .result-toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .cost-center-report #account_list {
            margin: 0;
        }

        .cost-center-report .result-panel {
            padding: 14px;
            overflow: visible;
        }

        .cost-center-report .table-responsive {
            border: 1px solid var(--cc-border);
            border-radius: 8px;
            max-height: 68vh;
            overflow-x: auto;
            overflow-y: auto;
            width: 100%;
        }

        .cost-center-report .statement-table {
            margin-bottom: 0;
            min-width: 980px;
            table-layout: fixed;
            vertical-align: middle;
        }

        .cost-center-report .statement-table thead th {
            border-bottom: 1px solid var(--cc-border);
            font-size: 12px;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 2;
            white-space: nowrap;
            text-align: center;
        }

        .cost-center-report .statement-table tbody td {
            color: #263b44;
            line-height: 1.7;
            word-break: break-word;
        }

        .cost-center-report .statement-table tbody tr:hover td {
            background-color: #f4fbfc;
        }

        .cost-center-report .statement-table .statement-cell {
            min-width: 220px;
            white-space: normal;
        }

        .cost-center-report .statement-table .account-cell,
        .cost-center-report .statement-table .user-cell {
            white-space: normal;
        }

        .cost-center-report .statement-table th:last-child,
        .cost-center-report .statement-table td:last-child {
            position: sticky;
            left: 0;
            z-index: 1;
            background: inherit;
            box-shadow: 2px 0 0 var(--cc-border);
        }

        .cost-center-report .statement-table thead th:last-child {
            z-index: 3;
            background-color: #eef7f8;
        }

        .cost-center-report .statement-table tfoot td:last-child {
            background: #fff9e8;
        }

        .cost-center-report .statement-table .number-cell {
            direction: ltr;
            text-align: end;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .cost-center-report .statement-table tfoot td {
            background: #fff9e8;
            border-top: 2px solid #e4bf5b;
            color: #2d3840;
            font-weight: 800;
        }

        .cost-center-report .empty-state {
            margin: 0;
            border-radius: 8px;
        }

        .cost-center-report .report-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 220px;
            width: 100%;
        }

        .cost-center-report .loader {
            border: 5px solid #edf3f5;
            border-top: 5px solid var(--cc-primary);
            border-radius: 50%;
            width: 54px;
            height: 54px;
            animation: spin 0.9s linear infinite;
        }

        .cost-center-report .header {
            position: sticky;
            top: 0;
            z-index: 2;
            background-color: #eef7f8;
            color: #20343c;
        }

        .cost-center-report td {
            font-size: small;
        }

        .cost-center-report .report-scroll-hint {
            display: none;
            margin-top: 8px;
            color: var(--cc-muted);
            font-size: 12px;
            text-align: center;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 767.98px) {
            .cost-center-report .filter-panel {
                padding: 14px;
            }

            .cost-center-report .result-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .cost-center-report .result-toolbar-actions {
                justify-content: flex-end;
            }

            .cost-center-report .report-scroll-hint {
                display: block;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #account_list,
            #account_list * {
                visibility: visible;
            }

            #account_list {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
                font-size: 12pt;
            }

            .sidebar {
                display: none !important;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    @php
        $filters = request()->only(['cost_center_id', 'account_id', 'from_date', 'to_date', 'type']);
    @endphp

    <div class="cost-center-report">
        <div class="filter-panel">
            <div class="filter-title">
                <i class="fa fa-filter"></i>
                <span>{{ __('finance::finance.Search') }}</span>
            </div>

            <div class="row g-3 align-items-end">
                @if (!empty($nationalityNodes))
                    <div class="col-lg-2 col-md-6">
                        <label for="nationality_filter" class="form-label">الجنسية</label>
                        <select id="nationality_filter" class="form-control">
                            <option value="">كل الجنسيات</option>
                            @foreach ($nationalityNodes as $node)
                                <option value="{{ $node['id'] }}">{{ $node['name'] }} ({{ $node['children'] }} عقد)</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="{{ !empty($nationalityNodes) ? 'col-lg-2' : 'col-lg-3' }} col-md-6">
                    <label for="cost_center_id" class="form-label"> {{ __('finance::finance.fn-cost-centers') }}</label>
                    <select name="cost_center_id" id="cost_center_id" class="form-control show-tick ms select2 search_keys">
                        {!! showSelectElement($cost_centers, 'title', $filters['cost_center_id'] ?? null) !!}
                    </select>
                </div>

                <div class="{{ !empty($nationalityNodes) ? 'col-lg-2' : 'col-lg-3' }} col-md-6">
                    <label for="account_id" class="form-label"> {{ __('finance::finance.account') }}</label>
                    <select name="account_id" id="account_id" class="form-control show-tick ms select2 search_keys">
                        {!! showSelectElement($accounts, 'name', $filters['account_id'] ?? null) !!}
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label for="from_date" class="form-label"> {{ __('finance::finance.From Date') }}</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                        class="form-control search_keys" id="from_date">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label for="to_date" class="form-label"> {{ __('finance::finance.To Date') }}</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? date('Y-m-d', time()) }}"
                        class="form-control search_keys" id="to_date">
                </div>

                <div class="col-lg-1 col-md-6">
                    <label for="type" class="form-label">{{ __('finance::finance.Transaction Type') }}</label>
                    <select name="type" id="type" class="form-control search_keys">
                        <option value="both" {{ ($filters['type'] ?? 'both') === 'both' ? 'selected' : '' }}>
                            {{ __('finance::finance.All') }}</option>
                        <option value="debtor" {{ ($filters['type'] ?? '') === 'debtor' ? 'selected' : '' }}>
                            {{ __('finance::finance.debtor') }}</option>
                        <option value="creditor" {{ ($filters['type'] ?? '') === 'creditor' ? 'selected' : '' }}>
                            {{ __('finance::finance.creditor') }}</option>
                    </select>
                </div>

                <div class="col-lg-1 col-md-6">
                    <div class="filter-actions">
                        <button type="button" id="search_button" class="btn btn-success btn-search-report w-100">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="result-toolbar print-and-excel-buttons d-none">
            <div class="result-toolbar-title">
                <i class="fa fa-list-alt"></i>
                <span>{{ $bladeTitle }}</span>
            </div>
            <div class="result-toolbar-actions">
                <button onclick="window.print()" class="btn btn-sm btn-primary" title="{{ __('finance::finance.print') }}">
                    <i class="fa fa-print"></i>
                </button>
                <button id="toExcel" class="btn btn-sm btn-info" title="Excel">
                    <i class="fa fa-file-excel-o"></i>
                </button>
            </div>
        </div>

        <div class="result-panel">
            <div id="account_list"></div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('.select2').select2();

        function searchCostCenterStatement() {
            var data = $(".search_keys").serialize();
            let url = '{{ $ajaxRoute }}';

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function() {
                    $("#account_list").html('<div class="report-loading">' +
                        '  <div class="loader"></div>' +
                        '</div>');
                },
                success: function(result) {
                    $("#account_list").html(result.data.html);
                    if (result.data.count > 0) {
                        $('.print-and-excel-buttons').removeClass('d-none');
                    } else {
                        $('.print-and-excel-buttons').addClass('d-none');
                    }
                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault()
            searchCostCenterStatement();
        });

        // فلتر الجنسية: يختار عقدة الدولة في «مراكز التكلفة» (فيجمع الكشف كل عقودها)، ويضيّق القائمة
        // على عقود الدولة لمن يريد عقداً بعينه. «كل الجنسيات» يرجع القائمة كاملة.
        (function() {
            const centersByNationality = @json($nationalityCenterIds ?? []);
            const $nationality = $('#nationality_filter');
            const $center = $('#cost_center_id');
            if (!$nationality.length) {
                return;
            }
            const $allOptions = $center.find('option').clone();

            $nationality.on('change', function() {
                const nodeId = $(this).val();
                const keep = nodeId ? (centersByNationality[nodeId] || []).map(String) : null;

                $center.empty().append($allOptions.clone().filter(function() {
                    return !keep || this.value === '' || keep.includes(this.value);
                }));
                $center.val(nodeId || '').trigger('change');

                if (nodeId) {
                    searchCostCenterStatement();
                } else {
                    $("#account_list").empty();
                    $('.print-and-excel-buttons').addClass('d-none');
                }
            });
        })();
    </script>
    <script src="{{ asset('plugins') }}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        if (button) {
            button.addEventListener('click', function() {
                var table = document.querySelector('#simpleTable4');
                if (!table) {
                    return;
                }
                TableToExcel.convert(table);
            });
        }

        @if (request()->filled('cost_center_id') || request()->boolean('auto_search'))
            searchCostCenterStatement();
        @endif
    </script>
@endsection
