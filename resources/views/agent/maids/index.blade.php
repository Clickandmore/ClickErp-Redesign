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
        }
        .ml-stat:hover { box-shadow: 0 12px 28px -18px rgba(15, 23, 42, .18); transform: translateY(-1px); }
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
            display: flex; gap: 8px; align-items: center;
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
        }
        .ml-card:hover {
            box-shadow: 0 18px 32px -18px rgba(15, 23, 42, .25);
            transform: translateY(-3px);
            border-color: rgba(15, 118, 110, .3);
        }
        .ml-card__top { display: flex; gap: 12px; align-items: flex-start; }
        .ml-avatar {
            width: 64px; height: 64px;
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
            position: absolute; inset-inline-end: 12px; top: 12px;
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 9px; border-radius: 999px;
            font-size: 10.5px; font-weight: 700;
        }
        .ml-sub-badge--pending { background: rgba(245, 158, 11, .14); color: #92400e; border: 1px solid rgba(245, 158, 11, .35); }
        .ml-sub-badge--approved { background: rgba(22, 163, 74, .12); color: #166534; border: 1px solid rgba(22, 163, 74, .35); }
        .ml-sub-badge--rejected { background: rgba(220, 38, 38, .12); color: #991b1b; border: 1px solid rgba(220, 38, 38, .35); }

        .ml-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ml-meta__item { background: var(--ml-bg-soft); border-radius: 10px; padding: 8px 10px; display: flex; flex-direction: column; gap: 2px; }
        .ml-meta__label { color: var(--ml-muted); font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .ml-meta__value { font-size: 13px; color: var(--ml-ink); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .ml-card__actions { display: flex; gap: 6px; padding-top: 10px; border-top: 1px dashed var(--ml-line); }
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
        .ml-pagination { margin-top: 22px; display: flex; justify-content: center; }
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

        @media (max-width: 640px) {
            .ml-meta { grid-template-columns: 1fr; }
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
        <div class="ml-stat" style="--bar:#0f766e;">
            <div class="ml-stat__icon" style="background: rgba(15, 118, 110, .12); color: #0f766e;"><i class="fa fa-users"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'إجمالي العمالة' : 'Total Workers' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['all'] ?? 0 }}</span>
            </div>
        </div>
        <div class="ml-stat" style="--bar:#16a34a;">
            <div class="ml-stat__icon" style="background: rgba(22, 163, 74, .12); color: #166534;"><i class="fa fa-circle-check"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'المتاحة' : 'Available' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['available'] ?? 0 }}</span>
            </div>
        </div>
        <div class="ml-stat" style="--bar:#2563eb;">
            <div class="ml-stat__icon" style="background: rgba(37, 99, 235, .12); color: #1e40af;"><i class="fa fa-file-signature"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'متعاقد عليها' : 'Contracted' }}</span>
                <span class="ml-stat__value">{{ $filterCounts['contracted'] ?? 0 }}</span>
            </div>
        </div>
        <div class="ml-stat" style="--bar:#f59e0b;">
            <div class="ml-stat__icon" style="background: rgba(245, 158, 11, .14); color: #92400e;"><i class="fa fa-hourglass-half"></i></div>
            <div class="ml-stat__body">
                <span class="ml-stat__label">{{ $isAr ? 'بانتظار المراجعة' : 'Pending' }}</span>
                <span class="ml-stat__value">{{ $submissionCounts['pending'] ?? 0 }}</span>
            </div>
        </div>
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
                <button type="submit">
                    <i class="fa fa-magnifying-glass"></i>
                    <span>{{ $isAr ? 'بحث' : 'Search' }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Workers grid --}}
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
                    $statusKey = $isContracted ? 'contracted' : ($maid->status ?: 'default');
                    $statusLabel = $isContracted
                        ? ($isAr ? 'متعاقد عليها' : 'Contracted')
                        : match ($maid->status) {
                            'available' => $isAr ? 'متاحة' : 'Available',
                            'reserved' => $isAr ? 'محجوزة' : 'Reserved',
                            default => $maid->status ?: '-',
                        };

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
                @endphp
                <div class="ml-card">
                    <span class="ml-sub-badge ml-sub-badge--{{ $subBadgeKey }}">
                        <i class="fa {{ $subBadgeIcon }}"></i>
                        <span>{{ $subBadgeLabel }}</span>
                    </span>

                    <div class="ml-card__top">
                        @if ($hasImage)
                            <img src="{{ asset('storage/' . $maid->image) }}" alt="" class="ml-avatar"
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

                    <div class="ml-card__actions">
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
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ml-pagination">
            {{ $maids->links() }}
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('notesModal');
        const closeBtn = document.getElementById('closeNotesModal');
        const closeBtnBottom = document.getElementById('closeNotesModalBtn');
        const form = document.getElementById('notesForm');
        const textarea = document.getElementById('maidNotes');

        document.querySelectorAll('.edit-notes-btn').forEach(btn => {
            btn.addEventListener('click', function() {
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
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    });
</script>
@endpush
