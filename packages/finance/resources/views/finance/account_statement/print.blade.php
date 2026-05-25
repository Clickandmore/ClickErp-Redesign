<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />

    <title>{{ $title ?? 'كشف حساب' }}</title>

    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css" />
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap"
        rel="stylesheet" />

    <style>
        :root {
            --statement-primary: #4c818b;
            --statement-primary-dark: #315a63;
            --statement-accent: #e7ca08;
            --statement-accent-soft: rgba(231, 202, 8, 0.14);
            --statement-surface: #ffffff;
            --statement-surface-alt: #f6fbfc;
            --statement-border: #d7e3e7;
            --statement-text: #1f2937;
            --statement-muted: #6b7280;
            --statement-shadow: 0 20px 40px rgba(49, 90, 99, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #edf2f4;
            color: var(--statement-text);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(76, 129, 139, 0.12);
        }

        .print-toolbar__copy {
            color: var(--statement-muted);
            line-height: 1.8;
        }

        .print-toolbar__actions {
            display: flex;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .print-toolbar__actions button {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 42px;
            padding: 0.65rem 1rem;
            border: 0;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .print-toolbar__actions .btn-print {
            background: var(--statement-primary);
            color: #fff;
        }

        .print-toolbar__actions .btn-close {
            background: rgba(107, 114, 128, 0.12);
            color: var(--statement-text);
        }

        .print-page {
            width: min(100%, 1180px);
            margin: 0 auto;
            padding: 1.25rem;
        }

        .print-shell {
            background: var(--statement-surface);
            border: 1px solid rgba(76, 129, 139, 0.14);
            border-radius: 28px;
            box-shadow: var(--statement-shadow);
            overflow: hidden;
        }

        .print-body {
            padding: 1.5rem;
        }

        .print-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem 0;
        }

        .print-heading__title h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--statement-primary-dark);
        }

        .print-heading__title p {
            margin-top: 0.5rem;
            color: var(--statement-muted);
            line-height: 1.8;
        }

        .print-heading__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.8rem;
            margin-bottom: 0.8rem;
            border-radius: 999px;
            background: var(--statement-accent-soft);
            color: #8b6e00;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .print-heading__meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.75rem;
            min-width: 300px;
            width: min(100%, 520px);
        }

        .print-meta-card {
            padding: 0.95rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(76, 129, 139, 0.14);
            background: var(--statement-surface-alt);
        }

        .print-meta-card span {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--statement-muted);
            font-size: 0.84rem;
        }

        .print-meta-card strong {
            display: block;
            color: var(--statement-primary-dark);
            font-size: 1rem;
        }

        .statement-report {
            padding: 0;
        }

        .statement-report-top {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .statement-report-heading h2 {
            margin: 0.25rem 0 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--statement-primary-dark);
        }

        .statement-report-heading p {
            margin: 0.6rem 0 0;
            max-width: 720px;
            color: var(--statement-muted);
            line-height: 1.75;
        }

        .statement-report-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(76, 129, 139, 0.1);
            color: var(--statement-primary-dark);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .statement-report-meta,
        .statement-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }

        .statement-meta-pill,
        .statement-summary-card {
            padding: 0.95rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(76, 129, 139, 0.14);
            background: var(--statement-surface-alt);
        }

        .statement-meta-pill span,
        .statement-summary-card span {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.85rem;
            color: var(--statement-muted);
        }

        .statement-meta-pill strong,
        .statement-summary-card strong {
            display: block;
            font-size: 1.05rem;
            color: var(--statement-primary-dark);
        }

        .statement-summary-card small {
            display: block;
            margin-top: 0.35rem;
            color: var(--statement-muted);
        }

        .statement-summary-grid {
            margin-bottom: 1rem;
        }

        .statement-summary-card.is-debtor {
            background: rgba(225, 29, 72, 0.06);
            border-color: rgba(225, 29, 72, 0.14);
        }

        .statement-summary-card.is-creditor {
            background: rgba(37, 99, 235, 0.06);
            border-color: rgba(37, 99, 235, 0.14);
        }

        .statement-summary-card.is-accent {
            background: var(--statement-accent-soft);
            border-color: rgba(231, 202, 8, 0.22);
        }

        .statement-summary-card.is-neutral {
            background: rgba(107, 114, 128, 0.08);
            border-color: rgba(107, 114, 128, 0.18);
        }

        .statement-table-card {
            border: 1px solid var(--statement-border);
            border-radius: 20px;
            overflow: hidden;
        }

        .statement-table-card--currency {
            margin-inline: -0.35rem;
        }

        .statement-table-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--statement-border);
            background: linear-gradient(135deg, rgba(76, 129, 139, 0.08), rgba(255, 255, 255, 0.96));
        }

        .statement-table-head h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--statement-primary-dark);
        }

        .statement-table-head p {
            margin: 0.3rem 0 0;
            color: var(--statement-muted);
            line-height: 1.7;
        }

        .statement-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .statement-table-scroll {
            width: 100%;
            overflow-x: visible;
        }

        .statement-table--currency {
            table-layout: fixed;
            font-size: 0.78rem;
        }

        .statement-table--currency .statement-col-index {
            width: 3.5%;
        }

        .statement-table--currency .statement-col-date {
            width: 7.5%;
        }

        .statement-table--currency .statement-col-number {
            width: 6%;
        }

        .statement-table--currency .statement-col-type {
            width: 8.5%;
        }

        .statement-table--currency .statement-col-account {
            width: 12%;
        }

        .statement-table--currency .statement-col-statement {
            width: 19%;
        }

        .statement-table--currency .statement-col-money {
            width: 11%;
        }

        .statement-table--currency .statement-col-balance {
            width: 12%;
        }

        .statement-table--currency .statement-col-user {
            width: 9.5%;
        }

        .statement-table thead th {
            padding: 0.62rem 0.38rem;
            background: var(--statement-primary-dark);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.08);
            text-align: right;
            white-space: normal;
            font-size: 0.78rem;
            line-height: 1.35;
        }

        .statement-th-note {
            display: block;
            margin-top: 0.2rem;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .statement-table tbody td,
        .statement-table tfoot td {
            padding: 0.52rem 0.36rem;
            border: 1px solid rgba(76, 129, 139, 0.12);
            vertical-align: middle;
            font-size: 0.78rem;
            overflow-wrap: anywhere;
        }

        .statement-table tbody tr:nth-child(even):not(.statement-row--opening) {
            background: rgba(76, 129, 139, 0.03);
        }

        .statement-row--opening {
            background: var(--statement-accent-soft);
            font-weight: 700;
        }

        .statement-row--total {
            background: rgba(76, 129, 139, 0.08);
            font-weight: 800;
        }

        .statement-row--muted {
            color: var(--statement-muted);
        }

        .statement-cell--wide {
            line-height: 1.7;
            white-space: normal;
            word-break: break-word;
        }

        .statement-entry-link {
            color: var(--statement-primary-dark);
            font-weight: 800;
            text-decoration: none;
        }

        .statement-balance-cell {
            white-space: nowrap;
            text-align: center;
        }

        .statement-money-cell {
            text-align: center;
            white-space: normal;
        }

        .statement-balance-pill {
            display: inline-flex;
            flex-direction: column;
            gap: 0.15rem;
            width: 96px;
            max-width: 100%;
            padding: 0.32rem 0.36rem;
            border-radius: 14px;
            border: 1px solid rgba(76, 129, 139, 0.14);
            background: #fff;
            line-height: 1.2;
        }

        .statement-balance-pill strong {
            font-size: 0.92rem;
        }

        .statement-balance-pill small {
            color: var(--statement-muted);
            font-size: 0.76rem;
        }

        .statement-balance-pill.is-debtor {
            background: rgba(225, 29, 72, 0.06);
            border-color: rgba(225, 29, 72, 0.16);
        }

        .statement-balance-pill.is-creditor {
            background: rgba(37, 99, 235, 0.06);
            border-color: rgba(37, 99, 235, 0.16);
        }

        .statement-balance-pill.is-neutral {
            background: rgba(107, 114, 128, 0.08);
            border-color: rgba(107, 114, 128, 0.18);
        }

        .statement-currency-stack {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            width: 88px;
            max-width: 100%;
            margin-inline: auto;
            line-height: 1.2;
            white-space: nowrap;
            text-align: center;
            direction: rtl;
        }

        .statement-currency-row {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 0.24rem;
            margin: 0;
            padding: 0.16rem 0.24rem;
            border-radius: 8px;
            background: rgba(76, 129, 139, 0.045);
            direction: rtl;
        }

        .statement-currency-row b {
            min-width: 52px;
            font-weight: 800;
            color: var(--statement-primary-dark);
            text-align: right;
            direction: ltr;
            font-variant-numeric: tabular-nums;
            unicode-bidi: isolate;
        }

        .statement-currency-row em {
            color: var(--statement-muted);
            min-width: 26px;
            text-align: right;
            font-size: 0.68rem;
            font-style: normal;
            font-weight: 700;
            white-space: nowrap;
            direction: rtl;
        }

        .statement-currency-row--usd b {
            color: #0f766e;
        }

        .statement-currency-row--sar {
            border-top: 1px dashed rgba(76, 129, 139, 0.18);
        }

        .statement-other-accounts {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .statement-other-account {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 100%;
            padding: 0.22rem 0.55rem;
            border-radius: 999px;
            background: rgba(76, 129, 139, 0.08);
            color: var(--statement-primary-dark);
            font-size: 0.82rem;
            white-space: normal;
            text-decoration: none;
            gap: 0.35rem;
            border: 1px solid transparent;
        }

        .statement-other-account--empty {
            background: rgba(107, 114, 128, 0.1);
            color: var(--statement-muted);
        }

        .statement-empty-state {
            padding: 2.5rem 1.25rem;
            text-align: center;
            border: 1px dashed rgba(76, 129, 139, 0.28);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(76, 129, 139, 0.04), rgba(255, 255, 255, 0.95));
        }

        .statement-empty-icon {
            display: grid;
            place-items: center;
            width: 68px;
            height: 68px;
            margin: 0 auto 1rem;
            border-radius: 18px;
            background: var(--statement-accent-soft);
            color: #8b6e00;
            font-size: 1.6rem;
        }

        .statement-empty-state h3 {
            margin: 0;
            color: var(--statement-primary-dark);
            font-size: 1.2rem;
            font-weight: 800;
        }

        .statement-empty-state p {
            max-width: 480px;
            margin: 0.75rem auto 0;
            color: var(--statement-muted);
            line-height: 1.8;
        }

        .print-signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .print-signature {
            padding: 1rem 1.1rem;
            border-radius: 18px;
            border: 1px dashed rgba(76, 129, 139, 0.28);
            background: rgba(76, 129, 139, 0.03);
        }

        .print-signature h4 {
            margin: 0 0 0.8rem;
            font-size: 1rem;
            color: var(--statement-primary-dark);
        }

        .print-signature span {
            display: block;
            height: 30px;
            border-bottom: 1px solid rgba(31, 41, 55, 0.28);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {

            .print-toolbar,
            .print-heading,
            .statement-report-top {
                flex-direction: column;
            }

            .print-signatures {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            @page {
                size: auto;
                margin: 12mm;
            }

            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .print-page {
                width: 100%;
                padding: 0;
            }

            .print-shell {
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .print-body {
                padding: 1rem 0 0;
            }

            .statement-table thead {
                display: table-header-group;
            }

            .statement-table tr,
            .statement-summary-card,
            .print-signature {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="print-toolbar no-print">
        <div class="print-toolbar__copy">نسخة مهيأة للطباعة. يمكنك إعادة الطباعة أو إغلاق الصفحة بعد المراجعة.</div>

        <div class="print-toolbar__actions">
            <button type="button" class="btn-print" onclick="window.print()">
                <i class="fa fa-print"></i>
                {{ __('finance::finance.print') }}
            </button>
            <button type="button" class="btn-close" onclick="window.close()">
                <i class="fa fa-times"></i>
                إغلاق
            </button>
        </div>
    </div>

    <div class="print-page">
        <div class="print-shell">
            @include('click.layout.inc.print_header')

            <section class="print-heading">
                <div class="print-heading__title">
                    <span class="print-heading__badge">نسخة كشف حساب رسمية</span>
                    <h1>{{ $title ?? 'كشف حساب' }}</h1>
                    <p>نسخة جاهزة للطباعة أو الحفظ PDF، مبنية على نفس الفلاتر الحالية مع إظهار أوضح للرصيد والحسابات
                        المقابلة.</p>
                </div>

                <div class="print-heading__meta">
                    <div class="print-meta-card">
                        <span>{{ __('finance::finance.account') }}</span>
                        <strong>{{ $accountName ?: 'غير محدد' }}</strong>
                    </div>
                    <div class="print-meta-card">
                        <span>{{ __('finance::finance.From Date') }}</span>
                        <strong>{{ !empty($filters['from_date']) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y') : 'بداية مفتوحة' }}</strong>
                    </div>
                    <div class="print-meta-card">
                        <span>{{ __('finance::finance.To Date') }}</span>
                        <strong>{{ !empty($filters['to_date']) ? \Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : 'حتى اليوم' }}</strong>
                    </div>
                    <div class="print-meta-card">
                        <span>تاريخ الإصدار</span>
                        <strong>{{ now()->format('d/m/Y h:i A') }}</strong>
                    </div>
                </div>
            </section>

            <main class="print-body">
                @include('finance::finance.account_statement.partials.report', [
                    'data' => $data,
                    'firstBalance' => $firstBalance,
                    'otherAccount' => $otherAccount,
                    'show_other_account' => $show_other_account,
                    'accountName' => $accountName,
                    'filters' => $filters ?? [],
                    'reportTitle' => $reportTitle ?? null,
                    'isPrint' => true,
                    'showDollarEquivalent' => $showDollarEquivalent ?? false,
                ])

                <section class="print-signatures">
                    <div class="print-signature">
                        <h4>المحاسب</h4>
                        <span></span>
                    </div>
                    <div class="print-signature">
                        <h4>يعتمد</h4>
                        <span></span>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        const shouldAutoPrint = new URLSearchParams(window.location.search).get('autoprint') === '1';
        const shouldAutoClose = new URLSearchParams(window.location.search).get('autoclose') === '1';

        if (shouldAutoPrint) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 250);
            });
        }

        window.onafterprint = function() {
            if (shouldAutoClose) {
                window.close();
            }
        };
    </script>
</body>

</html>
