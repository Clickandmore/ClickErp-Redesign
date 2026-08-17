@extends('click.layout.indexs.index')
@section('style')
    <style>
        :root {
            --trial-primary: #315a63;
            --trial-accent: #f7941d;
            --trial-surface: #ffffff;
            --trial-soft: #f7fafb;
            --trial-border: #dce7ea;
            --trial-text: #1f2937;
            --trial-muted: #6b7280;
            --trial-shadow: 0 12px 28px rgba(49, 90, 99, 0.08);
        }

        .loader {
            border: 10px solid #edf2f4;
            border-top: 10px solid var(--trial-accent);
            border-radius: 50%;
            width: 84px;
            height: 84px;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .trial-balance-page {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            color: var(--trial-text);
        }

        .trial-panel,
        .trial-actions,
        .trial-result {
            background: var(--trial-surface);
            border: 1px solid var(--trial-border);
            border-radius: 8px;
            box-shadow: var(--trial-shadow);
        }

        .trial-panel {
            padding: 1rem;
        }

        .trial-panel__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 0.85rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--trial-border);
        }

        .trial-panel__title {
            margin: 0;
            color: var(--trial-primary);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .trial-year {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            min-height: 34px;
            padding: 0.25rem 0.7rem;
            border-radius: 6px;
            background: var(--trial-soft);
            color: var(--trial-primary);
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid var(--trial-border);
        }

        .trial-filter-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 0.9rem;
            align-items: end;
        }

        .trial-field {
            grid-column: span 3;
        }

        .trial-field--small {
            grid-column: span 2;
        }

        .trial-field--action {
            grid-column: span 1;
        }

        .trial-field label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--trial-primary);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .trial-field .form-control {
            min-height: 42px;
            border-color: var(--trial-border);
            border-radius: 6px;
            box-shadow: none;
        }

        .trial-field .form-control:focus {
            border-color: rgba(247, 148, 29, 0.65);
            box-shadow: 0 0 0 0.15rem rgba(247, 148, 29, 0.12);
        }

        .trial-search-btn {
            width: 100%;
            min-height: 42px;
            border-radius: 6px;
            font-weight: 700;
            white-space: nowrap;
        }

        .trial-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            padding: 0.75rem;
        }

        .trial-actions .btn {
            min-height: 38px;
            border-radius: 6px;
            font-weight: 700;
        }

        .trial-result {
            min-height: 260px;
            overflow: hidden;
        }

        .trial-table-wrap {
            width: 100%;
            overflow: auto;
        }

        .trial-table {
            min-width: 980px;
            margin: 0;
            color: var(--trial-text);
        }

        .trial-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
            background: var(--trial-primary);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.22);
            font-size: 0.88rem;
            text-align: center;
            white-space: nowrap;
        }

        .trial-table tbody td {
            vertical-align: middle;
            border-color: var(--trial-border);
            background: #fff;
        }

        .trial-table tbody tr:nth-child(even) td {
            background: var(--trial-soft);
        }

        .trial-table .trial-code {
            width: 110px;
            color: var(--trial-primary);
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .trial-table .trial-name {
            min-width: 260px;
            font-weight: 700;
        }

        .trial-table .trial-number {
            min-width: 120px;
            direction: ltr;
            text-align: end;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .trial-total-row td {
            position: sticky;
            bottom: 0;
            z-index: 1;
            background: #fff4e6 !important;
            color: #4f3420;
            font-weight: 800;
        }

        .trial-result__empty {
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--trial-muted);
            background: var(--trial-soft);
            text-align: center;
            padding: 1rem;
        }

        .trial-loading {
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--trial-soft);
        }

        .trial-print-header {
            display: none;
        }

        td {
            font-size: small;
        }

        @media (max-width: 991.98px) {
            .trial-field,
            .trial-field--small,
            .trial-field--action {
                grid-column: span 6;
            }
        }

        @media (max-width: 575.98px) {
            .trial-panel__head,
            .trial-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .trial-field,
            .trial-field--small,
            .trial-field--action {
                grid-column: span 12;
            }
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            html,
            body {
                width: auto !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .layout-1 .wrapper,
            .layout-1 .wrapper .page-body,
            .layout-1 .wrapper .container-fluid,
            .layout-1 .wrapper .card,
            .layout-1 .wrapper .card-body,
            .trial-balance-page {
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                background: transparent !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            body * {
                visibility: hidden;
            }

            #result,
            #result * {
                visibility: visible;
            }

            #result {
                position: absolute;
                top: 0;
                right: 0;
                left: 0;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none;
                border: 0;
                overflow: visible !important;
                min-height: 0;
            }

            .trial-print-header {
                display: grid !important;
                grid-template-columns: 1fr 1.5fr 1fr;
                align-items: start;
                gap: 8px;
                margin-bottom: 8px;
                padding-bottom: 6px;
                color: #000;
                font-size: 10.5px;
                line-height: 1.4;
                border-bottom: 1px solid #dce7ea;
            }

            .trial-print-header__title {
                margin: 0;
                font-size: 12px;
                font-weight: 800;
                text-align: center;
            }

            .trial-print-header__date {
                direction: ltr;
                text-align: left;
                white-space: nowrap;
            }

            .trial-print-header__period {
                text-align: right;
            }

            .trial-table-wrap {
                width: 100% !important;
                max-width: 100% !important;
                overflow: visible !important;
            }

            .trial-table {
                width: 100% !important;
                min-width: 0 !important;
                table-layout: fixed;
                border-collapse: collapse !important;
                page-break-inside: auto;
            }

            .trial-table thead {
                display: table-header-group;
            }

            .trial-table tfoot {
                display: table-footer-group;
            }

            .trial-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .trial-table thead th,
            .trial-table tbody td,
            .trial-total-row td {
                position: static !important;
                padding: 5px 4px !important;
                font-size: 10px !important;
                line-height: 1.25 !important;
                white-space: normal !important;
                word-break: break-word;
            }

            .trial-table .trial-code {
                width: 9% !important;
                white-space: nowrap !important;
            }

            .trial-table .trial-name {
                width: 23% !important;
                min-width: 0 !important;
            }

            .trial-table .trial-number {
                min-width: 0 !important;
                white-space: nowrap !important;
                font-size: 9.5px !important;
            }

            .sidebar,
            .trial-panel,
            .trial-actions {
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
    <div class="trial-balance-page">
        @if ($report_period)
            <div class="trial-panel">
                <div class="trial-panel__head">
                    <h3 class="trial-panel__title">{{ $bladeTitle }}</h3>
                    <span class="trial-year">
                        <i class="fa fa-calendar"></i>
                        {{ $report_period->from_date }} / {{ $report_period->to_date }}
                    </span>
                </div>

                <div class="trial-filter-grid">
                    <div class="trial-field">
                        <label for="from_date">{{ __('finance::finance.from_date') }}</label>
                        <input type="date" name="from_date" class="form-control search_keys" id="from_date"
                            value="{{ $report_period->from_date }}">
                    </div>

                    <div class="trial-field">
                        <label for="to_date">{{ __('finance::finance.to_date') }}</label>
                        <input type="date" name="to_date" class="form-control search_keys" id="to_date"
                            value="{{ $report_period->to_date }}">
                    </div>

                    <div class="trial-field">
                        <label for="levelSelect">{{ __('finance::finance.select the level') }}</label>
                        <select name="level" id="levelSelect" class="form-control search_keys">
                            @foreach ($levels as $level)
                                <option value="{{ $level }}">
                                    {{ helperTrans('finance::finance.level') }}
                                    {{ $level }}
                                </option>
                            @endforeach
                            <option value="all">{{ __('finance::finance.all_levels') }}</option>
                        </select>
                    </div>

                    <div class="trial-field trial-field--small">
                        <label for="show_first_balance">{{ __('finance::finance.show first balance') }}</label>
                        <select name="show_first_balance" id="show_first_balance" class="form-control search_keys">
                            <option value="0">{{ helperTrans('finance::finance.No') }}</option>
                            <option value="1">{{ helperTrans('finance::finance.Yes') }}</option>
                        </select>
                    </div>

                    <div class="trial-field trial-field--action">
                        <button type="button" id="search_button" class="btn btn-success trial-search-btn">
                            <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info mb-0" style="color:red;">{{ trans('finance::finance.the year is not active') }}</div>
        @endif

        @if (@AdminGuardHasPerm('finance_trail_balance_export') || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
        <div class="trial-actions comprehensive-export-buttons">
            <a href="#" class="btn btn-success comprehensive-export"
                data-url="{{ route('finance.trail-balance.export', 'excel') }}">
                <i class="fa fa-file-excel-o"></i> تصدير شامل Excel
            </a>
            <a href="#" class="btn btn-danger comprehensive-export"
                data-url="{{ route('finance.trail-balance.export', 'pdf') }}" data-new-window="true">
                <i class="fa fa-file-pdf-o"></i> طباعة / حفظ PDF شامل
            </a>
        </div>
        <div class="trial-actions print-and-excel-buttons d-none">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa fa-print"></i>
                {{ helperTrans('dataTable.print') }}
            </button>
            <button id="toExcel" class="btn btn-warning text-white">
                <i class="fa fa-file-excel-o"></i>
                {{ helperTrans('dataTable.Export to Excel') }}
            </button>
        </div>
        @endif

        <div class="trial-result" id="result">
            <div class="trial-result__empty">{{ __('finance::finance.no_data_found') }}</div>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        let debounceTimer;
        function debounce(func, delay) {
            return function(...args) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const validateDateRangeDebounced = debounce(validateDateRange, 1300);

        $('.comprehensive-export').on('click', function(e) {
            e.preventDefault();
            if (!validateDateRange()) {
                return;
            }
            const query = $('.search_keys').serialize();
            const url = $(this).data('url') + (query ? '?' + query : '');
            if ($(this).data('new-window')) {
                window.open(url, '_blank', 'noopener');
                return;
            }
            window.location.href = url;
        });

        $('#from_date, #to_date').on('input', validateDateRangeDebounced);

        function validateDateRange() {
            const fromDate = new Date($('#from_date').val());
            const toDate = new Date($('#to_date').val());

            if (toDate < fromDate) {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.To Date cannot be earlier than From Date') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
                $('#to_date').val(''); // Clear the invalid input
                return false;
            }

            return true;
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault();
            if (!validateDateRange()) {
                return; // Exit if validation fails
            }
            var data = $(".search_keys").serialize();
            let url = '{{ $dataTableRoute }}';
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function() {
                    $("#result").html('<div class="trial-loading"><div class="loader"></div></div>');
                },
                success: function(result) {
                    console.log(result);
                    $("#result").html(result.data.html);
                    $(".print-and-excel-buttons").removeClass("d-none");
                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });

        // Excel export functionality
        $('#toExcel').on('click', function() {
            let table = $('#result').find('table').get(0);
            if (table) {
                let fromDate = $('#from_date').val().replace(/-/g, '');
                let toDate = $('#to_date').val().replace(/-/g, '');
                let filename = `financial_report_${fromDate}_to_${toDate}.xlsx`;

                let wb = XLSX.utils.table_to_book(table, {
                    sheet: "Sheet JS"
                });
                XLSX.writeFile(wb, filename);
            } else {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.No data available to export') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
            }
        });
    </script>
@endsection
