@extends('click.layout.indexs.index')

@section('style')
    <style>
        .tax-report-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .tax-report-filter,
        .tax-report-actions,
        .tax-report-loading {
            background: #fff;
            border: 1px solid #d7e3e7;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(49, 90, 99, 0.07);
        }

        .tax-report-filter {
            padding: 1rem;
        }

        .tax-report-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .tax-report-field {
            grid-column: span 3;
        }

        .tax-report-field label {
            font-weight: 700;
            color: #315a63;
        }

        .tax-report-field .form-control {
            min-height: 44px;
            border-radius: 8px;
        }

        .tax-report-actions {
            display: none;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
        }

        .tax-report-actions.is-visible {
            display: flex;
        }

        .tax-report-loading {
            padding: 2rem;
            text-align: center;
            color: #6b7280;
        }

        .loader {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            border: 8px solid #edf2f4;
            border-top-color: #4c818b;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 991.98px) {
            .tax-report-field {
                grid-column: span 6;
            }
        }

        @media (max-width: 575.98px) {
            .tax-report-field {
                grid-column: span 12;
            }

            .tax-report-actions {
                align-items: stretch;
                flex-direction: column;
            }
        }

        @media print {
            body.tax-report-printing * {
                visibility: hidden;
            }

            body.tax-report-printing #print_section,
            body.tax-report-printing #print_section * {
                visibility: visible;
            }

            body.tax-report-printing #print_section {
                position: absolute;
                inset: 0;
                width: 100%;
                background: #fff;
            }

            body.tax-report-printing .tax-report-head,
            body.tax-report-printing .tax-summary-grid {
                break-inside: avoid;
            }

            body.tax-report-printing table {
                width: 100%;
                border-collapse: collapse;
            }

            body.tax-report-printing th,
            body.tax-report-printing td {
                border: 1px solid #444 !important;
                padding: 5px 6px !important;
                text-align: center;
                font-size: 11px;
                vertical-align: middle;
            }
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
    <div class="tax-report-shell">
        <form id="taxInvoiceFilters" class="tax-report-filter">
            <div class="tax-report-grid">
                <div class="tax-report-field">
                    <label for="from_date">{{ __('finance::finance.From Date') }}</label>
                    <input type="date" name="from_date" id="from_date" class="form-control search_keys"
                        value="{{ $filters['from_date'] ?? '' }}">
                </div>

                <div class="tax-report-field">
                    <label for="to_date">{{ __('finance::finance.To Date') }}</label>
                    <input type="date" name="to_date" id="to_date" class="form-control search_keys"
                        value="{{ $filters['to_date'] ?? date('Y-m-d') }}">
                </div>

                <div class="tax-report-field">
                    <label for="source">نوع الفواتير</label>
                    <select name="source" id="source" class="form-control search_keys">
                        <option value="all" @selected(($filters['source'] ?? 'all') === 'all')>الكل</option>
                        <option value="recruitment" @selected(($filters['source'] ?? '') === 'recruitment')>الاستقدام والتفويض</option>
                        <option value="rental" @selected(($filters['source'] ?? '') === 'rental')>الإيجار</option>
                    </select>
                </div>

                <div class="tax-report-field">
                    <button type="button" id="search_button" class="btn btn-success w-100">
                        <i class="fa fa-search"></i>
                        {{ __('finance::finance.Search') }}
                    </button>
                </div>
            </div>
        </form>

        <div id="taxReportActions" class="tax-report-actions">
            <span>التصدير والطباعة يعتمدان على النتيجة الحالية.</span>
            <div>
                <button type="button" id="printReport" class="btn btn-outline-success">
                    <i class="fa fa-print"></i>
                    طباعة
                </button>
                <button type="button" id="toPdf" class="btn btn-danger">
                    <i class="fa fa-file-pdf-o"></i>
                    PDF
                </button>
                <button type="button" id="toExcel" class="btn btn-info">
                    <i class="fa fa-file-excel-o"></i>
                    Excel
                </button>
            </div>
        </div>

        <div id="account_list"></div>
    </div>
@endsection

@section('js')
    <script>
        const filtersContainer = $('#taxInvoiceFilters');
        const resultsContainer = $('#account_list');
        const actionsBar = $('#taxReportActions');
        const reportUrl = @json($dataTableRoute);
        const exportRouteTemplate = @json($exportRouteTemplate);

        function renderLoadingState() {
            resultsContainer.html(`
                <div class="tax-report-loading">
                    <div class="loader"></div>
                    <div>جاري تجهيز تقرير الفواتير الضريبية...</div>
                </div>
            `);
        }

        function loadReport() {
            $.ajax({
                url: reportUrl,
                type: 'GET',
                data: filtersContainer.find('.search_keys').serialize(),
                beforeSend: function() {
                    renderLoadingState();
                },
                success: function(result) {
                    resultsContainer.html(result.data.html);
                    actionsBar.toggleClass('is-visible', result.data.count > 0);
                },
                error: function(errorObj) {
                    handleErrorResponce(errorObj);
                }
            });
        }

        $('#search_button').on('click', function(e) {
            e.preventDefault();
            loadReport();
        });

        function downloadReport(format) {
            const query = filtersContainer.find('.search_keys').serialize();
            const url = exportRouteTemplate.replace('__FORMAT__', format);

            window.location.href = url + (query ? '?' + query : '');
        }

        $('#toExcel').on('click', function() {
            downloadReport('xlsx');
        });

        $('#toPdf').on('click', function() {
            downloadReport('pdf');
        });

        $('#printReport').on('click', function() {
            const section = document.getElementById('print_section');

            if (!section) {
                return;
            }

            document.body.classList.add('tax-report-printing');
            window.print();
            setTimeout(function() {
                document.body.classList.remove('tax-report-printing');
            }, 500);
        });

        loadReport();
    </script>
@endsection
