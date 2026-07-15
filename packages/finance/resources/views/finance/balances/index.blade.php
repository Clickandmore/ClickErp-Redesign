@extends('click.layout.indexs.index')

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('style')
    <style>
        .balances-dashboard {
            --balance-navy: #172554;
            --balance-purple: #6d28d9;
            --balance-debit: #2563eb;
            --balance-credit: #f59e0b;
            --balance-surface: #f6f7fb;
        }

        .balances-toolbar {
            padding: 18px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #172554 0%, #312e81 100%);
            border-radius: 16px;
            box-shadow: 0 10px 28px rgba(23, 37, 84, .18);
        }

        .balances-toolbar-grid {
            display: grid;
            grid-template-columns: minmax(240px, 2fr) repeat(2, minmax(150px, 1fr)) auto;
            gap: 12px;
            align-items: end;
        }

        .balances-field label {
            display: block;
            margin-bottom: 6px;
            color: #e0e7ff;
            font-size: 12px;
            font-weight: 700;
        }

        .balances-field .form-control {
            min-height: 43px;
            background: rgba(255, 255, 255, .98);
            border: 0;
            border-radius: 9px;
        }

        .balances-actions {
            display: flex;
            gap: 7px;
            flex-wrap: nowrap;
        }

        .balances-actions .btn {
            min-width: 43px;
            min-height: 43px;
            border: 0;
            border-radius: 9px;
        }

        .balance-summary-card,
        .balance-table-card {
            background: #fff;
            border: 1px solid #e8eaf2;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(23, 37, 84, .06);
        }

        .balance-summary-card {
            position: relative;
            min-height: 118px;
            padding: 17px;
            overflow: hidden;
        }

        .balance-summary-card::after {
            content: '';
            position: absolute;
            width: 70px;
            height: 70px;
            inset-inline-end: -20px;
            bottom: -24px;
            background: currentColor;
            border-radius: 50%;
            opacity: .08;
        }

        .balance-summary-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .balance-summary-value {
            margin-top: 8px;
            color: #172554;
            font-size: 22px;
            font-weight: 800;
        }

        .balance-summary-note {
            display: block;
            margin-top: 5px;
            color: #9ca3af;
            font-size: 11px;
        }

        .balance-table-card {
            overflow: hidden;
        }

        .balance-table-scroll {
            max-height: none;
            overflow-x: auto;
            overflow-y: visible;
        }

        .balance-table-card .table {
            margin: 0;
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .balance-table-card thead th {
            padding: 13px 10px;
            color: #374151;
            background: #f3f4f8;
            border: 0;
            font-size: 12px;
            white-space: nowrap;
        }

        .balance-table-card thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 8;
        }

        .balance-period-switcher {
            display: flex;
            gap: 7px;
            padding: 12px 14px;
            overflow-x: auto;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .balance-period-button {
            min-width: 145px;
            padding: 9px 12px;
            color: #475569;
            background: #fff;
            border: 1px solid #dbe1ea;
            border-radius: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .balance-period-button.is-active {
            color: #fff;
            background: #312e81;
            border-color: #312e81;
            box-shadow: 0 5px 12px rgba(49, 46, 129, .2);
        }

        .balance-active-period {
            padding: 10px 14px;
            color: #312e81;
            background: #eef2ff;
            font-size: 12px;
            font-weight: 800;
        }

        .balance-table-card tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-color: #f0f1f5;
            font-size: 13px;
        }

        .balance-parent-row td {
            background: #eef0f6 !important;
            font-weight: 800;
        }

        .balance-level-mark {
            display: inline-block;
            width: 6px;
            height: 25px;
            margin-inline-end: 7px;
            background: #6d28d9;
            border-radius: 5px;
            vertical-align: middle;
        }

        .balance-direction {
            display: inline-block;
            min-width: 48px;
            padding: 4px 7px;
            text-align: center;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
        }

        .balance-direction--debtor {
            color: #1e40af;
            background: #dbeafe;
        }

        .balance-direction--creditor {
            color: #92400e;
            background: #fef3c7;
        }

        .balance-report-heading {
            padding: 19px 20px;
            color: #fff;
            background: linear-gradient(135deg, #172554 0%, #6d28d9 100%);
        }

        .balance-report-heading h4 {
            margin: 0 0 5px;
            color: #fff;
        }

        .balance-report-heading p {
            margin: 0;
            color: #ddd6fe;
            font-size: 12px;
        }

        .balance-account-link {
            color: #312e81 !important;
            font-weight: 800;
        }

        .balance-movement-track {
            display: flex;
            width: 150px;
            height: 9px;
            overflow: hidden;
            background: #e5e7eb;
            border-radius: 20px;
        }

        .balance-movement-debit { background: var(--balance-debit); }
        .balance-movement-credit { background: var(--balance-credit); }

        .balance-movement-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            color: #6b7280;
            font-size: 10px;
        }

        .balance-net {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 8px;
            font-weight: 800;
            white-space: nowrap;
        }

        .balance-net--debtor {
            color: #1e40af;
            background: #dbeafe;
        }

        .balance-net--creditor {
            color: #92400e;
            background: #fef3c7;
        }

        .balances-loader {
            width: 44px;
            height: 44px;
            margin: 55px auto;
            border: 5px solid #e5e7eb;
            border-top-color: #6d28d9;
            border-radius: 50%;
            animation: balances-spin .8s linear infinite;
        }

        @keyframes balances-spin { to { transform: rotate(360deg); } }

        @media (max-width: 991.98px) {
            .balances-toolbar-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 575.98px) {
            .balances-toolbar-grid { grid-template-columns: 1fr; }
            .balances-actions { flex-wrap: wrap; }
        }

        @media print {
            body * { visibility: hidden; }
            #balances_result, #balances_result * { visibility: visible; }
            #balances_result { position: absolute; inset: 0; width: 100%; }
            .balances-no-print { display: none !important; }
            .balance-summary-card, .balance-table-card { box-shadow: none; }
        }
    </style>
@endsection

@section('content')
    <div class="balances-dashboard" dir="rtl">
        <div class="balances-toolbar balances-no-print">
            <div class="balances-toolbar-grid">
                <div class="balances-field">
                    <label for="balances_keyword">بحث في الحسابات</label>
                    <input type="search" id="balances_keyword" name="keyword"
                           value="{{ $filters['keyword'] ?? '' }}" class="form-control balance-filter"
                           placeholder="اسم الحساب أو رقمه أو الحساب الأب">
                </div>
                <div class="balances-field">
                    <label for="balances_from_date">{{ __('finance::finance.from_date') }}</label>
                    <input type="date" id="balances_from_date" name="from_date"
                           value="{{ $filters['from_date'] ?? '' }}" class="form-control balance-filter">
                </div>
                <div class="balances-field">
                    <label for="balances_to_date">{{ __('finance::finance.to_date') }}</label>
                    <input type="date" id="balances_to_date" name="to_date"
                           value="{{ $filters['to_date'] ?? '' }}" class="form-control balance-filter">
                </div>
                <div class="balances-actions">
                    <button type="button" id="balances_search" class="btn btn-light" title="بحث"><i class="fa fa-search"></i></button>
                    <button type="button" id="balances_reset" class="btn btn-secondary" title="إعادة ضبط"><i class="fa fa-refresh"></i></button>
                    <button type="button" id="balances_print" class="btn btn-info" title="طباعة"><i class="fa fa-print"></i></button>
                    <button type="button" id="balances_excel" class="btn btn-warning" title="Excel"><i class="fa fa-file-excel-o"></i></button>
                </div>
            </div>
        </div>

        <div id="balances_result">
            @include('finance::finance.balances.result', [
                'accounts' => $accounts,
                'filters' => $filters,
                'reportType' => $reportType,
            ])
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('plugins') }}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        (function () {
            const resultContainer = $('#balances_result');
            const reportUrl = @json($dataTableRoute);

            function loadBalances(url) {
                const requestUrl = url || reportUrl;
                $.ajax({
                    url: requestUrl,
                    type: 'GET',
                    data: url ? {} : $('.balance-filter').serialize(),
                    beforeSend: function () {
                        $('#balances_search').prop('disabled', true);
                        resultContainer.html('<div class="balance-table-card"><div class="balances-loader"></div></div>');
                    },
                    success: function (result) {
                        resultContainer.html(result.data.html);
                    },
                    error: function (errorObj) {
                        handleErrorResponce(errorObj);
                    },
                    complete: function () {
                        $('#balances_search').prop('disabled', false);
                    }
                });
            }

            function formatBalanceValue(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(Number(value || 0));
            }

            function showMovementPeriod(periodIndex, button) {
                resultContainer.find('.balance-movement-row').each(function () {
                    const row = $(this);
                    const movements = row.data('m');
                    const values = movements && movements[periodIndex] ? movements[periodIndex] : null;

                    if (!values) {
                        return;
                    }

                    row.find('.balance-cell-debtor').text(formatBalanceValue(values[0]));
                    row.find('.balance-cell-creditor').text(formatBalanceValue(values[1]));
                    row.find('.balance-cell-total').text(formatBalanceValue(values[2]));

                    const statusCell = row.find('.balance-cell-status');
                    if (values[3] === 'd' || values[3] === 'c') {
                        const status = values[3] === 'd' ? 'debtor' : 'creditor';
                        const label = values[3] === 'd' ? 'مدين' : 'دائن';
                        statusCell.html('<span class="balance-direction balance-direction--' + status + '">' + label + '</span>');
                    } else {
                        statusCell.html('<span class="text-muted">—</span>');
                    }
                });

                resultContainer.find('.balance-period-button').removeClass('is-active');
                button.addClass('is-active');
                resultContainer.find('[data-active-period-label]').text(
                    button.text().trim() + ' — ' + (button.data('subtitle') || '')
                );
            }

            $('#balances_search').on('click', function () { loadBalances(); });
            $('#balances_keyword').on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadBalances();
                }
            });
            $('#balances_reset').on('click', function () {
                $('#balances_keyword, #balances_from_date, #balances_to_date').val('');
                loadBalances();
            });
            resultContainer.on('click', '.balance-period-button', function () {
                const button = $(this);
                showMovementPeriod(Number(button.data('period-index')), button);
            });
            $('#balances_print').on('click', function () { window.print(); });
            $('#balances_excel').on('click', function () {
                const table = document.querySelector('#balancesTable');
                if (table && typeof TableToExcel !== 'undefined') {
                    TableToExcel.convert(table, { name: @json($exportFileName) });
                }
            });
        })();
    </script>
@endsection
