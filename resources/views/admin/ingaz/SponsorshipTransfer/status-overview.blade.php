@extends('admin.layout.indexs.index')

@section('page-title')
    {{ $pageConfig['title'] }}
@endsection

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .status-overview-page {
            --so-border: #e5e7eb;
            --so-soft: #f8fafc;
            --so-text: #0f172a;
            --so-body: #334155;
            --so-muted: #64748b;
            --so-primary: #2563eb;
        }

        /* ===== رأس الصفحة ===== */
        .so-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.15rem 1.4rem;
            margin-bottom: 1.25rem;
            background: #fff;
            border: 1px solid var(--so-border);
            border-radius: 16px;
        }

        .so-hero__title {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .so-hero__icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 1.25rem;
            background: #eff6ff;
            color: var(--so-primary);
        }

        .so-hero h1 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--so-text);
        }

        .so-hero__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            font-size: 0.86rem;
            color: var(--so-muted);
        }

        .so-hero__meta strong {
            color: var(--so-text);
        }

        .so-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* ===== بطاقات الإحصائيات ===== */
        .so-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .so-stat {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            padding: 1rem 1.1rem;
            background: #fff;
            border: 1px solid var(--so-border);
            border-radius: 14px;
            color: inherit;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .so-stat:hover,
        .so-stat:focus {
            color: inherit;
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 0.6rem 1.4rem rgba(15, 23, 42, 0.08);
        }

        .so-stat__icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.15rem;
            background: #f1f5f9;
            color: #475569;
        }

        .so-stat--primary .so-stat__icon { background: #eff6ff; color: #2563eb; }
        .so-stat--success .so-stat__icon { background: #ecfdf5; color: #059669; }
        .so-stat--warning .so-stat__icon { background: #fffbeb; color: #d97706; }
        .so-stat--danger .so-stat__icon { background: #fef2f2; color: #dc2626; }

        .so-stat__label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--so-muted);
        }

        .so-stat__value {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.25;
            color: var(--so-text);
        }

        .so-stat__hint {
            font-size: 0.72rem;
            color: #94a3b8;
        }

        /* ===== اللوحات (الفلاتر / الجدول) ===== */
        .so-panel {
            margin-bottom: 1.25rem;
            background: #fff;
            border: 1px solid var(--so-border);
            border-radius: 16px;
            /* بدون overflow: hidden حتى لا تُقص قائمة الإجراءات أو يختفي جزء من الجدول */
        }

        .so-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1.25rem;
            background: var(--so-soft);
            border-bottom: 1px solid var(--so-border);
            border-radius: 16px 16px 0 0;
        }

        .so-panel__header:last-child {
            border-bottom: 0;
            border-radius: 16px;
        }

        .so-panel .table-responsive {
            border-radius: 0 0 16px 16px;
        }

        .so-panel .table-responsive:only-child {
            border-radius: 16px;
        }

        .so-panel__title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--so-text);
        }

        .so-panel__title i {
            color: var(--so-muted);
        }

        .so-panel__body {
            padding: 1.25rem;
        }

        .so-panel__footer {
            padding: 0.85rem 1.25rem;
            border-top: 1px solid var(--so-border);
        }

        .so-panel__footer .pagination {
            margin-bottom: 0;
        }

        .so-filters .form-label {
            margin-bottom: 0.35rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
        }

        .so-filters .form-control,
        .so-filters .form-select {
            min-height: 40px;
            border-color: #d7dee7;
            border-radius: 10px;
            box-shadow: none;
        }

        .so-filters .form-control:focus,
        .so-filters .form-select:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 0.18rem rgba(148, 163, 184, 0.16);
        }

        .so-filters__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 1.1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--so-border);
        }

        .status-overview-page .active-filters-box {
            margin-bottom: 1rem;
            padding: 0.8rem 1rem;
            border: 1px dashed #d6dde6;
            border-radius: 12px;
            background: var(--so-soft);
        }

        .status-overview-page .active-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.7rem;
            margin: 0.2rem;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #d6dde6;
            color: var(--so-body);
            font-size: 0.78rem;
        }

        /* ===== الجدول ===== */
        .status-overview-table {
            margin-bottom: 0;
        }

        .status-overview-table thead th {
            padding: 0.75rem 1rem;
            white-space: nowrap;
            background: var(--so-soft);
            color: var(--so-muted);
            font-size: 0.8rem;
            font-weight: 700;
            border-bottom: 1px solid var(--so-border);
        }

        .status-overview-table td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-color: #f1f5f9;
            color: var(--so-body);
        }

        .status-overview-table tbody tr:hover {
            background: #fafcff;
        }

        .so-contract-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
            background: #f1f5f9;
            color: var(--so-body);
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
            text-decoration: none;
        }

        a.so-contract-badge:hover {
            background: #e2e8f0;
            color: var(--so-text);
        }

        .so-person {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            min-width: 150px;
        }

        .so-avatar {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .so-avatar--maid {
            background: #fdf2f8;
            color: #be185d;
        }

        .so-person__name {
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1.35;
            color: var(--so-text);
        }

        .so-person__sub {
            font-size: 0.76rem;
            line-height: 1.5;
            color: var(--so-muted);
        }

        .so-date {
            white-space: nowrap;
        }

        .so-date strong {
            display: block;
            font-size: 0.86rem;
            color: var(--so-text);
        }

        .so-date small {
            font-size: 0.75rem;
            color: var(--so-muted);
        }

        .so-amount {
            font-weight: 700;
            white-space: nowrap;
            color: var(--so-text);
        }

        .so-amount--due { color: #b91c1c; }
        .so-amount--clear { color: #047857; }

        .so-amount small {
            font-weight: 500;
            color: var(--so-muted);
        }

        .reason-box {
            max-width: 280px;
            white-space: normal;
            font-size: 0.84rem;
            color: #475569;
        }

        .status-overview-page .status-pill,
        .status-overview-page .meta-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .status-overview-page .meta-pill {
            margin-top: 0.3rem;
        }

        .status-overview-page .status-pill.status-primary,
        .status-overview-page .meta-pill.meta-primary {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-overview-page .status-pill.status-success {
            background: #d1fae5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .status-overview-page .status-pill.status-danger,
        .status-overview-page .meta-pill.meta-danger {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .status-overview-page .meta-pill.meta-warning {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
        }

        .status-overview-page .actions-btn {
            background: #fff;
            color: #1f2937;
            border: 1px solid #d6dde6;
            border-radius: 10px;
            white-space: nowrap;
        }

        .status-overview-page .actions-btn:hover,
        .status-overview-page .actions-btn:focus {
            background: var(--so-soft);
            color: #111827;
            border-color: #c1ccd8;
        }

        .status-overview-page .actions-dropdown .dropdown-menu {
            min-width: 230px;
            padding: 0.4rem;
            border: 1px solid var(--so-border);
            border-radius: 12px;
        }

        .status-overview-page .actions-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.5rem 0.7rem;
            border-radius: 8px;
            font-size: 0.86rem;
        }

        .status-overview-page .actions-dropdown .dropdown-item i {
            width: 18px;
            text-align: center;
        }

        .so-empty {
            padding: 3rem 1rem;
            text-align: center;
        }

        .so-empty__icon {
            width: 72px;
            height: 72px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            border-radius: 50%;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 1.8rem;
        }

        .so-reason-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.7rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        .so-reason-row:last-child {
            border-bottom: 0;
        }

        /* ===== عناصر مشتركة بين النوافذ ===== */
        .so-note {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.7rem 0.9rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 0.83rem;
            line-height: 1.65;
            text-align: right;
        }

        .so-note i {
            margin-top: 0.2rem;
        }

        .so-note--warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .so-note--info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }

        /* ===== نافذة تغيير العاملة ===== */
        .so-modal .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .so-modal .modal-header {
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: #fffbeb;
            border-bottom: 1px solid #fde68a;
        }

        .so-modal .modal-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.05rem;
            font-weight: 800;
            color: #78350f;
        }

        .so-modal__title-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fef3c7;
            color: #d97706;
        }

        .so-modal .modal-body {
            padding: 1.25rem;
        }

        .so-modal .modal-footer {
            padding: 0.9rem 1.25rem;
            background: var(--so-soft);
            border-top: 1px solid var(--so-border);
        }

        .so-current-maid {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--so-border);
            border-radius: 12px;
            background: var(--so-soft);
        }

        .so-field-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--so-body);
        }

        .so-choice {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            padding: 0.8rem 0.95rem;
            margin-bottom: 0.6rem;
            border: 1.5px solid var(--so-border);
            border-radius: 12px;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .so-choice:hover {
            border-color: #cbd5e1;
        }

        .so-choice:has(input:checked) {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .so-choice .form-check-input {
            flex-shrink: 0;
            margin: 0.2rem 0 0;
        }

        .so-choice__title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--so-text);
        }

        .so-choice__desc {
            font-size: 0.79rem;
            line-height: 1.55;
            color: var(--so-muted);
        }

        /* ===== نافذة تأكيد فشل التجربة (SweetAlert) ===== */
        .tf-swal.swal2-popup {
            width: min(780px, 96vw);
            padding: 0 0 1.25rem;
            border-radius: 18px;
        }

        .tf-swal .swal2-title {
            display: flex !important;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 800;
            text-align: right;
            color: #991b1b;
            background: #fef2f2;
            border-bottom: 1px solid #fecaca;
        }

        .tf-swal .swal2-html-container {
            max-height: 68vh;
            margin: 0;
            padding: 1.25rem 1.5rem 0;
            overflow-y: auto;
            text-align: right;
        }

        .tf-swal .swal2-actions {
            justify-content: flex-start;
            gap: 0.5rem;
            width: 100%;
            margin: 1rem 0 0;
            padding: 1rem 1.5rem 0;
            border-top: 1px solid var(--so-border, #e5e7eb);
        }

        /* زر الإغلاق في رأس النافذة (جهة اليسار لأن العنوان يمين) */
        .tf-swal .swal2-close {
            position: absolute;
            top: 0.8rem;
            left: 1rem;
            right: auto;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 0.95rem;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .tf-swal .swal2-close:hover,
        .tf-swal .swal2-close:focus {
            background: #fee2e2;
            color: #7f1d1d;
            box-shadow: none;
        }

        .tf-swal .swal2-title {
            padding-left: 4rem;
        }

        .tf-btn {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 42px;
            padding: 0.55rem 1.4rem;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .tf-btn i {
            display: inline-block;
            margin: 0;
            font-size: 0.9rem;
        }

        .tf-btn--danger {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.22);
        }

        .tf-btn--danger:hover,
        .tf-btn--danger:focus {
            background: #b91c1c;
            border-color: #b91c1c;
            color: #fff;
        }

        .tf-btn--ghost {
            background: #fff;
            border-color: #d6dde6;
            color: #334155;
        }

        .tf-btn--ghost:hover,
        .tf-btn--ghost:focus {
            background: #f8fafc;
            border-color: #c1ccd8;
            color: #0f172a;
        }

        .tf-btn:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .tf-swal .swal2-validation-message {
            margin: 1rem 1.5rem 0;
            border-radius: 10px;
        }

        .tf-swal .form-control,
        .tf-swal .form-select {
            min-height: 40px;
            font-size: 0.88rem;
            border-radius: 10px;
        }

        .tf-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .tf-summary__item {
            padding: 0.65rem 0.8rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8fafc;
        }

        .tf-summary__label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
        }

        .tf-summary__value {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .tf-summary__value small {
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
        }

        .tf-summary__item--paid .tf-summary__value { color: #047857; }
        .tf-summary__item--due .tf-summary__value { color: #b91c1c; }

        .tf-section {
            margin-bottom: 0.9rem;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        .tf-section__title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
        }

        .tf-section__icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 0.85rem;
        }

        .tf-title-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.95rem;
        }

        .tf-section__icon--warning {
            background: #fffbeb;
            color: #d97706;
        }

        .tf-tag {
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .tf-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .tf-span-2 {
            grid-column: 1 / -1;
        }

        .tf-label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
        }

        .tf-hint {
            margin-top: 0.3rem;
            font-size: 0.73rem;
            color: #64748b;
        }

        .tf-segment {
            display: flex;
            gap: 3px;
            padding: 3px;
            border-radius: 10px;
            background: #f1f5f9;
        }

        .tf-segment button {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-height: 34px;
            padding: 0.35rem 0.75rem;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #475569;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .tf-segment button.active {
            background: #fff;
            color: #1d4ed8;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.14);
        }

        .tf-preview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.6rem;
            margin-bottom: 0.9rem;
            padding: 0.8rem 0.9rem;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
        }

        .tf-preview__label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #1e40af;
        }

        .tf-preview__value {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        @media (max-width: 767.98px) {
            .so-hero {
                padding: 1rem;
            }

            .tf-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tf-grid,
            .tf-preview {
                grid-template-columns: minmax(0, 1fr);
            }

            .tf-swal .swal2-html-container,
            .tf-swal .swal2-actions {
                padding-inline: 1rem;
            }
        }
    </style>
@endsection

@section('content')
@if(auth()->guard('admin')->user()->can('sponsor_transfer_view'))
    @php
        $headerIcons = [
            'trial' => 'fas fa-user-clock',
            'transferred' => 'fas fa-check-circle',
            'failed' => 'fas fa-times-circle',
        ];

        $headerIcon = $headerIcons[$pageConfig['page']] ?? 'fas fa-exchange-alt';

        $statusPillClasses = [
            'primary' => 'status-primary',
            'success' => 'status-success',
            'danger' => 'status-danger',
            'warning' => 'status-primary',
            'info' => 'status-primary',
        ];

        $activeFilters = collect([
            helperTrans("ingaz.contract_number") => request('contract_number'),
            helperTrans("ingaz.old_sponsor") => request('old_sponsor'),
            helperTrans("ingaz.new_sponsor") => request('new_sponsor'),
            'العاملة' => request('maid_keyword'),
            helperTrans("ingaz.date_from") => request('date_from'),
            helperTrans("ingaz.date_to") => request('date_to'),
            helperTrans("ingaz.trial_days") => request('trial_days') ? request('trial_days') . ' يوم' : null,
            helperTrans("ingaz.contract_value") . ' من' => request('amount_from'),
            helperTrans("ingaz.contract_value") . ' إلى' => request('amount_to'),
            'العودة للإيواء' => request('returned_to_shelter') ? 'نعم' : null,
            'سبب موثق' => request('documented_reason') ? 'نعم' : null,
            'حالة التجربة' => request('trial_state') ? [
                'active' => 'داخل المدة',
                'today' => 'تنتهي اليوم',
                'soon' => 'تنتهي خلال 7 أيام',
                'expired' => 'منتهية',
            ][request('trial_state')] ?? request('trial_state') : null,
            'سبب الفشل' => request('failure_reason'),
            'الترتيب حسب' => request('sort') ? ($pageConfig['sort_options'][request('sort')] ?? request('sort')) : null,
            'الاتجاه' => request('direction') ? (request('direction') === 'asc' ? 'تصاعدي' : 'تنازلي') : null,
        ])->filter(fn ($value) => filled($value));
    @endphp

    <div class="container-fluid status-overview-page">
        <div class="so-hero">
            <div class="so-hero__title">
                <span class="so-hero__icon"><i class="{{ $headerIcon }}"></i></span>
                <div>
                    <h1>{{ $pageConfig['title'] }}</h1>
                    <div class="so-hero__meta">
                        <span>عدد النتائج الحالية: <strong>{{ number_format($transfers->total()) }}</strong></span>
                        @if($filtersActive)
                            <span class="so-chip"><i class="fas fa-filter"></i> تم تطبيق فلترة</span>
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-2"></i>
                العودة للإدارة الرئيسية
            </a>
        </div>

        <div class="so-stats">
            @foreach($stats as $card)
                <a href="{{ $card['href'] ?? $pageConfig['route'] }}" class="so-stat so-stat--{{ $card['theme'] ?? 'primary' }}" title="اضغط للتصفية">
                    <span class="so-stat__icon"><i class="{{ $card['icon'] }}"></i></span>
                    <span>
                        <span class="so-stat__label d-block">{{ $card['label'] }}</span>
                        <span class="so-stat__value d-block">{{ $card['value'] }}</span>
                        <span class="so-stat__hint d-block">اضغط للتصفية</span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="so-panel so-filters">
            <div class="so-panel__header">
                <h6 class="so-panel__title">
                    <i class="fas fa-filter"></i>
                    {{ helperTrans("ingaz.filters") }}
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="{{ $filtersActive ? 'true' : 'false' }}" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="collapse {{ $filtersActive ? 'show' : '' }}" id="filtersCollapse">
                <div class="so-panel__body">
                    <form method="GET" action="{{ $pageConfig['route'] }}">
                        @if($activeFilters->isNotEmpty())
                            <div class="active-filters-box">
                                <div class="small text-muted mb-2">الفلاتر المطبقة حالياً</div>
                                <div>
                                    @foreach($activeFilters as $label => $value)
                                        <span class="active-filter-chip">
                                            <strong>{{ $label }}:</strong>
                                            <span>{{ $value }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_number") }}</label>
                                <input type="text" class="form-control" name="contract_number" value="{{ request('contract_number') }}" placeholder="ابحث برقم العقد">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.old_sponsor") }}</label>
                                <input type="text" class="form-control" name="old_sponsor" value="{{ request('old_sponsor') }}" placeholder="اسم أو جوال أو هوية">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.new_sponsor") }}</label>
                                <input type="text" class="form-control" name="new_sponsor" value="{{ request('new_sponsor') }}" placeholder="اسم أو جوال أو هوية">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">العاملة</label>
                                <input type="text" class="form-control" name="maid_keyword" value="{{ request('maid_keyword') }}" placeholder="اسم أو رقم جواز">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.date_from") }} {{ $pageConfig['date_label'] }}</label>
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.date_to") }} {{ $pageConfig['date_label'] }}</label>
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.trial_days") }}</label>
                                <select class="form-select" name="trial_days">
                                    <option value="">{{ helperTrans("ingaz.all_trial_days") }}</option>
                                    @foreach([7, 14, 15, 30, 45, 60, 90] as $days)
                                        <option value="{{ $days }}" {{ (string) request('trial_days') === (string) $days ? 'selected' : '' }}>{{ $days }} يوم</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_value") }} من</label>
                                <input type="number" step="0.01" class="form-control" name="amount_from" value="{{ request('amount_from') }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_value") }} إلى</label>
                                <input type="number" step="0.01" class="form-control" name="amount_to" value="{{ request('amount_to') }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الترتيب حسب</label>
                                <select class="form-select" name="sort">
                                    @foreach($pageConfig['sort_options'] as $value => $label)
                                        <option value="{{ $value }}" {{ request('sort', $pageConfig['default_sort']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الاتجاه</label>
                                <select class="form-select" name="direction">
                                    <option value="asc" {{ request('direction', $pageConfig['default_direction']) === 'asc' ? 'selected' : '' }}>تصاعدي</option>
                                    <option value="desc" {{ request('direction', $pageConfig['default_direction']) === 'desc' ? 'selected' : '' }}>تنازلي</option>
                                </select>
                            </div>

                            @if($pageConfig['page'] === 'trial')
                                <div class="col-md-3">
                                    <label class="form-label">حالة التجربة</label>
                                    <select class="form-select" name="trial_state">
                                        <option value="">الكل</option>
                                        <option value="active" {{ request('trial_state') === 'active' ? 'selected' : '' }}>داخل المدة</option>
                                        <option value="today" {{ request('trial_state') === 'today' ? 'selected' : '' }}>تنتهي اليوم</option>
                                        <option value="soon" {{ request('trial_state') === 'soon' ? 'selected' : '' }}>تنتهي خلال 7 أيام</option>
                                        <option value="expired" {{ request('trial_state') === 'expired' ? 'selected' : '' }}>منتهية</option>
                                    </select>
                                </div>
                            @endif

                            @if($pageConfig['page'] === 'failed')
                                <div class="col-md-3">
                                    <label class="form-label">سبب الفشل</label>
                                    <input type="text" class="form-control" name="failure_reason" value="{{ request('failure_reason') }}" placeholder="ابحث بسبب الفشل">
                                </div>
                            @endif
                        </div>

                        <div class="so-filters__actions">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-2"></i>{{ helperTrans("ingaz.search") }}
                            </button>
                            <a href="{{ $pageConfig['route'] }}" class="btn btn-light border">
                                <i class="fas fa-times me-2"></i>{{ helperTrans("ingaz.clear_filters") }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="so-panel">
            <div class="so-panel__header">
                <h6 class="so-panel__title">
                    <i class="fas fa-list"></i>
                    قائمة {{ $pageConfig['title'] }}
                </h6>
                <span class="so-chip">{{ number_format($transfers->total()) }} سجل</span>
            </div>
            <div>
                @if($transfers->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle status-overview-table">
                            <thead>
                                <tr>
                                    <th>{{ helperTrans("ingaz.contract_number") }}</th>
                                    <th>{{ helperTrans("ingaz.old_sponsor") }}</th>
                                    <th>{{ helperTrans("ingaz.new_sponsor") }}</th>
                                    <th>بيانات العاملة</th>
                                    @if($pageConfig['page'] === 'trial')
                                        <th>بداية التجربة</th>
                                        <th>{{ helperTrans("ingaz.trial_days") }}</th>
                                        <th>نهاية التجربة</th>
                                        <th>{{ helperTrans("ingaz.remaining_amount") }}</th>
                                    @elseif($pageConfig['page'] === 'transferred')
                                        <th>تاريخ النقل</th>
                                        <th>{{ helperTrans("ingaz.contract_value") }}</th>
                                        <th>{{ helperTrans("ingaz.paid_amount") }}</th>
                                        <th>نفذ بواسطة</th>
                                    @else
                                        <th>تاريخ الفشل</th>
                                        <th>سبب الفشل</th>
                                        <th>المستحق للعميل</th>
                                        <th>سجل بواسطة</th>
                                    @endif
                                    <th>{{ helperTrans("ingaz.status") }}</th>
                                    <th>{{ helperTrans("ingaz.actions") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers as $transfer)
                                    @php
                                        $contract = $transfer->contract;
                                        $maid = $contract?->maid;
                                        $maidNationality = optional($maid?->nationality)->title ?: optional($maid?->nationality)->name ?: optional($maid?->nationality)->sub_title ?: 'غير محدد';
                                        $trialEndDate = $transfer->trial_end_date;
                                        $daysRemaining = $transfer->trial_days_remaining;
                                        $returnedToShelter = $pageConfig['page'] === 'transferred'
                                            && $contract?->status === \App\Enums\ingaz\ContractStatusEnum::Shelter->value
                                            && (int) $contract?->user_id === (int) $transfer->new_sponsor_id
                                            && !$contract?->info?->is_outside_shelter;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($contract)
                                                <a href="{{ route('contracts.details', $contract->id) }}" class="so-contract-badge" title="عرض العقد">
                                                    <i class="fas fa-file-contract"></i> {{ $contract->id }}
                                                </a>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        @foreach([$transfer->oldSponsor, $transfer->newSponsor] as $sponsor)
                                            <td>
                                                <div class="so-person">
                                                    <span class="so-avatar">{{ mb_substr($sponsor?->name ?? '؟', 0, 1) }}</span>
                                                    <div>
                                                        <div class="so-person__name">{{ $sponsor?->name ?? 'غير محدد' }}</div>
                                                        <div class="so-person__sub">{{ $sponsor?->documentNumber() ?? $sponsor?->phone ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach
                                        <td>
                                            @if($maid)
                                                <div class="so-person">
                                                    <span class="so-avatar so-avatar--maid"><i class="fas fa-user"></i></span>
                                                    <div>
                                                        <div class="so-person__name">{{ $maid->name ?? 'غير محدد' }}</div>
                                                        <div class="so-person__sub">{{ $maid->passport_num ?? 'بدون جواز' }} · {{ $maidNationality }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">لا توجد بيانات</span>
                                            @endif
                                        </td>

                                        @if($pageConfig['page'] === 'trial')
                                            <td>
                                                @if($transfer->effective_trial_start_date)
                                                    <div class="so-date">
                                                        <strong>{{ $transfer->effective_trial_start_date->format('Y-m-d') }}</strong>
                                                        <small>{{ $transfer->effective_trial_start_date->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td><span class="so-chip">{{ $transfer->trial_days }} يوم</span></td>
                                            <td>
                                                @if($trialEndDate)
                                                    <div class="so-date">
                                                        <strong>{{ $trialEndDate->format('Y-m-d') }}</strong>
                                                        @if($daysRemaining > 0)
                                                            <span class="meta-pill meta-primary">باقي {{ $daysRemaining }} يوم</span>
                                                        @elseif($daysRemaining === 0)
                                                            <span class="meta-pill meta-warning">تنتهي اليوم</span>
                                                        @else
                                                            <span class="meta-pill meta-danger">انتهت منذ {{ abs($daysRemaining) }} يوم</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="so-amount {{ (float) $transfer->remaining_amount > 0 ? 'so-amount--due' : 'so-amount--clear' }}">
                                                    {{ number_format((float) $transfer->remaining_amount, 2) }} <small>ريال</small>
                                                </span>
                                            </td>
                                        @elseif($pageConfig['page'] === 'transferred')
                                            <td>
                                                @if($transfer->transferred_at)
                                                    <div class="so-date">
                                                        <strong>{{ $transfer->transferred_at->format('Y-m-d') }}</strong>
                                                        <small>{{ $transfer->transferred_at->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td><span class="so-amount">{{ number_format((float) $transfer->contract_value, 2) }} <small>ريال</small></span></td>
                                            <td><span class="so-amount so-amount--clear">{{ number_format((float) $transfer->paid_amount, 2) }} <small>ريال</small></span></td>
                                            <td>
                                                @if($transfer->transferredBy)
                                                    <div class="so-date">
                                                        <strong>{{ $transfer->transferredBy->name }}</strong>
                                                        <small>{{ $transfer->transferred_at?->diffForHumans() }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        @else
                                            <td>
                                                @if($transfer->failed_at)
                                                    <div class="so-date">
                                                        <strong>{{ $transfer->failed_at->format('Y-m-d') }}</strong>
                                                        <small>{{ $transfer->failed_at->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="reason-box">
                                                    {{ $transfer->failure_reason ?: 'لم يتم تحديد السبب' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="so-amount so-amount--due">
                                                    {{ number_format((float) $transfer->failure_settlement_amount, 2) }} <small>ريال</small>
                                                </div>
                                                <div class="so-person__sub">
                                                    تصفير المتبقي: {{ number_format((float) $transfer->failure_clear_remaining_amount, 2) }} ريال
                                                    @if($transfer->failure_refund_method)
                                                        · {{ $transfer->failure_refund_method_text }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($transfer->failedBy)
                                                    <div class="so-date">
                                                        <strong>{{ $transfer->failedBy->name }}</strong>
                                                        <small>{{ $transfer->failed_at?->diffForHumans() }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td>
                                            <span class="status-pill {{ $statusPillClasses[$pageConfig['theme']] ?? 'status-primary' }}">{{ $transfer->status_text }}</span>
                                        </td>
                                        <td>
                                            <div class="dropdown actions-dropdown">
                                                {{-- strategy: fixed تُخرج القائمة فوق حاوية التمرير الأفقي للجدول فلا تُقص --}}
                                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="الإجراءات المتاحة">
                                                    <i class="fas fa-cogs me-1"></i> الإجراءات
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('SponsorshipTransfer.show', $transfer->id) }}">
                                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                                        </a>
                                                    </li>

                                                    @if($pageConfig['page'] === 'trial')
                                                        @if(!$transfer->invoice && (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_REPLACE_MAID) || @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL)))
                                                        <li>
                                                            <a class="dropdown-item replace-maid-action" href="#"
                                                               data-id="{{ $transfer->id }}" data-maid="{{ $maid?->name }}">
                                                                <i class="fas fa-exchange-alt text-warning"></i> تغيير العاملة
                                                            </a>
                                                        </li>
                                                        @endif
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('SponsorshipTransfer.maid-receipt', $transfer->id) }}">
                                                                <i class="fas fa-file-word text-primary"></i> تحميل نموذج استلام العاملة
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item confirm-action" href="#" data-action="confirm-transfer" data-id="{{ $transfer->id }}" data-text="تأكيد نقل الخدمات وإنهاء فترة التجربة">
                                                                <i class="fas fa-check-circle"></i> تأكيد النقل
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item cancel-transfer-action" href="#"
                                                                data-id="{{ $transfer->id }}"
                                                                data-contract-value="{{ (float) $transfer->contract_value }}"
                                                                data-paid-amount="{{ (float) $transfer->paid_amount }}"
                                                                data-remaining-amount="{{ (float) $transfer->remaining_amount }}">
                                                                <i class="fas fa-times-circle"></i> إلغاء النقل
                                                            </a>
                                                        </li>
                                                    @else
                                                        @if($contract)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('contracts.details', $contract->id) }}">
                                                                    <i class="fas fa-file-contract"></i> عرض العقد
                                                                </a>
                                                            </li>
                                                        @endif

                                                        @if($pageConfig['page'] === 'failed')
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('ShelterContract.availableForTransfer') }}">
                                                                    <i class="fas fa-home"></i> عرض في الإيواء
                                                                </a>
                                                            </li>
                                                        @endif

                                                        @if($pageConfig['page'] === 'transferred')
                                                            @if($returnedToShelter)
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="{{ route('ShelterContract.availableForTransfer') }}">
                                                                        <i class="fas fa-house-circle-check"></i> موجودة داخل الإيواء
                                                                    </a>
                                                                </li>
                                                            @elseif(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CONTROL))
                                                                <li>
                                                                    <a class="dropdown-item return-to-shelter-action" href="#"
                                                                        data-url="{{ route('SponsorshipTransfer.returnToShelter', $transfer->id) }}"
                                                                        data-maid="{{ $maid?->name }}"
                                                                        data-sponsor="{{ $transfer->newSponsor?->name }}">
                                                                        <i class="fas fa-house-user"></i> إعادة العاملة للإيواء
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        @endif
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($transfers->hasPages())
                        <div class="so-panel__footer">
                            {{ $transfers->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                @else
                    <div class="so-empty">
                        <span class="so-empty__icon"><i class="{{ $pageConfig['empty_icon'] }}"></i></span>
                        <h5 class="fw-bold mb-1">{{ $pageConfig['empty_title'] }}</h5>
                        <p class="text-muted mb-0">{{ $pageConfig['empty_message'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if($pageConfig['page'] === 'failed' && $topFailureReasons->count() > 0)
            <div class="so-panel">
                <div class="so-panel__header">
                    <h6 class="so-panel__title">
                        <i class="fas fa-chart-bar"></i>
                        أكثر أسباب الفشل تكراراً
                    </h6>
                </div>
                <div>
                    @foreach($topFailureReasons as $reason)
                        <div class="so-reason-row">
                            <span>{{ $reason->failure_reason }}</span>
                            <span class="status-pill status-danger">{{ $reason->total }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @if($pageConfig['page'] === 'trial')
    <div class="modal fade so-modal status-overview-page" id="replaceMaidModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" id="replaceMaidForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="so-modal__title-icon"><i class="fas fa-exchange-alt"></i></span>
                        تغيير العاملة خلال فترة التجربة
                    </h5>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="so-current-maid">
                        <span class="so-avatar so-avatar--maid"><i class="fas fa-user"></i></span>
                        <div>
                            <div class="so-person__sub">العاملة الحالية</div>
                            <div class="so-person__name" id="replaceCurrentMaid">—</div>
                        </div>
                    </div>

                    <div class="so-note so-note--warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>سيبقى العميل وقيمة نقل الكفالة والمدفوعات كما هي، وسيتم استبدال العاملة والعقد المرتبط فقط. لا يمكن تنفيذ التغيير بعد إصدار الفاتورة.</span>
                    </div>

                    <div class="mb-3">
                        <label class="so-field-label" id="replacementMaidLabel" for="replacementContractId">العاملة البديلة</label>
                        <select class="form-select" id="replacementContractId" required><option value="">اختر العاملة...</option></select>
                        <div class="so-note so-note--info mt-2 mb-0">
                            <i class="fas fa-info-circle"></i>
                            <span id="replacementSelectionExplanation">جارٍ تحديد نوع العاملات المتاح اختيارهن...</span>
                        </div>
                    </div>

                    <span class="so-field-label">هل تصبح العاملة القديمة متاحة؟</span>
                    <label class="so-choice" for="oldAvailableYes">
                        <input class="form-check-input" type="radio" name="make_old_maid_available" id="oldAvailableYes" value="1" checked>
                        <span>
                            <span class="so-choice__title d-block">نعم، تصبح متاحة</span>
                            <span class="so-choice__desc d-block">تُعاد إلى الإيواء وتظهر كعاملة متاحة للعمل والنقل مرة أخرى.</span>
                        </span>
                    </label>
                    <label class="so-choice mb-0" for="oldAvailableNo">
                        <input class="form-check-input" type="radio" name="make_old_maid_available" id="oldAvailableNo" value="0">
                        <span>
                            <span class="so-choice__title d-block">لا، تبقى غير متاحة</span>
                            <span class="so-choice__desc d-block">تُعاد إلى الإيواء مع الاحتفاظ بها كغير متاحة، ولا تظهر ضمن العاملات المتاحات.</span>
                        </span>
                    </label>
                </div>
                <div class="modal-footer justify-content-start gap-2">
                    <button class="btn btn-warning px-4" type="submit"><i class="fas fa-check me-1"></i> تأكيد تغيير العاملة</button>
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script>
        $(document).ready(function () {
            @if($pageConfig['page'] === 'trial')
            let replacementTransferId = null;
            const replacementModalElement = document.getElementById('replaceMaidModal');
            const replacementModal = replacementModalElement ? new bootstrap.Modal(replacementModalElement) : null;

            $(document).on('click', '.replace-maid-action', function (event) {
                event.preventDefault();
                replacementTransferId = $(this).data('id');
                $('#replaceCurrentMaid').text($(this).data('maid') || 'غير محددة');
                $('#replacementSelectionExplanation').text('اكتب اسم العاملة أو رقم جوازها للبحث في العاملات المتاحات.');
                replacementModal.show();

                const url = @json(route('SponsorshipTransfer.replacementMaids', ['id' => '__ID__'])).replace('__ID__', replacementTransferId);
                const select = $('#replacementContractId');
                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }
                select.empty().append(new Option('', '', true, false)).select2({
                    width: '100%',
                    dir: 'rtl',
                    dropdownParent: $('#replaceMaidModal'),
                    placeholder: 'ابحث باسم العاملة أو رقم الجواز...',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: url,
                        dataType: 'json',
                        delay: 300,
                        cache: true,
                        data: params => ({q: params.term || ''}),
                        processResults: function (response) {
                            $('#replacementSelectionExplanation').text(response.selection_explanation || 'تظهر العاملات الموجودات داخل الإيواء والراغبات في العمل.');
                            $('#replacementMaidLabel').text(response.current_worker_type === 'company'
                                ? 'العاملة البديلة من عاملات الشركة'
                                : 'العاملة البديلة من العاملات داخل الإيواء');
                            return {results: response.results || []};
                        },
                        error: function (xhr) {
                            Swal.fire('تعذر التحميل', xhr.responseJSON?.message || 'تعذر تحميل العاملات المتاحة.', 'error');
                        }
                    },
                    language: {
                        inputTooShort: () => 'اكتب للبحث عن العاملة',
                        searching: () => 'جارٍ البحث...',
                        noResults: () => 'لا توجد عاملات مطابقات ومتاحات'
                    }
                });

                $(replacementModalElement).one('shown.bs.modal', () => select.select2('open'));
            });

            $('#replaceMaidForm').on('submit', function (event) {
                event.preventDefault();
                const submit = $(this).find('button[type="submit"]').prop('disabled', true);
                const url = @json(route('SponsorshipTransfer.replaceMaid', ['id' => '__ID__'])).replace('__ID__', replacementTransferId);
                const replacementName = $('#replacementContractId option:selected').text();
                const makeAvailable = $('input[name="make_old_maid_available"]:checked').val();
                const oldMaidResult = makeAvailable === '1'
                    ? 'ستعود العاملة القديمة للإيواء وستصبح متاحة للعمل.'
                    : 'ستعود العاملة القديمة للإيواء ولكن ستظل غير متاحة.';

                Swal.fire({
                    title: 'تأكيد استبدال العاملة',
                    text: `سيتم اختيار ${replacementName} بدلاً من العاملة الحالية. ${oldMaidResult} لن تتغير بيانات العميل أو المبالغ والمدفوعات.`,
                    icon: 'warning',
                    showCancelButton: true,
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-warning px-4 mx-1',
                        cancelButton: 'btn btn-light border px-4 mx-1'
                    },
                    confirmButtonText: 'نعم، تنفيذ التغيير',
                    cancelButtonText: 'رجوع'
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        submit.prop('disabled', false);
                        return;
                    }

                    $.ajax({url, type: 'POST', data: {
                        _token: @json(csrf_token()),
                        replacement_contract_id: $('#replacementContractId').val(),
                        make_old_maid_available: makeAvailable
                    }}).done(function (response) {
                        replacementModal.hide();
                        Swal.fire('تم بنجاح', response.message, 'success').then(() => window.location.reload());
                    }).fail(function (xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        Swal.fire('تعذر التغيير', Object.values(errors)[0]?.[0] || xhr.responseJSON?.message || 'حدث خطأ غير متوقع.', 'error');
                    }).always(() => submit.prop('disabled', false));
                });
            });
            @endif

            const filtersCollapse = document.getElementById('filtersCollapse');
            const filtersIcon = document.getElementById('filtersIcon');

            if (filtersCollapse && filtersIcon) {
                filtersCollapse.addEventListener('show.bs.collapse', function () {
                    filtersIcon.classList.remove('fa-chevron-down');
                    filtersIcon.classList.add('fa-chevron-up');
                });

                filtersCollapse.addEventListener('hide.bs.collapse', function () {
                    filtersIcon.classList.remove('fa-chevron-up');
                    filtersIcon.classList.add('fa-chevron-down');
                });

                if (filtersCollapse.classList.contains('show')) {
                    filtersIcon.classList.remove('fa-chevron-down');
                    filtersIcon.classList.add('fa-chevron-up');
                }
            }

            @if($pageConfig['page'] === 'trial')
                $(document).on('click', '.confirm-action', function (event) {
                    event.preventDefault();

                    const action = $(this).data('action');
                    const id = $(this).data('id');
                    const text = $(this).data('text');

                    Swal.fire({
                        title: 'تأكيد العملية',
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-success px-4 mx-1',
                            cancelButton: 'btn btn-light border px-4 mx-1'
                        },
                        confirmButtonText: '<i class="fas fa-check me-1"></i> تأكيد',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performAction(action, id);
                        }
                    });
                });

                $(document).on('click', '.cancel-transfer-action', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const id = button.data('id');
                    const contractValue = Number(button.data('contract-value')) || 0;
                    const paidAmount = Number(button.data('paid-amount')) || 0;
                    const remainingAmount = Number(button.data('remaining-amount')) || 0;
                    const money = (value) => Number(value || 0).toLocaleString('ar-SA', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    Swal.fire({
                        title: '<span class="tf-title-icon"><i class="fas fa-user-times"></i></span> تأكيد فشل التجربة',
                        html: `
                            <div dir="rtl">
                                <div class="tf-summary">
                                    <div class="tf-summary__item">
                                        <div class="tf-summary__label">قيمة العقد</div>
                                        <div class="tf-summary__value">${money(contractValue)} <small>ريال</small></div>
                                    </div>
                                    <div class="tf-summary__item tf-summary__item--paid">
                                        <div class="tf-summary__label">الدائن (المدفوع)</div>
                                        <div class="tf-summary__value">${money(paidAmount)} <small>ريال</small></div>
                                    </div>
                                    <div class="tf-summary__item tf-summary__item--due">
                                        <div class="tf-summary__label">المدين (المتبقي)</div>
                                        <div class="tf-summary__value">${money(remainingAmount)} <small>ريال</small></div>
                                    </div>
                                    <div class="tf-summary__item">
                                        <div class="tf-summary__label">إجمالي الخصومات</div>
                                        <div class="tf-summary__value">0.00 <small>ريال</small></div>
                                    </div>
                                </div>

                                <div class="tf-section">
                                    <div class="tf-section__title">
                                        <span class="tf-section__icon"><i class="fas fa-hand-holding-usd"></i></span>
                                        الاسترجاع للعميل
                                        <span class="tf-tag">يُسجَّل كمستحق للعميل</span>
                                        <input type="hidden" id="failureSettlementType" value="refund">
                                    </div>
                                    <div class="tf-grid">
                                        <div>
                                            <label class="tf-label" for="failureSettlementAmount">مبلغ الاسترجاع</label>
                                            <input type="number" id="failureSettlementAmount" class="form-control" min="0" step="0.01" value="${paidAmount.toFixed(2)}">
                                            <div class="tf-hint">محسوب تلقائياً من المبلغ المدفوع ويمكن تعديله</div>
                                        </div>
                                        <div>
                                            <span class="tf-label">طريقة السداد</span>
                                            <input type="hidden" id="failureRefundMethod" value="bank">
                                            <div class="tf-segment" role="group" aria-label="طريقة السداد">
                                                <button type="button" class="active" data-refund-method="bank"><i class="fas fa-university"></i> تحويل بنكي</button>
                                                <button type="button" data-refund-method="cash"><i class="fas fa-money-bill-wave"></i> نقدي</button>
                                            </div>
                                        </div>
                                        <div class="tf-span-2">
                                            <label class="tf-label" id="failureRefundAccountLabel" for="failureRefundAccount">البنك / حساب الصرف <span class="text-danger">*</span></label>
                                            <select id="failureRefundAccount" class="form-select">
                                                <option value="">اختر الحساب الذي سيصدر عليه سند الصرف</option>
                                                @foreach($receiptAccounts as $account)
                                                    <option value="{{ $account->id }}" data-cash="{{ (int) $account->getRawOriginal('type') === \Nami\Finance\Enums\AccountTypesEnum::Box->value ? 1 : 0 }}">{{ e($account->code . ' - ' . $account->name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="tf-grid tf-span-2" id="failureBankRefundFields">
                                            <div>
                                                <label class="tf-label" for="failureClientIban">آيبان العميل <span class="text-danger">*</span></label>
                                                <input type="text" id="failureClientIban" class="form-control" dir="ltr" maxlength="34" placeholder="SA0000000000000000000000">
                                            </div>
                                            <div>
                                                <label class="tf-label" for="failureClientIbanAttachment">مرفق الآيبان <span class="text-danger">*</span></label>
                                                <input type="file" id="failureClientIbanAttachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                            </div>
                                            <div class="tf-hint tf-span-2 mt-0">يكفي إدخال الآيبان أو إرفاق صورته.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tf-section">
                                    <div class="tf-section__title">
                                        <span class="tf-section__icon tf-section__icon--warning"><i class="fas fa-file-invoice-dollar"></i></span>
                                        المتبقي على العميل
                                    </div>
                                    <div class="so-note so-note--warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>فشل النقل لا يُعد سداداً؛ سيبقى المتبقي كما هو ما لم تسجل مبلغ شطب صراحةً.</span>
                                    </div>
                                    <label class="tf-label" for="failureClearRemainingAmount">مبلغ شطب المتبقي</label>
                                    <input type="number" id="failureClearRemainingAmount" class="form-control" min="0" step="0.01" value="0.00">
                                    <div class="tf-hint">اتركه صفراً إذا لم يحدث سداد أو شطب فعلي</div>
                                </div>

                                <div class="tf-preview" id="failureSettlementPreview"></div>

                                <label class="tf-label" for="failureReason">سبب الفشل</label>
                                <textarea id="failureReason" class="form-control" rows="3" placeholder="اكتب سبب الفشل أو الإلغاء"></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        showCloseButton: true,
                        closeButtonHtml: '<i class="fas fa-times"></i>',
                        closeButtonAriaLabel: 'إغلاق',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'tf-swal',
                            confirmButton: 'tf-btn tf-btn--danger',
                            cancelButton: 'tf-btn tf-btn--ghost'
                        },
                        confirmButtonText: '<i class="fas fa-save"></i><span>حفظ وإلغاء النقل</span>',
                        cancelButtonText: 'إلغاء',
                        didOpen: () => {
                            const updatePreview = () => {
                                const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                                const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                                $('#failureSettlementPreview').html(`
                                    <div><div class="tf-preview__label">الدائن بعد التسوية</div><div class="tf-preview__value">${money(paidAmount)} ريال</div></div>
                                    <div><div class="tf-preview__label">المدين بعد التسوية</div><div class="tf-preview__value">${money(Math.max(0, remainingAmount - clearRemainingAmount))} ريال</div></div>
                                    <div><div class="tf-preview__label">المستحق للعميل</div><div class="tf-preview__value">${money(settlementAmount)} ريال</div></div>
                                `);
                            };

                            $('#failureSettlementAmount, #failureClearRemainingAmount').on('input', updatePreview);
                            updatePreview();

                            $('.tf-segment [data-refund-method]').on('click', function() {
                                $('.tf-segment [data-refund-method]').removeClass('active');
                                $(this).addClass('active');
                                $('#failureRefundMethod').val($(this).data('refund-method')).trigger('change');
                            });

                            // السداد النقدي: صناديق فقط وبدون آيبان؛ البنكي (الافتراضي): حسابات بنكية مع الآيبان
                            const applyRefundMethod = () => {
                                const isCash = $('#failureRefundMethod').val() === 'cash';
                                $('#failureBankRefundFields').toggle(!isCash);
                                $('#failureRefundAccountLabel').html((isCash ? 'الصندوق / حساب الصرف' : 'البنك / حساب الصرف') + ' <span class="text-danger">*</span>');
                                $('#failureRefundAccount option[value!=""]').each(function() {
                                    const matches = String($(this).data('cash')) === (isCash ? '1' : '0');
                                    $(this).prop('hidden', !matches).prop('disabled', !matches);
                                });
                                if ($('#failureRefundAccount option:selected').prop('disabled')) {
                                    $('#failureRefundAccount').val('');
                                }
                            };

                            $('#failureRefundMethod').on('change', applyRefundMethod);
                            applyRefundMethod();
                        },
                        preConfirm: () => {
                            const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                            const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                            const iban = ($('#failureClientIban').val() || '').trim();
                            const attachment = $('#failureClientIbanAttachment')[0]?.files?.[0] || null;
                            const refundAccountId = $('#failureRefundAccount').val();
                            const refundMethod = $('#failureRefundMethod').val() || 'bank';

                            if (settlementAmount > paidAmount) {
                                Swal.showValidationMessage('مبلغ الاسترجاع لا يمكن أن يكون أكبر من المبلغ المدفوع.');
                                return false;
                            }

                            if (clearRemainingAmount > remainingAmount) {
                                Swal.showValidationMessage('مبلغ تصفير المتبقي لا يمكن أن يكون أكبر من المبلغ المتبقي.');
                                return false;
                            }

                            if (refundMethod === 'bank' && settlementAmount > 0 && !iban && !attachment) {
                                Swal.showValidationMessage('يرجى إدخال آيبان العميل أو إرفاق مرفق الآيبان.');
                                return false;
                            }

                            if (settlementAmount > 0 && !refundAccountId) {
                                Swal.showValidationMessage(refundMethod === 'cash'
                                    ? 'يرجى اختيار الصندوق لإصدار سند الصرف.'
                                    : 'يرجى اختيار البنك أو حساب الصرف لإصدار سند الصرف.');
                                return false;
                            }

                            return {
                                failure_reason: ($('#failureReason').val() || '').trim(),
                                failure_settlement_type: $('#failureSettlementType').val(),
                                failure_refund_method: refundMethod,
                                failure_settlement_amount: settlementAmount,
                                failure_clear_remaining_amount: clearRemainingAmount,
                                failure_client_iban: refundMethod === 'bank' ? iban : '',
                                refund_account_id: refundAccountId,
                                failure_client_iban_attachment: refundMethod === 'bank' ? attachment : null
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performCancelTransfer(id, result.value);
                        }
                    });
                });

                function performCancelTransfer(id, payload) {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('failure_reason', payload.failure_reason || '');
                    formData.append('failure_settlement_type', payload.failure_settlement_type || 'refund');
                    formData.append('failure_refund_method', payload.failure_refund_method || 'bank');
                    formData.append('failure_settlement_amount', payload.failure_settlement_amount || 0);
                    formData.append('failure_clear_remaining_amount', payload.failure_clear_remaining_amount || 0);
                    formData.append('failure_client_iban', payload.failure_client_iban || '');
                    formData.append('refund_account_id', payload.refund_account_id || '');

                    if (payload.failure_client_iban_attachment) {
                        formData.append('failure_client_iban_attachment', payload.failure_client_iban_attachment);
                    }

                    $.ajax({
                        url: `/admin/SponsorshipTransfer/${id}/cancel-transfer`,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            if (response.success) {
                                const hasAccountingWarning = Boolean(response.accounting_warning);
                                Swal.fire(
                                    hasAccountingWarning ? 'تمت العملية مع تنبيه محاسبي' : 'نجح!',
                                    response.message,
                                    hasAccountingWarning ? 'warning' : 'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            const message = Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
                            Swal.fire('خطأ!', message, 'error');
                        }
                    });
                }

                function performAction(action, id, payload = {}) {
                    $.ajax({
                        url: `/admin/SponsorshipTransfer/${id}/${action}`,
                        type: 'POST',
                        data: Object.assign({
                            _token: '{{ csrf_token() }}'
                        }, payload),
                        success: function(response) {
                            if (response.success) {
                                const hasAccountingWarning = Boolean(response.accounting_warning);
                                Swal.fire(
                                    hasAccountingWarning ? 'تمت العملية مع تنبيه محاسبي' : 'نجح!',
                                    response.message,
                                    hasAccountingWarning ? 'warning' : 'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
                            Swal.fire('خطأ!', message, 'error');
                        }
                    });
                }
            @endif

            @if($pageConfig['page'] === 'transferred')
                $(document).on('click', '.return-to-shelter-action', function (event) {
                    event.preventDefault();

                    const button = $(this);
                    const maid = button.data('maid') || 'العاملة';
                    const sponsor = button.data('sponsor') || 'الكفيل الحالي';

                    Swal.fire({
                        title: 'إعادة العاملة إلى الإيواء؟',
                        text: `سيتم إدخال ${maid} إلى الإيواء وإتاحتها لنقل الخدمات من ${sponsor} إلى كفيل آخر.`,
                        icon: 'question',
                        showCancelButton: true,
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-success px-4 mx-1',
                            cancelButton: 'btn btn-light border px-4 mx-1'
                        },
                        confirmButtonText: '<i class="fas fa-house-user me-1"></i> نعم، إعادة للإيواء',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: button.data('url'),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            success: function (response) {
                                if (!response.success) {
                                    Swal.fire('خطأ!', response.message, 'error');
                                    return;
                                }

                                Swal.fire({
                                    title: 'تمت الإعادة بنجاح',
                                    text: response.message,
                                    icon: 'success',
                                    showCancelButton: Boolean(response.redirect),
                                    confirmButtonText: response.redirect ? 'عرض المتاح للنقل' : 'حسناً',
                                    cancelButtonText: 'البقاء في الصفحة'
                                }).then((alertResult) => {
                                    if (alertResult.isConfirmed && response.redirect) {
                                        window.location.href = response.redirect;
                                        return;
                                    }

                                    window.location.reload();
                                });
                            },
                            error: function (xhr) {
                                const message = xhr.responseJSON?.message
                                    || Object.values(xhr.responseJSON?.errors || {})[0]?.[0]
                                    || 'حدث خطأ غير متوقع';
                                Swal.fire('خطأ!', message, 'error');
                            }
                        });
                    });
                });
            @endif
        });
    </script>
@endsection
