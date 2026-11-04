@extends('click.layout.indexs.index')

@php
    $statementRoute = $statementRoute ?? route('finance.account-statement.index');
    $printRoute = $printRoute ?? route('finance.account-statement-print.index');
    $printPermission = $printPermission ?? 'finance_account_statement_export';
    $canExport = @AdminGuardHasPerm($printPermission)
        || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value;
    $accountSearchRoute = $accountSearchRoute ?? null;
    $exportExcelRoute = $exportExcelRoute ?? route('finance.account-statement.export', 'excel');
    $exportPdfRoute = $exportPdfRoute ?? route('finance.account-statement.export', 'pdf');
    $exportScopeDescription = $exportScopeDescription
        ?? 'جميع الحسابات عند ترك الحساب فارغًا';
@endphp

@section('style')
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
            --statement-shadow: 0 18px 38px rgba(49, 90, 99, 0.08);
        }

        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid var(--statement-accent);
            border-radius: 50%;
            width: 96px;
            height: 96px;
            margin: 0 auto;
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

        .statement-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            color: var(--statement-text);
        }

        body.statement-page-wide .page-body,
        body:has(.statement-shell) .page-body {
            padding-inline: 0.45rem !important;
        }

        body.statement-page-wide .page-body > .container-fluid,
        body:has(.statement-shell) .page-body > .container-fluid {
            padding-inline: 0.45rem;
            max-width: none;
        }

        body.statement-page-wide .page-body > .container-fluid > .card > .card-body,
        body:has(.statement-shell) .page-body > .container-fluid > .card > .card-body {
            padding: 0.75rem;
        }

        .statement-hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: 24px;
            border: 1px solid rgba(76, 129, 139, 0.18);
            background:
                radial-gradient(circle at top right, rgba(231, 202, 8, 0.2), transparent 34%),
                linear-gradient(135deg, rgba(76, 129, 139, 0.1), rgba(255, 255, 255, 0.96));
            box-shadow: var(--statement-shadow);
        }

        .statement-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.7rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--statement-primary-dark);
            letter-spacing: 0.08em;
        }

        .statement-title {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--statement-primary-dark);
        }

        .statement-subtitle {
            max-width: 760px;
            margin: 0.75rem 0 0;
            color: var(--statement-muted);
            line-height: 1.8;
        }

        .statement-hero-note {
            min-width: 280px;
            max-width: 360px;
            padding: 1rem 1.1rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(76, 129, 139, 0.12);
            backdrop-filter: blur(10px);
        }

        .statement-hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            margin-bottom: 0.75rem;
            border-radius: 999px;
            background: var(--statement-accent-soft);
            color: #8b6e00;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .statement-hero-note p {
            margin: 0;
            color: var(--statement-muted);
            line-height: 1.8;
        }

        .statement-filter-card,
        .statement-report,
        .statement-action-bar {
            background: var(--statement-surface);
            border: 1px solid var(--statement-border);
            border-radius: 22px;
            box-shadow: var(--statement-shadow);
        }

        .statement-filter-card {
            padding: 1.3rem;
        }

        .statement-card-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .statement-card-head h3,
        .statement-table-head h3 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--statement-primary-dark);
        }

        .statement-card-head p,
        .statement-table-head p {
            margin: 0.3rem 0 0;
            color: var(--statement-muted);
            line-height: 1.7;
        }

        .statement-filter-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
        }

        .statement-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .statement-field--account {
            grid-column: span 4;
        }

        .statement-field--date,
        .statement-field--select {
            grid-column: span 2;
        }

        .statement-field label {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--statement-primary-dark);
        }

        .statement-field .form-control {
            min-height: 46px;
            border-radius: 14px;
            border-color: var(--statement-border);
            box-shadow: none;
        }

        .statement-field .form-control:focus {
            border-color: rgba(76, 129, 139, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(76, 129, 139, 0.1);
        }

        .statement-filter-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(76, 129, 139, 0.18);
        }

        .statement-filter-note {
            margin: 0;
            color: var(--statement-muted);
            line-height: 1.7;
        }

        .statement-filter-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .statement-filter-actions .btn {
            min-height: 46px;
            padding-inline: 1.1rem;
            border-radius: 14px;
            font-weight: 700;
        }

        .statement-filter-actions .btn-success {
            background-color: var(--statement-primary);
            border-color: var(--statement-primary);
        }

        .statement-filter-actions .btn-success:hover {
            background-color: var(--statement-primary-dark);
            border-color: var(--statement-primary-dark);
        }

        .statement-action-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.2rem;
        }

        .statement-action-copy {
            margin: 0;
            color: var(--statement-muted);
            line-height: 1.7;
        }

        .statement-action-buttons {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .statement-action-buttons .btn {
            min-height: 44px;
            padding-inline: 1rem;
            border-radius: 14px;
            font-weight: 700;
        }

        .statement-results {
            min-height: 120px;
        }

        .statement-loading-state {
            padding: 2.5rem 1.25rem;
            background: var(--statement-surface);
            border: 1px solid var(--statement-border);
            border-radius: 22px;
            box-shadow: var(--statement-shadow);
        }

        .statement-loading-state p {
            margin: 1rem 0 0;
            text-align: center;
            color: var(--statement-muted);
        }

        .statement-report {
            padding: 1.25rem;
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
            font-size: 1.55rem;
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

        .statement-report-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            width: min(100%, 540px);
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
            font-size: 1.1rem;
            color: var(--statement-primary-dark);
        }

        .statement-summary-card small {
            display: block;
            margin-top: 0.35rem;
            color: var(--statement-muted);
        }

        .statement-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.8rem;
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
            margin-inline: -0.55rem;
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

        .statement-table-note {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(76, 129, 139, 0.08);
            color: var(--statement-primary-dark);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .statement-table {
            width: 100%;
            margin-bottom: 0;
            color: var(--statement-text);
        }

        .statement-table:not(.statement-table--currency) {
            min-width: 1180px;
            table-layout: fixed;
        }

        .statement-table:not(.statement-table--currency) .statement-col-index { width: 5%; }
        .statement-table:not(.statement-table--currency) .statement-col-date { width: 10%; }
        .statement-table:not(.statement-table--currency) .statement-col-number { width: 9%; }
        .statement-table:not(.statement-table--currency) .statement-col-type { width: 10%; }
        .statement-table:not(.statement-table--currency) .statement-col-statement { width: 27%; }
        .statement-table:not(.statement-table--currency) .statement-col-money { width: 9%; }
        .statement-table:not(.statement-table--currency) .statement-col-balance { width: 13%; }
        .statement-table:not(.statement-table--currency) .statement-col-user { width: 8%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-index { width: 4%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-date { width: 9%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-number { width: 8%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-type { width: 9%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-account { width: 14%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-statement { width: 21%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-money { width: 8%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-balance { width: 12%; }
        .statement-table--with-counter:not(.statement-table--currency) .statement-col-user { width: 7%; }

        .statement-table-scroll {
            width: 100%;
            overflow-x: auto;
            scrollbar-gutter: stable;
        }

        .statement-table--currency {
            min-width: 100%;
            table-layout: fixed;
            font-size: 0.82rem;
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
            position: sticky;
            top: 0;
            z-index: 1;
            vertical-align: middle;
            white-space: normal;
            padding: 0.72rem 0.48rem;
            background: var(--statement-primary-dark);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.08);
            font-size: 0.82rem;
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
            padding: 0.62rem 0.45rem;
            vertical-align: middle;
            border-color: rgba(76, 129, 139, 0.12);
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

        .statement-entry-link:hover {
            text-decoration: underline;
        }

        .statement-balance-cell {
            white-space: nowrap;
            text-align: center;
        }

        .statement-money-cell {
            text-align: center;
            white-space: nowrap;
            overflow-wrap: normal !important;
            word-break: normal;
            font-variant-numeric: tabular-nums;
        }

        .statement-cell--index,
        .statement-cell--number,
        .statement-cell--type,
        .statement-cell--user {
            white-space: nowrap;
            overflow-wrap: normal !important;
            word-break: normal;
        }

        .statement-cell--index,
        .statement-cell--number {
            direction: ltr;
            font-variant-numeric: tabular-nums;
        }

        .statement-balance-pill {
            display: inline-flex;
            flex-direction: column;
            gap: 0.15rem;
            width: 104px;
            max-width: 100%;
            padding: 0.36rem 0.42rem;
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
            gap: 0.22rem;
            width: 96px;
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
            gap: 0.28rem;
            margin: 0;
            padding: 0.18rem 0.28rem;
            border-radius: 8px;
            background: rgba(76, 129, 139, 0.045);
            direction: rtl;
        }

        .statement-currency-row b {
            min-width: 58px;
            font-weight: 800;
            color: var(--statement-primary-dark);
            text-align: right;
            direction: ltr;
            font-variant-numeric: tabular-nums;
            unicode-bidi: isolate;
        }

        .statement-currency-row em {
            color: var(--statement-muted);
            min-width: 28px;
            text-align: right;
            font-size: 0.72rem;
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
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: rgba(76, 129, 139, 0.08);
            color: var(--statement-primary-dark);
            font-size: 0.82rem;
            white-space: normal;
            text-decoration: none;
            gap: 0.35rem;
            border: 1px solid transparent;
        }

        .statement-other-account:hover {
            color: var(--statement-primary-dark);
            border-color: rgba(76, 129, 139, 0.16);
        }

        .statement-other-account--empty {
            background: rgba(107, 114, 128, 0.1);
            color: var(--statement-muted);
        }

        .statement-empty-state {
            padding: 3rem 1.5rem;
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

        @media (max-width: 1199.98px) {
            .statement-field--account {
                grid-column: span 12;
            }

            .statement-field--date,
            .statement-field--select {
                grid-column: span 3;
            }
        }

        @media (max-width: 767.98px) {
            .statement-title {
                font-size: 1.5rem;
            }

            .statement-filter-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .statement-field--account,
            .statement-field--date,
            .statement-field--select {
                grid-column: span 1;
            }

            .statement-filter-footer,
            .statement-action-bar,
            .statement-report-top {
                flex-direction: column;
                align-items: stretch;
            }

            .statement-report-meta {
                width: 100%;
            }
        }

        @media (max-width: 1199.98px) {
            .statement-table--currency {
                min-width: 1060px;
            }

            .statement-table-scroll {
                padding-bottom: 0.35rem;
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
                inset: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .statement-hero,
            .statement-filter-card,
            .print-and-excel-buttons,
            .sidebar,
            .navbar,
            .breadcrumb,
            .page-title-box,
            footer {
                display: none !important;
            }

            .statement-report,
            .statement-table-card {
                box-shadow: none;
                border-color: #cdd5d8;
            }

            .statement-table thead th {
                position: static;
                background: #315a63 !important;
            }
        }

        .table-responsive {
            overflow-x: auto !important;
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
    <div class="statement-shell">
        {{-- <section class="statement-hero">
            <div>
                <span class="statement-eyebrow">FINANCE REPORT</span>
                <h1 class="statement-title">{{ $bladeTitle }}</h1>
                <p class="statement-subtitle">
                    عرض أوضح لكشف الحساب مع نفس الفلاتر للطباعة والحفظ PDF، وإظهار الحسابات المقابلة بشكل مرتب لتسهيل
                    المراجعة.
                </p>
            </div>

            <aside class="statement-hero-note">
                <span class="statement-hero-chip">جاهز للطباعة</span>
                <p>اختر الحساب ثم نفّذ البحث. النتيجة المعروضة بالأسفل أصبحت أقرب لنسخة ورقية، مع روابط مباشرة للقيد وكشف
                    الحسابات المقابلة.</p>
            </aside>
        </section> --}}

        <form id="accountStatementFilters" class="statement-filter-card">
            <div class="statement-card-head">
                <div>
                    <h3>تصفية كشف الحساب</h3>
                    <p>الفلاتر التالية تتحكم في الحركات المعروضة والطباعة النهائية لنفس النتيجة.</p>
                </div>
            </div>

            <div class="statement-filter-grid">
                <div class="statement-field statement-field--account">
                    <label for="account_id">{{ __('finance::finance.account') }}</label>
                    <select name="account_id" id="account_id" class="form-control search_keys">
                        <option value="">اختر الحساب بالاسم أو رقم الحساب</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) ($filters['account_id'] ?? request('account_id')) === (string) $account->id)>
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="statement-field statement-field--date">
                    <label for="from_date">{{ __('finance::finance.From Date') }}</label>
                    <input type="date" name="from_date" id="from_date" class="form-control search_keys"
                        value="{{ $filters['from_date'] ?? request('from_date') }}">
                </div>

                <div class="statement-field statement-field--date">
                    <label for="to_date">{{ __('finance::finance.To Date') }}</label>
                    <input type="date" name="to_date" id="to_date" class="form-control search_keys"
                        value="{{ $filters['to_date'] ?? request('to_date', date('Y-m-d')) }}">
                </div>

                <div class="statement-field statement-field--select">
                    <label for="type">{{ __('finance::finance.Transaction Type') }}</label>
                    <select name="type" id="type" class="form-control search_keys">
                        <option value="both" @selected(($filters['type'] ?? request('type', 'both')) === 'both')>{{ __('finance::finance.All') }}</option>
                        <option value="debtor" @selected(($filters['type'] ?? request('type')) === 'debtor')>{{ __('finance::finance.debtor') }}</option>
                        <option value="creditor" @selected(($filters['type'] ?? request('type')) === 'creditor')>{{ __('finance::finance.creditor') }}
                        </option>
                    </select>
                </div>

                @if ($showWorkerSearch ?? false)
                    <div class="statement-field statement-field--account">
                        <label for="worker_search">البحث عن عاملة</label>
                        <input type="text" name="worker_search" id="worker_search" class="form-control search_keys"
                            placeholder="اسم العاملة أو رقم الجواز" value="{{ $filters['worker_search'] ?? '' }}">
                    </div>
                @endif

                @include('admin.partials.branch-filter', [
                    'name' => 'branch_id',
                    'label' => __('financial_requests.branch'),
                    'id' => 'statement_branch_id',
                    'containerClass' => 'statement-field statement-field--select',
                    'selectClass' => 'form-control search_keys',
                    'labelClass' => '',
                ])

                <div class="statement-field statement-field--select">
                    <label for="other_account">{{ __('finance::finance.Show Counter Account') }}</label>
                    <select name="other_account" id="other_account" class="form-control search_keys">
                        <option value="no" @selected(($filters['other_account'] ?? request('other_account', 'no')) === 'no')>{{ __('finance::finance.No') }}</option>
                        <option value="yes" @selected(($filters['other_account'] ?? request('other_account', 'no')) === 'yes')>نعم</option>
                    </select>
                </div>
            </div>

            <div class="statement-filter-footer">
                <p class="statement-filter-note">
                    إذا تركت تاريخ البداية فارغًا فسيعرض النظام كل الحركات المتاحة حتى نهاية الفترة المحددة.
                </p>

                <div class="statement-filter-actions">
                    <button type="button" id="search_button" class="btn btn-success">
                        <i class="fa fa-search"></i>
                        {{ __('finance::finance.Search') }}
                    </button>
                </div>
            </div>
        </form>

        @if ($canExport)
            <div class="statement-action-bar mb-3">
                <p class="statement-action-copy">
                    التصدير الشامل يشمل {{ $exportScopeDescription }}، أو الحساب المحدد فقط، مع تطبيق الفترة ونوع الحركة.
                </p>
                <div class="statement-action-buttons">
                    <a href="#" class="btn btn-success comprehensive-statement-export"
                        data-export-url="{{ $exportExcelRoute }}">
                        <i class="fa fa-file-excel-o"></i> تصدير شامل Excel
                    </a>
                    <a href="#" class="btn btn-danger comprehensive-statement-export"
                        data-export-url="{{ $exportPdfRoute }}"
                        data-new-window="true">
                        <i class="fa fa-file-pdf-o"></i> طباعة / حفظ PDF شامل
                    </a>
                </div>
            </div>
        @endif

        <div class="print-and-excel-buttons {{ ($initialResultCount ?? 0) > 0 ? '' : 'd-none' }}">
            <div class="statement-action-bar">
                <p class="statement-action-copy">الطباعة والتصدير يعتمدان دائمًا على نفس الفلاتر الحالية ونتيجة البحث
                    المعروضة أسفلها.</p>

                @if ($canExport)
                <div class="statement-action-buttons">
                    {!! printButton(
                        $printRoute . '?' . http_build_query(array_merge(request()->all(), ['autoprint' => 1])),
                        null,
                        $printPermission,
                    ) !!}
                    <button type="button" id="toExcel" class="btn btn-info">
                        <i class="fa fa-file-excel-o"></i>
                        Excel
                    </button>
                </div>
                @endif
            </div>
        </div>

        <div id="account_list" class="statement-results">{!! $initialResultHtml ?? '' !!}</div>
    </div>
@endsection

@section('js')
    <script>
        document.body.classList.add('statement-page-wide');

        const accountSelectOptions = {
            placeholder: 'ابحث باسم الحساب أو رقم الحساب',
            width: '100%',
            allowClear: true,
            matcher: function(params, data) {
                const normalize = value => String(value || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase();
                const term = normalize(params.term);
                if (!term) return data;
                if (data.children) {
                    const children = data.children.filter(child => normalize(child.text).includes(term));
                    return children.length ? $.extend({}, data, { children: children }) : null;
                }
                return normalize(data.text).includes(term) ? data : null;
            },
            templateResult: function(account) {
                return account.text;
            },
            templateSelection: function(account) {
                return account.text;
            }
        };

        @if ($accountSearchRoute)
            accountSelectOptions.minimumInputLength = 1;
            accountSelectOptions.ajax = {
                url: @json($accountSearchRoute),
                dataType: 'json',
                delay: 300,
                cache: true,
                data: function(params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return data;
                }
            };
        @endif

        $('#account_id').select2(accountSelectOptions);

        const filtersContainer = $('#accountStatementFilters');
        const printButton = $('#print_button');
        const statementBaseUrl = @json($statementRoute);
        const printBaseUrl = @json($printRoute);
        const resultsContainer = $('#account_list');

        $('.comprehensive-statement-export').on('click', function(event) {
            event.preventDefault();
            const query = filtersContainer.find('.search_keys').serialize();
            const exportUrl = $(this).data('export-url') + (query ? '?' + query : '');

            if ($(this).data('new-window')) {
                window.open(exportUrl, '_blank', 'noopener');
                return;
            }

            window.location.href = exportUrl;
        });

        function updatePrintHref() {
            if (printButton.length === 0) {
                return;
            }

            const filterData = filtersContainer.find('.search_keys').serialize();
            const query = filterData.length ? `${filterData}&autoprint=1` : 'autoprint=1';
            printButton.attr('href', `${printBaseUrl}?${query}`);
        }

        function renderLoadingState() {
            resultsContainer.html(`
                <div class="statement-loading-state">
                    <div class="loader"></div>
                    <p>جاري تجهيز كشف الحساب...</p>
                </div>
            `);
        }

        function searchAccountStatement(urlOverride = null) {
            const data = filtersContainer.find('.search_keys').serialize();

            $.ajax({
                url: urlOverride || statementBaseUrl,
                type: 'GET',
                data: urlOverride ? null : data,
                beforeSend: function() {
                    renderLoadingState();
                },
                success: function(result) {
                    resultsContainer.html(result.data.html);

                    if (result.data.count > 0) {
                        $('.print-and-excel-buttons').removeClass('d-none');
                        updatePrintHref();
                    } else {
                        $('.print-and-excel-buttons').addClass('d-none');
                    }
                },
                error: function(errorObj) {
                    handleErrorResponce(errorObj);
                }
            });
        }

        $('#search_button').on('click', function(e) {
            e.preventDefault();
            searchAccountStatement();
        });

        resultsContainer.on('click', '.statement-pagination a', function(e) {
            e.preventDefault();
            searchAccountStatement(this.href);
        });

        filtersContainer.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAccountStatement();
            }
        });

        @if (!empty($filters['account_id'] ?? request('account_id')) && empty($initialResultHtml))
            searchAccountStatement();
        @endif
    </script>

    <script src="{{ asset('plugins') }}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script src="{{ asset('admin/assets/js/statement-export.js') }}"></script>
    <script>
        const excelButton = document.querySelector('#toExcel');

        if (excelButton) {
            excelButton.addEventListener('click', function() {
                const table = document.querySelector('#simpleTable4');

                if (!table) {
                    return;
                }

                exportFullStatement(printBaseUrl + '?' + filtersContainer.find('.search_keys').serialize(), this);
            });
        }
    </script>
@endsection
