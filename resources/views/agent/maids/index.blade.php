@extends('agent.layout')

@php
    use App\Models\ingaz\Maid;
    $isAr = app()->getLocale() === 'ar';
    $baseQuery = request()->except(['page']);

    $workerTabs = [
        'all' => [
            'label' => $isAr ? 'كل العمالة' : 'All Workers',
            'icon' => 'fa-users',
            'color' => '#0f766e',
        ],
        'available' => [
            'label' => $isAr ? 'المتاحة' : 'Available',
            'icon' => 'fa-circle-check',
            'color' => '#16a34a',
        ],
        'contracted' => [
            'label' => $isAr ? 'متعاقد عليها' : 'Contracted',
            'icon' => 'fa-file-signature',
            'color' => '#2563eb',
        ],
    ];

    $submissionTabs = [
        'all' => [
            'label' => $isAr ? 'الكل' : 'All',
            'icon' => 'fa-layer-group',
            'color' => '#64748b',
        ],
        'approved' => [
            'label' => $isAr ? 'متوافق عليها' : 'Approved',
            'icon' => 'fa-circle-check',
            'color' => '#16a34a',
        ],
        'pending' => [
            'label' => $isAr ? 'بانتظار المراجعة' : 'Pending Review',
            'icon' => 'fa-hourglass-half',
            'color' => '#f59e0b',
        ],
        'rejected' => [
            'label' => $isAr ? 'مرفوضة' : 'Rejected',
            'icon' => 'fa-circle-xmark',
            'color' => '#dc2626',
        ],
    ];
@endphp

@section('page-title', $isAr ? 'العمالة' : 'Workers')

