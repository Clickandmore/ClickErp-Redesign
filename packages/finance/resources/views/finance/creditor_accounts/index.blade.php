@extends('click.layout.indexs.index')

@section('style')
    <style>
        .creditor-report {
            --creditor-color: #315a63;
            --creditor-soft: #eef5f5;
        }

        .creditor-filter-card,
        .creditor-result-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(31, 67, 74, .08);
        }

        .creditor-filter-card {
            border-top: 3px solid var(--creditor-color);
        }

        .creditor-filter-card .form-label {
            color: #34495e;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .creditor-filter-card .form-control {
            min-height: 42px;
            border-radius: 8px;
        }

        .creditor-filter-actions {
            display: flex;
            align-items: end;
            gap: 8px;
            height: 100%;
            flex-wrap: wrap;
        }

        .creditor-filter-actions .btn {
            min-height: 42px;
            border-radius: 8px;
        }

        .creditor-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            padding: 7px 11px;
            border: 1px solid transparent;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
        }

        .creditor-status-badge--nature-debtor {
            color: #991b1b !important;
            background-color: #fee2e2 !important;
            border-color: #fca5a5 !important;
        }

        .creditor-status-badge--nature-creditor {
            color: #1e3a8a !important;
            background-color: #dbeafe !important;
            border-color: #93c5fd !important;
        }

        .creditor-status-badge--actual-creditor {
            color: #14532d !important;
            background-color: #dcfce7 !important;
            border-color: #86efac !important;
        }

        .creditor-status-badge--actual-debtor {
            color: #9a3412 !important;
            background-color: #ffedd5 !important;
            border-color: #fdba74 !important;
        }

        .creditor-loader {
            width: 46px;
            height: 46px;
            margin: 55px auto;
            border: 5px solid #e7eeee;
            border-top-color: var(--creditor-color);
            border-radius: 50%;
            animation: creditor-spin .8s linear infinite;
        }

        @keyframes creditor-spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 767.98px) {
            .creditor-filter-actions {
                margin-top: 6px;
            }

        }

        @media print {
            body * { visibility: hidden; }
            #account_list, #account_list * { visibility: visible; }
            #account_list {
                position: absolute;
                inset: 0;
                width: 100%;
            }
            .creditor-no-print { display: none !important; }
            .creditor-result-card { box-shadow: none; }
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
    <div class="creditor-report" dir="rtl">
        <div class="card creditor-filter-card mb-3 creditor-no-print">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label for="creditor_keyword" class="form-label">بحث عن حساب</label>
                        <input type="search" id="creditor_keyword" name="keyword"
                               value="{{ $filters['keyword'] ?? '' }}" class="form-control search_keys"
                               placeholder="اسم الحساب أو رقمه أو الحساب الأب">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label for="creditor_from_date" class="form-label">{{ __('finance::finance.from_date') }}</label>
                        <input type="date" id="creditor_from_date" name="from_date"
                               value="{{ $filters['from_date'] ?? '' }}" class="form-control search_keys">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label for="creditor_to_date" class="form-label">{{ __('finance::finance.to_date') }}</label>
                        <input type="date" id="creditor_to_date" name="to_date"
                               value="{{ $filters['to_date'] ?? '' }}" class="form-control search_keys">
                    </div>
                    <div class="col-lg-4">
                        <div class="creditor-filter-actions">
                            <button type="button" id="search_button" class="btn btn-info">
                                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
                            </button>
                            <button type="button" id="reset_button" class="btn btn-light border" title="إعادة ضبط البحث">
                                <i class="fa fa-refresh"></i> إعادة ضبط
                            </button>
                            <button type="button" id="print_button" class="btn btn-primary">
                                <i class="fa fa-print"></i> {{ __('finance::finance.print') }}
                            </button>
                            <button type="button" id="toExcel" class="btn btn-warning">
                                <i class="fa fa-file-excel"></i> {{ __('finance::finance.toExcel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="account_list">
            @include($resultView ?? 'finance::finance.creditor_accounts.result', ['accounts' => $accounts, 'filters' => $filters])
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/@linways/table-to-excel@1.0.4/dist/tableToExcel.min.js"></script>
    <script>
        (function () {
            const list = $('#account_list');
            const searchUrl = @json($dataTableRoute);

            function loadAccounts() {
                $.ajax({
                    url: searchUrl,
                    type: 'GET',
                    data: $('.search_keys').serialize(),
                    beforeSend: function () {
                        $('#search_button').prop('disabled', true);
                        list.html('<div class="card creditor-result-card"><div class="card-body"><div class="creditor-loader"></div></div></div>');
                    },
                    success: function (result) {
                        list.html(result.data.html);
                    },
                    error: function (errorObj) {
                        handleErrorResponce(errorObj);
                    },
                    complete: function () {
                        $('#search_button').prop('disabled', false);
                    }
                });
            }

            $('#search_button').on('click', loadAccounts);
            $('#creditor_keyword').on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadAccounts();
                }
            });
            $('#reset_button').on('click', function () {
                $('.search_keys').val('');
                loadAccounts();
            });
            $('#print_button').on('click', function () {
                window.print();
            });
            $('#toExcel').on('click', function () {
                const table = document.querySelector('#toExcelTable');
                if (table && typeof TableToExcel !== 'undefined') {
                    TableToExcel.convert(table, { name: @json($exportFileName ?? 'accounts-report.xlsx') });
                }
            });
        })();
    </script>
@endsection