@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            --ml-primary: #0f766e;
            --ml-primary-dark: #115e59;
            --ml-accent: #2563eb;
            --ml-ink: #0f172a;
            --ml-muted: #64748b;
            --ml-line: #e2e8f0;
            --ml-bg-soft: #f8fafc;
        }

        /* ---------- Hero ---------- */
        .ml-hero {
            position: relative;
            border-radius: 18px;
            padding: 22px 24px;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #1e3a8a 100%);
            color: #fff;
            box-shadow: 0 18px 38px -18px rgba(15, 118, 110, .65);
            overflow: hidden;
            margin-bottom: 18px;
        }
        .ml-hero::after {
            content: ""; position: absolute; inset: auto -60px -80px auto;
            width: 240px; height: 240px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%);
        }
        .ml-hero__row { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; position: relative; z-index: 1; }
        .ml-hero__title { display: flex; align-items: center; gap: 14px; }
        .ml-hero__icon { width: 52px; height: 52px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.14); backdrop-filter: blur(8px); font-size: 22px; }
        .ml-hero h2 { margin: 0; font-size: 22px; font-weight: 800; }
        .ml-hero p { margin: 4px 0 0; color: rgba(255,255,255,.82); font-size: 13px; }
        .ml-hero__actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .ml-hero__btn {
            border: 1px solid rgba(255,255,255,.35);
            background: rgba(255,255,255,.12);
            color: #fff; border-radius: 10px; padding: 9px 16px;
            font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; white-space: nowrap;
            transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
        }
        .ml-hero__btn:hover { background: rgba(255,255,255,.22); color: #fff; transform: translateY(-1px); text-decoration: none; box-shadow: 0 8px 18px -10px rgba(0,0,0,.35); }
        .ml-hero__btn--rapid { background: linear-gradient(135deg, #f59e0b, #d97706); border-color: rgba(245,158,11,.55); }
        .ml-hero__btn--rapid:hover { background: linear-gradient(135deg, #d97706, #b45309); }
        .ml-hero__btn--import { background: linear-gradient(135deg, #16a34a, #15803d); border-color: rgba(34,197,94,.55); }
        .ml-hero__btn--import:hover { background: linear-gradient(135deg, #15803d, #14532d); }

        /* ---------- Stat cards ---------- */
        .ml-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .ml-stat {
            background: #fff;
            border: 1px solid var(--ml-line);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex; align-items: center; gap: 12px;
            box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .08);
            position: relative;
            overflow: hidden;
            transition: box-shadow .25s ease, transform .25s ease;
            text-decoration: none;
        }
        .ml-stat:hover { box-shadow: 0 12px 28px -18px rgba(15, 23, 42, .18); transform: translateY(-1px); text-decoration: none; }
        .ml-stat__icon { width: 46px; height: 46px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .ml-stat__body { display: flex; flex-direction: column; min-width: 0; }
        .ml-stat__label { color: var(--ml-muted); font-size: 12px; font-weight: 600; }
        .ml-stat__value { font-size: 22px; font-weight: 800; color: var(--ml-ink); margin-top: 2px; }
        .ml-stat::after { content:""; position: absolute; inset: auto 0 0 0; height: 3px; background: var(--bar, var(--ml-primary)); }

        /* ---------- Toolbar (filters + search) ---------- */
        .ml-toolbar {
            background: #fff;
            border: 1px solid var(--ml-line);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .08);
            margin-bottom: 14px;
        }
        .ml-toolbar__title { font-size: 12px; font-weight: 700; color: var(--ml-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .ml-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .ml-chip {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1.5px solid var(--ml-line);
            background: #fff;
            color: var(--ml-ink);
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            transition: border-color .2s ease, background .2s ease, transform .15s ease, color .2s ease;
        }
        .ml-chip:hover { border-color: var(--chip-color, var(--ml-primary)); color: var(--chip-color, var(--ml-primary)); text-decoration: none; transform: translateY(-1px); }
        .ml-chip.is-active {
            background: var(--chip-color, var(--ml-primary));
            color: #fff !important;
            border-color: var(--chip-color, var(--ml-primary));
        }
        .ml-chip__count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 22px; height: 22px; padding: 0 6px;
            border-radius: 999px; background: rgba(15, 23, 42, .08);
            font-size: 11px; color: var(--ml-ink);
        }
        .ml-chip.is-active .ml-chip__count { background: rgba(255,255,255,.25); color: #fff; }

        .ml-toolbar__row + .ml-toolbar__row { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--ml-line); }
        .ml-search {
            display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;
        }
        .ml-search input {
            flex: 1; min-width: 0; min-height: 42px;
            border: 1.5px solid var(--ml-line);
            border-radius: 10px;
            padding: 0 14px;
            font-size: 14px;
            background: var(--ml-bg-soft);
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .ml-search__date { max-width: 170px; }
        .ml-date-field { display: flex; flex-direction: column; gap: 3px; min-width: 155px; }
        .ml-date-field label { font-size: 11px; font-weight: 700; color: var(--ml-muted); }
        .ml-search__clear {
            min-height: 42px;
            padding: 0 14px;
            border: 1.5px solid var(--ml-line);
            border-radius: 10px;
            color: var(--ml-muted);
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            white-space: nowrap;
        }
        .ml-search__clear:hover { color: var(--ml-ink); background: var(--ml-bg-soft); text-decoration: none; }
        .ml-search input:focus {
            outline: none; background: #fff;
            border-color: var(--ml-primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }
        .ml-search button {
            border: 0;
            background: linear-gradient(135deg, var(--ml-primary), var(--ml-primary-dark));
            color: #fff;
            border-radius: 10px;
            padding: 0 18px;
            min-height: 42px;
            font-weight: 700;
            display: inline-flex; align-items: center; gap: 7px;
            cursor: pointer;
            transition: transform .15s ease;
        }
        .ml-search button:hover { transform: translateY(-1px); }

        /* ---------- Worker cards (modern grid) ---------- */
        .ml-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; }
        .ml-card {
            background: #fff;
            border: 1px solid var(--ml-line);
            border-radius: 16px;
            padding: 16px;
            display: flex; flex-direction: column; gap: 12px;
            box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .08);
            transition: box-shadow .25s ease, transform .25s ease, border-color .2s ease;
            position: relative;
            overflow: hidden;
        }
        .ml-card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #cbd5e1;
        }
        .ml-card--available::before { background: #16a34a; }
        .ml-card--contracted::before { background: #2563eb; }
        .ml-card--available { border-color: rgba(22, 163, 74, .2); }
        .ml-card--contracted { border-color: rgba(37, 99, 235, .2); }
        .ml-card__select {
            position: absolute;
            inset-inline-start: 12px;
            top: 12px;
            width: 19px;
            height: 19px;
            accent-color: var(--ml-primary);
            z-index: 2;
        }
        .ml-card:hover {
            box-shadow: 0 18px 32px -18px rgba(15, 23, 42, .25);
            transform: translateY(-3px);
            border-color: rgba(15, 118, 110, .3);
        }
        .ml-card__top { display: flex; gap: 12px; align-items: flex-start; }
        .ml-avatar {
            width: 72px; height: 72px;
            border-radius: 14px;
            object-fit: cover;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: inline-flex;
            align-items: center; justify-content: center;
            color: var(--ml-muted);
            font-size: 22px;
            border: 2px solid #fff;
            box-shadow: 0 6px 14px -8px rgba(15, 23, 42, .3);
            flex-shrink: 0;
        }
        .ml-card__info { flex: 1; min-width: 0; }
        .ml-card__name { font-weight: 800; font-size: 15px; color: var(--ml-ink); margin: 0 0 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ml-card__id { display: inline-block; font-size: 11px; color: var(--ml-muted); background: var(--ml-bg-soft); padding: 2px 8px; border-radius: 6px; }

        .ml-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 999px;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .3px;
        }
        .ml-badge--available { background: rgba(22, 163, 74, .12); color: #166534; }
        .ml-badge--contracted { background: rgba(37, 99, 235, .12); color: #1e40af; }
        .ml-badge--reserved { background: rgba(245, 158, 11, .14); color: #92400e; }
        .ml-badge--default { background: rgba(100, 116, 139, .14); color: #475569; }

        .ml-sub-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 9px; border-radius: 999px;
            font-size: 10.5px; font-weight: 700;
        }
        .ml-card__status-row {
            min-height: 24px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-inline-start: 28px;
        }
        .ml-sub-badge--pending { background: rgba(245, 158, 11, .14); color: #92400e; border: 1px solid rgba(245, 158, 11, .35); }
        .ml-sub-badge--approved { background: rgba(22, 163, 74, .12); color: #166534; border: 1px solid rgba(22, 163, 74, .35); }
        .ml-sub-badge--rejected { background: rgba(220, 38, 38, .12); color: #991b1b; border: 1px solid rgba(220, 38, 38, .35); }

        .ml-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ml-meta__item { background: var(--ml-bg-soft); border-radius: 10px; padding: 8px 10px; display: flex; flex-direction: column; gap: 2px; }
        .ml-meta__label { color: var(--ml-muted); font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .ml-meta__value { font-size: 13px; color: var(--ml-ink); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .ml-card__actions { display: flex; flex-wrap: wrap; gap: 6px; padding-top: 10px; border-top: 1px dashed var(--ml-line); }
        .ml-card__actions > * { min-width: calc(50% - 3px); }
        .ml-card__actions form { flex: 1; display: flex; margin: 0; }
        .ml-action-btn {
            flex: 1; min-height: 36px;
            border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            font-size: 12.5px; font-weight: 700;
            text-decoration: none;
            transition: transform .15s ease, background .2s ease;
            border: 1.5px solid transparent;
            cursor: pointer;
        }
        .ml-action-btn--view {
            background: linear-gradient(135deg, var(--ml-primary), var(--ml-primary-dark));
            color: #fff;
        }
        .ml-action-btn--view:hover { color: #fff; transform: translateY(-1px); text-decoration: none; }
        .ml-action-btn--notes {
            background: #fff; color: var(--ml-ink);
            border-color: var(--ml-line);
        }
        .ml-action-btn--notes:hover { background: var(--ml-bg-soft); color: var(--ml-ink); }
        .ml-action-btn--request {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }
        .ml-action-btn--request:hover { background: #ffedd5; color: #9a3412; }
        .ml-action-btn--pending {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
            cursor: default;
        }
        .ml-action-btn--backout {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .ml-action-btn--backout:hover { background: #fee2e2; color: #991b1b; }

        .ml-bulk-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 12px 14px;
            margin-bottom: 14px;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            background: #fff7ed;
        }

        .ml-results-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 18px 0 12px;
        }
        .ml-results-title { margin: 0; color: var(--ml-ink); font-size: 17px; font-weight: 800; }
        .ml-results-subtitle { margin: 3px 0 0; color: var(--ml-muted); font-size: 12px; }
        .ml-results-legend { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .ml-legend-item { display: inline-flex; align-items: center; gap: 6px; color: var(--ml-muted); font-size: 12px; font-weight: 700; }
        .ml-legend-dot { width: 9px; height: 9px; border-radius: 50%; }

        /* ---------- Empty state ---------- */
        .ml-empty {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border: 1.5px dashed var(--ml-line);
            border-radius: 16px;
        }
        .ml-empty__icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--ml-bg-soft);
            color: var(--ml-muted);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; margin-bottom: 12px;
        }
        .ml-empty__title { font-weight: 800; color: var(--ml-ink); margin: 0 0 4px; }
        .ml-empty__text { color: var(--ml-muted); font-size: 13px; margin: 0 0 14px; }

        /* ---------- Pagination ---------- */
        .ml-pagination-wrap {
            margin-top: 22px;
            padding: 14px;
            border: 1px solid var(--ml-line);
            border-radius: 14px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .ml-pagination-summary { color: var(--ml-muted); font-size: 12px; font-weight: 700; }
        .ml-pagination { display: flex; justify-content: center; }
        .ml-pagination nav { display: block; }
        .ml-pagination .pagination {
            gap: 6px; margin: 0; padding: 0;
            list-style: none;
            display: inline-flex; align-items: center; flex-wrap: wrap; justify-content: center;
        }
        .ml-pagination .page-item { margin: 0 !important; }
        .ml-pagination .page-item .page-link {
            border-radius: 10px !important;
            min-width: 40px; height: 40px;
            padding: 0 12px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1.5px solid var(--ml-line);
            background: #fff;
            color: var(--ml-ink);
            font-weight: 700;
            font-size: 14px;
            line-height: 1;
            text-decoration: none;
            margin: 0 !important;
            box-shadow: 0 2px 6px -4px rgba(15, 23, 42, .08);
            transition: transform .15s ease, background .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .ml-pagination .page-item .page-link:hover {
            background: var(--ml-bg-soft);
            border-color: var(--ml-primary);
            color: var(--ml-primary);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -10px rgba(15, 118, 110, .35);
        }
        .ml-pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--ml-primary), var(--ml-primary-dark));
            border-color: var(--ml-primary);
            color: #fff !important;
            box-shadow: 0 10px 20px -10px rgba(15, 118, 110, .6);
            cursor: default;
        }
        .ml-pagination .page-item.active .page-link:hover { transform: none; }
        .ml-pagination .page-item.disabled .page-link {
            opacity: .45;
            cursor: not-allowed;
            background: var(--ml-bg-soft);
            color: var(--ml-muted);
            box-shadow: none;
        }
        .ml-pagination .page-item.disabled .page-link:hover {
            transform: none;
            border-color: var(--ml-line);
            background: var(--ml-bg-soft);
            color: var(--ml-muted);
            box-shadow: none;
        }
        /* Prev/Next: hide the &lsaquo; / &rsaquo; entities and swap with clean chevrons */
        .ml-pagination .page-item:first-child .page-link,
        .ml-pagination .page-item:last-child .page-link {
            font-size: 0;
            min-width: 44px;
            padding: 0;
        }
        .ml-pagination .page-item:first-child .page-link::before,
        .ml-pagination .page-item:last-child .page-link::before {
            font-size: 15px;
            font-weight: 900;
            line-height: 1;
            display: inline-block;
        }
        .ml-pagination .page-item:first-child .page-link::before { content: "\276E"; } /* ❮ */
        .ml-pagination .page-item:last-child  .page-link::before { content: "\276F"; } /* ❯ */
        /* RTL: swap arrow direction so "Previous" always points backwards visually */
        [dir="rtl"] .ml-pagination .page-item:first-child .page-link::before { content: "\276F"; }
        [dir="rtl"] .ml-pagination .page-item:last-child  .page-link::before { content: "\276E"; }

        /* ---------- Modal ---------- */
        .ml-modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center; justify-content: center;
            z-index: 1050;
            padding: 20px;
        }
        .ml-modal-backdrop.is-open { display: flex; animation: ml-fade .2s ease; }
        @keyframes ml-fade { from { opacity: 0; } to { opacity: 1; } }
        .ml-modal {
            background: #fff;
            border-radius: 16px;
            width: 100%; max-width: 520px;
            box-shadow: 0 30px 60px -20px rgba(15, 23, 42, .35);
            overflow: hidden;
        }
        .ml-modal__head {
            background: linear-gradient(135deg, var(--ml-primary), var(--ml-primary-dark));
            color: #fff;
            padding: 16px 18px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .ml-modal__head h4 { margin: 0; font-size: 16px; font-weight: 800; }
        .ml-modal__close {
            background: rgba(255,255,255,.18); border: 0; color: #fff;
            width: 30px; height: 30px; border-radius: 8px;
            cursor: pointer; font-size: 16px;
        }
        .ml-modal__body { padding: 18px; }
        .ml-modal__body textarea {
            width: 100%; padding: 12px;
            border: 1.5px solid var(--ml-line);
            border-radius: 10px;
            resize: vertical;
            font-family: inherit;
        }
        .ml-modal__body textarea:focus {
            outline: none; border-color: var(--ml-primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }
        .ml-modal__actions { padding: 14px 18px; border-top: 1px solid var(--ml-line); display: flex; justify-content: flex-end; gap: 8px; }
        .ml-btn { padding: 9px 18px; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid transparent; cursor: pointer; transition: transform .15s ease; }
        .ml-btn-primary { background: linear-gradient(135deg, var(--ml-primary), var(--ml-primary-dark)); color: #fff; }
        .ml-btn-primary:hover { transform: translateY(-1px); }
        .ml-btn-light { background: #fff; border-color: var(--ml-line); color: var(--ml-ink); }
        .ml-btn-light:hover { background: var(--ml-bg-soft); }

        .ml-toast {
            position: fixed;
            inset-inline-end: 24px;
            bottom: 24px;
            z-index: 2000;
            min-width: 260px;
            max-width: min(420px, calc(100vw - 32px));
            padding: 13px 16px;
            border-radius: 12px;
            background: #166534;
            color: #fff;
            box-shadow: 0 18px 40px -15px rgba(15, 23, 42, .55);
            font-weight: 700;
            display: none;
        }
        .ml-toast.is-error { background: #b91c1c; }
        .ml-toast.is-visible { display: block; animation: ml-fade .2s ease; }

        @media (max-width: 640px) {
            .ml-meta { grid-template-columns: 1fr; }
            .ml-pagination-wrap { justify-content: center; }
            .ml-pagination-summary { width: 100%; text-align: center; }
            .ml-pagination .page-item .page-link { min-width: 35px; height: 36px; padding: 0 9px; }
        }
    </style>
@endpush

@section('content')
    {{-- Hero --}}
    <div class="ml-hero">
        <div class="ml-hero__row">
            <div class="ml-hero__title">
                <span class="ml-hero__icon"><i class="fa fa-users"></i></span>
                <div>
                    <h2>{{ $isAr ? 'إدارة العمالة' : 'Workers Management' }}</h2>
                    <p>{{ $isAr ? 'تابع حالة السير الذاتية وأضِف وعدّل من مكان واحد' : 'Track CV status and add or edit from one place' }}</p>
                </div>
            </div>
            <div class="ml-hero__actions">
                <a href="{{ route('agent.maids.create') }}" class="ml-hero__btn">
                    <i class="fa fa-plus"></i>
                    <span>{{ $isAr ? 'سيرة جديدة' : 'Add CV' }}</span>
                </a>
                <a href="{{ route('agent.maids.create_rapid_cv') }}" class="ml-hero__btn ml-hero__btn--rapid">
                    <i class="fa fa-bolt"></i>
                    <span>{{ $isAr ? 'سيرة مختصرة' : 'Short CV' }}</span>
                </a>
                <a href="{{ route('agent.maids.bulk-import.create') }}" class="ml-hero__btn ml-hero__btn--import">
                    <i class="fa fa-file-excel"></i>
                    <span>{{ $isAr ? 'استيراد' : 'Import' }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="ml-stats">
        @php
            $statsBaseQuery = $baseQuery;
            unset($statsBaseQuery['worker_filter'], $statsBaseQuery['submission_filter']);
            $allStatsQuery = $statsBaseQuery;
            $availableStatsQuery = array_merge($statsBaseQuery, ['worker_filter' => 'available']);
            $contractedStatsQuery = array_merge($statsBaseQuery, ['worker_filter' => 'contracted']);
            $pendingStatsQuery = array_merge($statsBaseQuery, ['submission_filter' => 'pending']);
        @endphp
        <a href="{{ route('agent.maids.index', $allStatsQuery) }}" class="ml-stat" style="--bar:#0f766e;">
            <div class="ml-stat__icon" style="background: rgba(15, 118, 110, .12); color: #0f766e;"><i class="fa fa-users"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'إجمالي العمالة' : 'Total Workers' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['all'] ?? 0 }}</span>
            </div>
        </a>
        <a href="{{ route('agent.maids.index', $availableStatsQuery) }}" class="ml-stat" style="--bar:#16a34a;">
            <div class="ml-stat__icon" style="background: rgba(22, 163, 74, .12); color: #166534;"><i class="fa fa-circle-check"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'المتاحة' : 'Available' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['available'] ?? 0 }}</span>
            </div>
        </a>
        <a href="{{ route('agent.maids.index', $contractedStatsQuery) }}" class="ml-stat" style="--bar:#2563eb;">
            <div class="ml-stat__icon" style="background: rgba(37, 99, 235, .12); color: #1e40af;"><i class="fa fa-file-signature"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'متعاقد عليها' : 'Contracted' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['contracted'] ?? 0 }}</span>
            </div>
        </a>
        <a href="{{ route('agent.maids.index', $pendingStatsQuery) }}" class="ml-stat" style="--bar:#f59e0b;">
            <div class="ml-stat__icon" style="background: rgba(245, 158, 11, .14); color: #92400e;"><i class="fa fa-hourglass-half"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'بانتظار المراجعة' : 'Pending' }}</span>
                <span class="ml-stat__value">{{ $submissionCounts['pending'] ?? 0 }}</span>
            </div>
        </a>
    </div>

    {{-- Toolbar: filters + search --}}
    <div class="ml-toolbar">
        <div class="ml-toolbar__row">
            <div class="ml-toolbar__title">
                <i class="fa fa-filter me-1"></i>
                {{ $isAr ? 'حالة التوافق' : 'Submission Status' }}
            </div>
            <div class="ml-chips">
                @foreach ($submissionTabs as $key => $tab)
                    @php
                        $q = $baseQuery;
                        if ($key !== 'all') { $q['submission_filter'] = $key; } else { unset($q['submission_filter']); }
                    @endphp
                    <a href="{{ route('agent.maids.index', $q) }}"
                        class="ml-chip {{ $submissionFilter === $key ? 'is-active' : '' }}"
                        style="--chip-color:{{ $tab['color'] }};">
                        <i class="fa {{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="ml-chip__count">{{ $submissionCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="ml-toolbar__row">
            <div class="ml-toolbar__title">
                <i class="fa fa-briefcase me-1"></i>
                {{ $isAr ? 'حالة العمالة' : 'Worker Status' }}
            </div>
            <div class="ml-chips">
                @foreach ($workerTabs as $key => $tab)
                    @php
                        $q = $baseQuery;
                        if ($key !== 'all') { $q['worker_filter'] = $key; } else { unset($q['worker_filter']); }
                    @endphp
                    <a href="{{ route('agent.maids.index', $q) }}"
                        class="ml-chip {{ $workerFilter === $key ? 'is-active' : '' }}"
                        style="--chip-color:{{ $tab['color'] }};">
                        <i class="fa {{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="ml-chip__count">{{ $filterCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="ml-toolbar__row">
            <form action="{{ route('agent.maids.index') }}" method="GET" class="ml-search">
                @if ($workerFilter !== 'all')
                    <input type="hidden" name="worker_filter" value="{{ $workerFilter }}">
                @endif
                @if ($submissionFilter !== 'all')
                    <input type="hidden" name="submission_filter" value="{{ $submissionFilter }}">
                @endif
                <input type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ $isAr ? 'ابحث بالاسم أو رقم الجواز أو رقم الهوية...' : 'Search by name, passport or ID...' }}">
                <div class="ml-date-field">
                    <label for="maidDateFrom">{{ $isAr ? 'من تاريخ الإضافة' : 'Created from' }}</label>
                    <input type="date" id="maidDateFrom" name="date_from" value="{{ request('date_from') }}"
                        class="ml-search__date">
                </div>
                <div class="ml-date-field">
                    <label for="maidSort">{{ $isAr ? 'الترتيب' : 'Sort' }}</label>
                    <select name="sort" id="maidSort" class="form-select">
                        <option value="available_first" @selected($sort === 'available_first')>{{ $isAr ? 'المتاحة أولًا' : 'Available first' }}</option>
                        <option value="contracted_first" @selected($sort === 'contracted_first')>{{ $isAr ? 'المتعاقد عليها أولًا' : 'Contracted first' }}</option>
                        <option value="newest" @selected($sort === 'newest')>{{ $isAr ? 'الأحدث' : 'Newest' }}</option>
                        <option value="oldest" @selected($sort === 'oldest')>{{ $isAr ? 'الأقدم' : 'Oldest' }}</option>
                        <option value="name" @selected($sort === 'name')>{{ $isAr ? 'الاسم أبجديًا' : 'Name' }}</option>
                    </select>
                </div>
                <div class="ml-date-field">
                    <label for="maidDateTo">{{ $isAr ? 'إلى تاريخ الإضافة' : 'Created to' }}</label>
                    <input type="date" id="maidDateTo" name="date_to" value="{{ request('date_to') }}"
                        class="ml-search__date">
                </div>
                <button type="submit">
                    <i class="fa fa-magnifying-glass"></i>
                    <span>{{ $isAr ? 'بحث' : 'Search' }}</span>
                </button>
                @if (request()->hasAny(['search', 'date_from', 'date_to', 'worker_filter', 'submission_filter']))
                    <a href="{{ route('agent.maids.index') }}" class="ml-search__clear">
                        <i class="fa fa-xmark me-1"></i>
                        {{ $isAr ? 'مسح' : 'Clear' }}
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="ml-results-head">
        <div>
            <h3 class="ml-results-title">
                @if ($workerFilter === 'available')
                    {{ $isAr ? 'العاملات المتاحات' : 'Available Workers' }}
                @elseif ($workerFilter === 'contracted')
                    {{ $isAr ? 'العاملات المتعاقد عليهن' : 'Contracted Workers' }}
                @else
                    {{ $isAr ? 'قائمة العاملات' : 'Workers List' }}
                @endif
            </h3>
            <p class="ml-results-subtitle">
                {{ $isAr ? 'النتائج المطابقة للفلاتر الحالية' : 'Results matching the current filters' }}
                · {{ $maids->total() }}
            </p>
        </div>
        <div class="ml-results-legend">
            <span class="ml-legend-item"><span class="ml-legend-dot" style="background:#16a34a;"></span>{{ $isAr ? 'متاحة' : 'Available' }}</span>
            <span class="ml-legend-item"><span class="ml-legend-dot" style="background:#2563eb;"></span>{{ $isAr ? 'متعاقد عليها' : 'Contracted' }}</span>
            <span class="ml-legend-item"><span class="ml-legend-dot" style="background:#cbd5e1;"></span>{{ $isAr ? 'حالة أخرى' : 'Other' }}</span>
        </div>
    </div>

    {{-- Workers grid --}}
    @if (!$maids->isEmpty())
        <form id="bulkStatusRequestForm" method="POST"
            action="{{ route('agent.maids.status-change-requests.bulk') }}">
            @csrf
            <div class="ml-bulk-bar">
                <label class="d-inline-flex align-items-center gap-2 mb-0 fw-bold">
                    <input type="checkbox" id="selectAllEligibleMaids">
                    {{ $isAr ? 'تحديد كل الظاهر القابل للتعليق' : 'Select all eligible visible workers' }}
                </label>
                <button type="submit" class="ml-btn ml-btn-primary" id="bulkSuspendButton" disabled>
                    <i class="fa fa-pause-circle"></i>
                    {{ $isAr ? 'طلب تعليق المحدد' : 'Request Selected Suspension' }}
                    (<span id="selectedMaidsCount">0</span>)
                </button>
            </div>
        </form>
    @endif

    @if ($maids->isEmpty())
        <div class="ml-empty">
            <div class="ml-empty__icon"><i class="fa fa-folder-open"></i></div>
            <h4 class="ml-empty__title">{{ $isAr ? 'لا توجد سجلات' : 'No records' }}</h4>
            <p class="ml-empty__text">{{ $isAr ? 'لم نعثر على سير ذاتية مطابقة لهذه الفلاتر.' : 'No CVs match the current filters.' }}</p>
            <a href="{{ route('agent.maids.create') }}" class="ml-btn ml-btn-primary">
                <i class="fa fa-plus"></i>
                {{ $isAr ? 'إضافة سيرة جديدة' : 'Add New CV' }}
            </a>
        </div>
    @else
        <div class="ml-cards">
            @foreach ($maids as $maid)
                @php
                    $activeContractsCount = (int) ($maid->active_contracts_count ?? 0);
                    $isContracted = $activeContractsCount > 0 || $maid->status === 'contracted';
                    $isAvailableState = !$isContracted
                        && ((bool) $maid->available || $maid->status === 'available')
                        && !in_array($maid->status, ['reserved', 'suspended', 'backout'], true);
                    $statusKey = $isContracted ? 'contracted' : ($isAvailableState ? 'available' : ($maid->status ?: 'default'));
                    $statusLabel = $isContracted
                        ? ($isAr ? 'متعاقد عليها' : 'Contracted')
                        : ($isAvailableState
                            ? ($isAr ? 'متاحة' : 'Available')
                            : match ($maid->status) {
                            'available' => $isAr ? 'متاحة' : 'Available',
                            'reserved' => $isAr ? 'محجوزة' : 'Reserved',
                            'suspended' => $isAr ? 'معلقة' : 'Suspended',
                            'backout' => 'Back Out',
                            default => $maid->status ?: '-',
                        });

                    $submissionStatus = $maid->submission_status;
                    $subBadgeKey = $submissionStatus === Maid::SUBMISSION_PENDING
                        ? 'pending'
                        : ($submissionStatus === Maid::SUBMISSION_REJECTED ? 'rejected' : 'approved');
                    $subBadgeLabel = $subBadgeKey === 'pending'
                        ? ($isAr ? 'بانتظار المراجعة' : 'Pending')
                        : ($subBadgeKey === 'rejected'
                            ? ($isAr ? 'مرفوضة' : 'Rejected')
                            : ($isAr ? 'متوافق عليها' : 'Approved'));
                    $subBadgeIcon = $subBadgeKey === 'pending' ? 'fa-hourglass-half' : ($subBadgeKey === 'rejected' ? 'fa-circle-xmark' : 'fa-circle-check');

                    $hasImage = $maid->image && trim($maid->image) !== '';
                    $isApprovedSubmission = !in_array(
                        $submissionStatus,
                        [Maid::SUBMISSION_PENDING, Maid::SUBMISSION_REJECTED],
                        true
                    );
                    $isAvailable = $isAvailableState;
                    $hasPendingStatusRequest = (bool) $maid->pendingStatusChangeRequest;
                    $canRequestSuspension = !$hasPendingStatusRequest && $isApprovedSubmission && $isAvailable;
                    $canRequestBackout = !$hasPendingStatusRequest && $isApprovedSubmission && $isContracted;
                @endphp
                <div class="ml-card ml-card--{{ $isContracted ? 'contracted' : ($isAvailable ? 'available' : 'other') }}"
                    data-maid-id="{{ $maid->id }}">
                    @if ($canRequestSuspension)
                        <input type="checkbox" name="maid_ids[]" value="{{ $maid->id }}"
                            class="ml-card__select bulk-maid-checkbox" form="bulkStatusRequestForm"
                            aria-label="{{ $isAr ? 'تحديد العاملة' : 'Select worker' }} {{ $maid->name }}">
                    @endif
                    <div class="ml-card__status-row">
                        <span class="ml-sub-badge ml-sub-badge--{{ $subBadgeKey }}">
                            <i class="fa {{ $subBadgeIcon }}"></i>
                            <span>{{ $subBadgeLabel }}</span>
                        </span>
                    </div>

                    <div class="ml-card__top">
                        @if ($hasImage)
                            <img src="{{ showFile($maid->image) }}" alt="" class="ml-avatar"
                                onerror="this.outerHTML='<span class=\'ml-avatar\'><i class=\'fa fa-user\'></i></span>'">
                        @else
                            <span class="ml-avatar"><i class="fa fa-user"></i></span>
                        @endif
                        <div class="ml-card__info">
                            <h5 class="ml-card__name">{{ $maid->name ?: ($isAr ? 'بدون اسم' : 'No name') }}</h5>
                            <span class="ml-card__id">#{{ $maid->id }}</span>
                            <div class="mt-2">
                                <span class="ml-badge ml-badge--{{ $statusKey === 'available' || $statusKey === 'contracted' || $statusKey === 'reserved' ? $statusKey : 'default' }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="ml-meta">
                        <div class="ml-meta__item">
                            <span class="ml-meta__label">{{ $isAr ? 'الجنسية' : 'Nationality' }}</span>
                            <span class="ml-meta__value">{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '-') }}</span>
                        </div>
                        <div class="ml-meta__item">
                            <span class="ml-meta__label">{{ $isAr ? 'العمر' : 'Age' }}</span>
                            <span class="ml-meta__value">{{ $maid->age ?: '-' }}</span>
                        </div>
                        <div class="ml-meta__item">
                            <span class="ml-meta__label">{{ $isAr ? 'الجواز' : 'Passport' }}</span>
                            <span class="ml-meta__value">{{ $maid->passport_num ?: '-' }}</span>
                        </div>
                        <div class="ml-meta__item">
                            <span class="ml-meta__label">{{ $isAr ? 'العقود النشطة' : 'Active Contracts' }}</span>
                            <span class="ml-meta__value">{{ $activeContractsCount }}</span>
                        </div>
                    </div>

                    <div class="ml-card__actions js-maid-actions">
                        <a href="{{ route('agent.maids.show', $maid->id) }}" class="ml-action-btn ml-action-btn--view">
                            <i class="fa fa-eye"></i>
                            <span>{{ $isAr ? 'عرض' : 'View' }}</span>
                        </a>
                        <button type="button" class="ml-action-btn ml-action-btn--notes edit-notes-btn"
                            data-id="{{ $maid->id }}"
                            data-notes="{{ $maid->additional_notes ?? '' }}">
                            <i class="fa fa-pen"></i>
                            <span>{{ $isAr ? 'ملاحظات' : 'Notes' }}</span>
                        </button>
                        @if ($hasPendingStatusRequest)
                            <button type="button" class="ml-action-btn ml-action-btn--pending" disabled
                                title="{{ $maid->pendingStatusChangeRequest->reason }}">
                                <i class="fa fa-hourglass-half"></i>
                                <span>{{ $isAr ? 'الطلب قيد المراجعة' : 'Request Pending' }}</span>
                            </button>
                        @else
                            @if ($canRequestSuspension)
                                <form method="POST" action="{{ route('agent.maids.status-change-requests.store', $maid->id) }}"
                                    class="js-status-request-form" data-maid-id="{{ $maid->id }}">
                                    @csrf
                                    <input type="hidden" name="type" value="suspend">
                                    <button type="submit" class="ml-action-btn ml-action-btn--request"
                                        onclick="return confirm('{{ $isAr ? 'إرسال طلب تعليق هذه العاملة للإدارة؟' : 'Send this suspension request to administration?' }}')">
                                        <i class="fa fa-pause-circle"></i>
                                        <span>{{ $isAr ? 'تعليق' : 'Suspend' }}</span>
                                    </button>
                                </form>
                            @endif
                            @if ($canRequestBackout)
                                <button type="button" class="ml-action-btn ml-action-btn--backout status-request-btn"
                                    data-id="{{ $maid->id }}" data-name="{{ $maid->name }}">
                                    <i class="fa fa-user-slash"></i>
                                    <span>Back Out</span>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ml-pagination-wrap">
            <div class="ml-pagination-summary">
                {{ $isAr ? 'عرض' : 'Showing' }}
                {{ $maids->firstItem() }}–{{ $maids->lastItem() }}
                {{ $isAr ? 'من' : 'of' }}
                {{ $maids->total() }}
            </div>
            <div class="ml-pagination">
                {{ $maids->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif

    {{-- Notes modal --}}
    <div id="notesModal" class="ml-modal-backdrop">
        <div class="ml-modal">
            <div class="ml-modal__head">
                <h4><i class="fa fa-pen-to-square me-1"></i> {{ $isAr ? 'ملاحظات العمالة' : 'Worker Notes' }}</h4>
                <button type="button" id="closeNotesModal" class="ml-modal__close"><i class="fa fa-xmark"></i></button>
            </div>
            <form id="notesForm" action="" method="POST">
                @csrf
                <div class="ml-modal__body">
                    <textarea name="additional_notes" id="maidNotes" rows="5"
                        placeholder="{{ $isAr ? 'أدخل ملاحظاتك هنا...' : 'Enter your notes here...' }}"></textarea>
                </div>
                <div class="ml-modal__actions">
                    <button type="button" id="closeNotesModalBtn" class="ml-btn ml-btn-light">
                        <i class="fa fa-xmark"></i>
                        {{ $isAr ? 'إلغاء' : 'Cancel' }}
                    </button>
                    <button type="submit" class="ml-btn ml-btn-primary">
                        <i class="fa fa-floppy-disk"></i>
                        {{ $isAr ? 'حفظ' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Status change request modal --}}
    <div id="statusRequestModal" class="ml-modal-backdrop">
        <div class="ml-modal">
            <div class="ml-modal__head">
                <h4><i class="fa fa-paper-plane me-1"></i> {{ $isAr ? 'طلب إجراء على العاملة' : 'Request Worker Action' }}</h4>
                <button type="button" id="closeStatusRequestModal" class="ml-modal__close"><i class="fa fa-xmark"></i></button>
            </div>
            <form id="statusRequestForm" action="" method="POST">
                @csrf
                <div class="ml-modal__body">
                    <div class="alert alert-light border mb-3" id="statusRequestMaidName"></div>
                    <input type="hidden" name="type" value="backout">

                    <label for="statusRequestReason" class="form-label fw-bold">
                        {{ $isAr ? 'سبب الطلب' : 'Reason' }}
                    </label>
                    <textarea name="reason" id="statusRequestReason" rows="5" maxlength="1000" required
                        placeholder="{{ $isAr ? 'اكتب السبب والتفاصيل التي تحتاج الإدارة لمراجعتها...' : 'Enter the reason and details for administration...' }}"></textarea>
                    <div class="form-text mt-2">
                        {{ $isAr ? 'لن تتغير حالة العاملة إلا بعد اعتماد الإدارة.' : 'The worker status will only change after administration approval.' }}
                    </div>
                </div>
                <div class="ml-modal__actions">
                    <button type="button" id="closeStatusRequestModalBtn" class="ml-btn ml-btn-light">
                        <i class="fa fa-xmark"></i>
                        {{ $isAr ? 'إلغاء' : 'Cancel' }}
                    </button>
                    <button type="submit" class="ml-btn ml-btn-primary">
                        <i class="fa fa-paper-plane"></i>
                        {{ $isAr ? 'إرسال للإدارة' : 'Send to Administration' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="maidActionToast" class="ml-toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('notesModal');
        const closeBtn = document.getElementById('closeNotesModal');
        const closeBtnBottom = document.getElementById('closeNotesModalBtn');
        const form = document.getElementById('notesForm');
        const textarea = document.getElementById('maidNotes');
        const requestModal = document.getElementById('statusRequestModal');
        const requestForm = document.getElementById('statusRequestForm');
        const requestReason = document.getElementById('statusRequestReason');
        const requestMaidName = document.getElementById('statusRequestMaidName');
        const toast = document.getElementById('maidActionToast');
        let activeNotesButton = null;
        let toastTimer = null;

        const showToast = (message, isError = false) => {
            clearTimeout(toastTimer);
            toast.textContent = message;
            toast.classList.toggle('is-error', isError);
            toast.classList.add('is-visible');
            toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3500);
        };

        const submitAjax = async formElement => {
            const response = await fetch(formElement.action, {
                method: 'POST',
                body: new FormData(formElement),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstErrors = data.errors ? Object.values(data.errors).flat() : [];
                throw new Error(firstErrors[0] || data.message || `{{ $isAr ? 'تعذر تنفيذ الإجراء.' : 'The action could not be completed.' }}`);
            }

            return data;
        };

        const setFormLoading = (formElement, loading) => {
            const button = formElement.querySelector('button[type="submit"]');
            if (!button) return;
            if (loading) {
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> {{ $isAr ? 'جارٍ الحفظ' : 'Saving' }}`;
            } else if (button.isConnected) {
                button.disabled = false;
                button.innerHTML = button.dataset.originalHtml || button.innerHTML;
            }
        };

        const markRequestPending = (maidId, reason = '') => {
            const card = document.querySelector(`.ml-card[data-maid-id="${maidId}"]`);
            if (!card) return;

            const checkbox = card.querySelector('.bulk-maid-checkbox');
            if (checkbox) checkbox.remove();

            const actions = card.querySelector('.js-maid-actions');
            if (!actions) return;
            actions.querySelectorAll('.js-status-request-form, .status-request-btn').forEach(element => element.remove());

            const pendingButton = document.createElement('button');
            pendingButton.type = 'button';
            pendingButton.disabled = true;
            pendingButton.className = 'ml-action-btn ml-action-btn--pending';
            pendingButton.title = reason;
            pendingButton.innerHTML = `<i class="fa fa-hourglass-half"></i><span>{{ $isAr ? 'الطلب قيد المراجعة' : 'Request Pending' }}</span>`;
            actions.appendChild(pendingButton);

            updateBulkSelection();
        };

        document.querySelectorAll('.edit-notes-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                activeNotesButton = this;
                form.action = `{{ url('agent/maids') }}/${this.dataset.id}/notes`;
                textarea.value = this.dataset.notes || '';
                modal.classList.add('is-open');
                setTimeout(() => textarea.focus(), 100);
            });
        });

        const close = () => modal.classList.remove('is-open');
        closeBtn.addEventListener('click', close);
        closeBtnBottom.addEventListener('click', close);
        modal.addEventListener('click', e => { if (e.target === modal) close(); });

        document.querySelectorAll('.status-request-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                requestForm.action = `{{ url('agent/maids') }}/${this.dataset.id}/status-change-requests`;
                requestForm.dataset.maidId = this.dataset.id;
                requestMaidName.textContent = `{{ $isAr ? 'العاملة:' : 'Worker:' }} ${this.dataset.name || '#' + this.dataset.id}`;
                requestReason.value = '';
                requestModal.classList.add('is-open');
                setTimeout(() => requestReason.focus(), 100);
            });
        });

        const closeRequest = () => requestModal.classList.remove('is-open');
        document.getElementById('closeStatusRequestModal').addEventListener('click', closeRequest);
        document.getElementById('closeStatusRequestModalBtn').addEventListener('click', closeRequest);
        requestModal.addEventListener('click', e => { if (e.target === requestModal) closeRequest(); });

        const bulkCheckboxes = Array.from(document.querySelectorAll('.bulk-maid-checkbox'));
        const selectAll = document.getElementById('selectAllEligibleMaids');
        const bulkButton = document.getElementById('bulkSuspendButton');
        const selectedCount = document.getElementById('selectedMaidsCount');

        const updateBulkSelection = () => {
            const activeCheckboxes = bulkCheckboxes.filter(checkbox => checkbox.isConnected);
            const count = activeCheckboxes.filter(checkbox => checkbox.checked).length;
            if (selectedCount) selectedCount.textContent = count;
            if (bulkButton) bulkButton.disabled = count === 0;
            if (selectAll) {
                selectAll.checked = activeCheckboxes.length > 0 && count === activeCheckboxes.length;
                selectAll.indeterminate = count > 0 && count < activeCheckboxes.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                bulkCheckboxes.filter(checkbox => checkbox.isConnected)
                    .forEach(checkbox => checkbox.checked = this.checked);
                updateBulkSelection();
            });
        }
        bulkCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateBulkSelection));

        form.addEventListener('submit', async event => {
            event.preventDefault();
            setFormLoading(form, true);
            try {
                const data = await submitAjax(form);
                if (activeNotesButton) {
                    activeNotesButton.dataset.notes = data.notes || '';
                }
                close();
                showToast(data.message);
            } catch (error) {
                showToast(error.message, true);
            } finally {
                setFormLoading(form, false);
            }
        });

        document.querySelectorAll('.js-status-request-form').forEach(statusForm => {
            statusForm.addEventListener('submit', async event => {
                event.preventDefault();
                setFormLoading(statusForm, true);
                try {
                    const data = await submitAjax(statusForm);
                    markRequestPending(statusForm.dataset.maidId, data.reason || '');
                    showToast(data.message);
                } catch (error) {
                    showToast(error.message, true);
                    setFormLoading(statusForm, false);
                }
            });
        });

        requestForm.addEventListener('submit', async event => {
            event.preventDefault();
            setFormLoading(requestForm, true);
            try {
                const data = await submitAjax(requestForm);
                markRequestPending(requestForm.dataset.maidId, data.reason || requestReason.value);
                closeRequest();
                showToast(data.message);
            } catch (error) {
                showToast(error.message, true);
            } finally {
                setFormLoading(requestForm, false);
            }
        });

        const bulkForm = document.getElementById('bulkStatusRequestForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', async event => {
                event.preventDefault();
                const checkedCount = bulkCheckboxes.filter(checkbox => checkbox.isConnected && checkbox.checked).length;
                if (!checkedCount) return;
                if (!confirm(`{{ $isAr ? 'إرسال طلب تعليق للعاملات المحددة؟' : 'Send suspension requests for selected workers?' }}`)) return;

                setFormLoading(bulkForm, true);
                try {
                    const data = await submitAjax(bulkForm);
                    (data.maid_ids || []).forEach(maidId => markRequestPending(maidId));
                    showToast(data.message);
                } catch (error) {
                    showToast(error.message, true);
                } finally {
                    setFormLoading(bulkForm, false);
                    updateBulkSelection();
                }
            });
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                close();
                closeRequest();
            }
        });
    });
</script>
@endpush
