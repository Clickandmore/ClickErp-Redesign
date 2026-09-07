@extends('admin.layout.indexs.index')
@section('page-title', 'إشعارات الأدمنز')

@section('style')
<style>
    .dispatch-page {
        --dp-primary: #4f46e5;
        --dp-primary-dark: #3730a3;
        --dp-primary-soft: #eef2ff;
        --dp-border: #e5e7eb;
        --dp-muted: #6b7280;
        --dp-text: #111827;
        --dp-radius: 16px;
        color: var(--dp-text);
    }

    /* ---------- Hero ---------- */
    .dispatch-hero {
        position: relative;
        overflow: hidden;
        border-radius: var(--dp-radius);
        padding: 26px 28px;
        margin-bottom: 22px;
        color: #fff;
        background: linear-gradient(135deg, #4338ca 0%, #6d28d9 55%, #7c3aed 100%);
        box-shadow: 0 18px 40px -20px rgba(79, 70, 229, 0.65);
    }

    .dispatch-hero::before,
    .dispatch-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    .dispatch-hero::before { width: 260px; height: 260px; top: -120px; left: -60px; }
    .dispatch-hero::after  { width: 180px; height: 180px; bottom: -90px; right: 22%; }

    .dispatch-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .dispatch-hero__title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .dispatch-hero__icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.28);
        backdrop-filter: blur(6px);
    }

    .dispatch-hero__title h4 {
        margin: 0;
        font-weight: 800;
        font-size: 1.35rem;
        color: #fff;
    }

    .dispatch-hero__title p {
        margin: 4px 0 0;
        font-size: 0.86rem;
        color: rgba(255, 255, 255, 0.82);
        max-width: 640px;
    }

    .dispatch-hero__stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .dispatch-stat {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.28);
        backdrop-filter: blur(6px);
    }

    .dispatch-stat i {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .dispatch-stat strong {
        display: block;
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .dispatch-stat span {
        font-size: 0.74rem;
        opacity: 0.85;
    }

    /* ---------- Cards ---------- */
    .dispatch-card {
        border: 1px solid var(--dp-border);
        border-radius: var(--dp-radius);
        background: #fff;
        box-shadow: 0 10px 30px -22px rgba(17, 24, 39, 0.35);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .dispatch-card__header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 22px;
        border-bottom: 1px solid var(--dp-border);
        background: linear-gradient(180deg, #fafafe 0%, #fff 100%);
    }

    .dispatch-card__heading {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .dispatch-card__icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .dispatch-card__icon--indigo { background: #eef2ff; color: #4338ca; }
    .dispatch-card__icon--teal   { background: #f0fdfa; color: #0f766e; }

    .dispatch-card__heading h5 {
        margin: 0;
        font-weight: 800;
        font-size: 1.02rem;
    }

    .dispatch-card__heading p {
        margin: 3px 0 0;
        font-size: 0.82rem;
        color: var(--dp-muted);
    }

    .dispatch-card__body { padding: 22px; }

    /* ---------- Steps ---------- */
    .dispatch-step + .dispatch-step {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px dashed var(--dp-border);
    }

    .dispatch-step__title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    .dispatch-step__num {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--dp-primary);
        color: #fff;
        font-size: 0.82rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    .dispatch-step__title h6 {
        margin: 0;
        font-weight: 800;
        font-size: 0.95rem;
    }

    .dispatch-step__title small {
        display: block;
        color: var(--dp-muted);
        font-size: 0.76rem;
        margin-top: 2px;
    }

    .dispatch-page .form-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #374151;
        margin-bottom: 6px;
    }

    .dispatch-page .form-control,
    .dispatch-page .form-select {
        border-radius: 10px;
        border-color: #d1d5db;
        font-size: 0.9rem;
        min-height: 42px;
    }

    .dispatch-page .form-control:focus,
    .dispatch-page .form-select:focus {
        border-color: #a5b4fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .dispatch-page textarea.form-control {
        line-height: 1.7;
        resize: vertical;
    }

    .field-counter {
        display: flex;
        justify-content: flex-end;
        margin-top: 4px;
        font-size: 0.72rem;
        color: var(--dp-muted);
        font-variant-numeric: tabular-nums;
    }

    .field-counter.is-near { color: #b45309; }
    .field-counter.is-full { color: #b91c1c; font-weight: 700; }

    .field-help {
        font-size: 0.76rem;
        color: var(--dp-muted);
        margin-top: 6px;
    }

    /* ---------- Audience picker ---------- */
    .audience-picker {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
    }

    .audience-picker .btn-check + .audience-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        cursor: pointer;
        text-align: start;
        transition: all .16s ease;
        margin: 0;
    }

    .audience-option__icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 1rem;
        flex-shrink: 0;
        transition: all .16s ease;
    }

    .audience-option__text strong {
        display: block;
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--dp-text);
    }

    .audience-option__text span {
        display: block;
        font-size: 0.72rem;
        color: var(--dp-muted);
        margin-top: 2px;
    }

    .audience-picker .btn-check + .audience-option:hover {
        border-color: #a5b4fc;
        background: #fbfbff;
    }

    .audience-picker .btn-check:checked + .audience-option {
        border-color: var(--dp-primary);
        background: var(--dp-primary-soft);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .audience-picker .btn-check:checked + .audience-option .audience-option__icon {
        background: var(--dp-primary);
        color: #fff;
    }

    .audience-picker .btn-check:focus-visible + .audience-option {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    }

    .audience-target {
        margin-top: 14px;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid var(--dp-border);
        background: #fbfbfd;
    }

    /* select2 tweaks (scoped) */
    .dispatch-page .select2-container--default .select2-selection--multiple {
        border-radius: 10px;
        border-color: #d1d5db;
        min-height: 46px;
        padding: 4px 6px;
    }

    .dispatch-page .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #a5b4fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .dispatch-page .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--dp-primary-soft);
        border: 1px solid #c7d2fe;
        color: var(--dp-primary-dark);
        border-radius: 999px;
        padding: 2px 10px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 4px;
    }

    .dispatch-page .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: var(--dp-primary-dark);
        margin-inline-end: 4px;
    }

    /* ---------- Channels ---------- */
    .channel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }

    .channel-card {
        --ch: #6b7280;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        cursor: pointer;
        margin: 0;
        transition: all .16s ease;
        position: relative;
    }

    .channel-card__icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: var(--ch);
        font-size: 1.05rem;
        flex-shrink: 0;
        transition: all .16s ease;
    }

    .channel-card__text strong {
        display: block;
        font-size: 0.88rem;
        font-weight: 700;
    }

    .channel-card__text span {
        display: block;
        font-size: 0.72rem;
        color: var(--dp-muted);
        margin-top: 2px;
    }

    .channel-card__check {
        margin-inline-start: auto;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.7rem;
        flex-shrink: 0;
        transition: all .16s ease;
    }

    .channel-card[data-channel="system"]   { --ch: #4f46e5; }
    .channel-card[data-channel="email"]    { --ch: #d97706; }
    .channel-card[data-channel="sms"]      { --ch: #0284c7; }
    .channel-card[data-channel="whatsapp"] { --ch: #16a34a; }

    .btn-check + .channel-card:hover {
        border-color: var(--ch);
        background: #fbfbff;
    }

    .btn-check:checked + .channel-card,
    .channel-card.is-locked {
        border-color: var(--ch);
        background: #f5f3ff;
        background: color-mix(in srgb, var(--ch) 8%, #fff);
    }

    .btn-check:checked + .channel-card .channel-card__icon,
    .channel-card.is-locked .channel-card__icon {
        background: var(--ch);
        color: #fff;
    }

    .btn-check:checked + .channel-card .channel-card__check,
    .channel-card.is-locked .channel-card__check {
        background: var(--ch);
        border-color: var(--ch);
    }

    .btn-check:focus-visible + .channel-card {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    }

    .channel-card.is-locked {
        cursor: default;
    }

    .channel-card__lock {
        position: absolute;
        top: 8px;
        inset-inline-end: 10px;
        font-size: 0.68rem;
        font-weight: 700;
        color: var(--ch);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* ---------- Form footer ---------- */
    .dispatch-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid var(--dp-border);
    }

    .dispatch-footer__hint {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.78rem;
        color: var(--dp-muted);
    }

    .btn-dispatch-send {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 999px;
        border: 0;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        font-weight: 700;
        font-size: 0.92rem;
        box-shadow: 0 10px 22px -10px rgba(79, 70, 229, 0.8);
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
    }

    .btn-dispatch-send:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 14px 26px -10px rgba(79, 70, 229, 0.9);
    }

    .btn-dispatch-send:disabled {
        opacity: 0.7;
        transform: none;
        cursor: progress;
    }

    /* ---------- No permission ---------- */
    .dispatch-locked {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 40px 16px;
        text-align: center;
        color: var(--dp-muted);
    }

    .dispatch-locked i {
        font-size: 2rem;
        color: #a5b4fc;
    }

    .dispatch-locked strong { color: #374151; }

    /* ---------- History ---------- */
    .history-table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .history-table thead th {
        background: #f8f9fc;
        color: #4b5563;
        font-size: 0.78rem;
        font-weight: 700;
        border-bottom: 1px solid var(--dp-border);
        padding: 12px 14px;
        white-space: nowrap;
    }

    .history-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #f1f2f6;
        vertical-align: top;
        font-size: 0.86rem;
        transition: background .15s ease;
    }

    .history-table tbody tr:last-child td { border-bottom: 0; }
    .history-table tbody tr:hover td { background: #fafaff; }

    .history-date {
        display: flex;
        flex-direction: column;
        gap: 2px;
        white-space: nowrap;
    }

    .history-date strong {
        font-size: 0.84rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .history-date span {
        font-size: 0.72rem;
        color: var(--dp-muted);
        font-variant-numeric: tabular-nums;
    }

    .history-recipient {
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
    }

    .history-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--dp-primary-soft);
        color: var(--dp-primary-dark);
        font-weight: 800;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .history-avatar.is-missing {
        background: #f3f4f6;
        color: #9ca3af;
    }

    .history-title {
        font-weight: 800;
        font-size: 0.9rem;
        color: var(--dp-text);
    }

    .history-audience {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .history-message {
        margin-top: 6px;
    }

    .history-message summary {
        cursor: pointer;
        font-size: 0.76rem;
        font-weight: 600;
        color: var(--dp-primary);
        list-style: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        user-select: none;
    }

    .history-message summary::-webkit-details-marker { display: none; }

    .history-message summary i {
        transition: transform .18s ease;
        font-size: 0.7rem;
    }

    .history-message[open] summary i { transform: rotate(180deg); }

    .history-message p {
        margin: 8px 0 0;
        padding: 10px 12px;
        border-radius: 10px;
        background: #fbfbfd;
        border: 1px solid var(--dp-border);
        white-space: pre-wrap;
        font-size: 0.82rem;
        line-height: 1.7;
        color: #374151;
        max-width: 520px;
    }

    .delivery-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .delivery-pill {
        --st: #6b7280;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        color: var(--st);
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        background: color-mix(in srgb, var(--st) 12%, #fff);
        border: 1px solid color-mix(in srgb, var(--st) 30%, #fff);
        white-space: nowrap;
    }

    .delivery-pill i { font-size: 0.72rem; }

    .delivery-pill[data-state="saved"]      { --st: #4f46e5; }
    .delivery-pill[data-state="pending"]    { --st: #b45309; }
    .delivery-pill[data-state="processing"] { --st: #0369a1; }
    .delivery-pill[data-state="accepted"]   { --st: #15803d; }
    .delivery-pill[data-state="failed"]     { --st: #b91c1c; }

    .read-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .read-pill.is-read   { background: #dcfce7; color: #166534; }
    .read-pill.is-unread { background: #f3f4f6; color: #6b7280; }

    .history-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 40px 16px;
        color: var(--dp-muted);
        text-align: center;
    }

    .history-empty i {
        font-size: 2rem;
        color: #c7d2fe;
    }

    .history-pagination {
        padding: 14px 22px;
        border-top: 1px solid var(--dp-border);
        background: #fafafe;
    }

    .history-pagination .pagination {
        margin: 0;
        justify-content: center;
    }

    .history-pagination .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
        border-color: var(--dp-border);
        color: var(--dp-primary-dark);
        font-size: 0.84rem;
    }

    .history-pagination .page-item.active .page-link {
        background: var(--dp-primary);
        border-color: var(--dp-primary);
        color: #fff;
    }

    .dispatch-page .alert {
        border-radius: 12px;
        border: 0;
        font-size: 0.88rem;
    }

    @media (max-width: 768px) {
        .dispatch-hero { padding: 20px; }
        .dispatch-card__body { padding: 16px; }
    }
</style>
@endsection

@section('content')
@php
    $permissions = \App\Support\AdminDispatchPermissions::class;
    $audiences = [
        'users' => ['label' => 'مستخدمون محددون', 'hint' => 'اختر أدمنز بعينهم', 'icon' => 'fa-solid fa-user-check'],
        'roles' => ['label' => 'حسب الدور', 'hint' => 'كل من يحمل الدور المختار', 'icon' => 'fa-solid fa-user-tag'],
        'all' => ['label' => 'كل الأدمنز', 'hint' => 'جميع الحسابات النشطة', 'icon' => 'fa-solid fa-users'],
    ];
    $channels = [
        'email' => ['label' => 'البريد الإلكتروني', 'hint' => 'يُرسل إلى بريد المستلم المسجل', 'icon' => 'fa-solid fa-envelope'],
        'sms' => ['label' => 'رسالة نصية SMS', 'hint' => 'يُرسل إلى رقم الجوال المسجل', 'icon' => 'fa-solid fa-comment-sms'],
        'whatsapp' => ['label' => 'واتساب', 'hint' => 'عبر واتساب للأعمال', 'icon' => 'fa-brands fa-whatsapp'],
    ];
    $channelLabels = collect($channels)->map(fn ($c) => $c['label'])->all();
    $states = [
        'pending' => ['label' => 'بانتظار الإرسال', 'icon' => 'fa-regular fa-clock'],
        'processing' => ['label' => 'جارٍ الإرسال', 'icon' => 'fa-solid fa-spinner'],
        'accepted' => ['label' => 'قبله مزود الإرسال', 'icon' => 'fa-solid fa-circle-check'],
        'failed' => ['label' => 'تعذر الإرسال', 'icon' => 'fa-solid fa-circle-xmark'],
    ];
    $canSend = in_array($permissions::SEND, $allowed, true);
    $allowedAudiences = collect($audiences)->filter(fn ($a, $key) => in_array($permissions::AUDIENCES[$key], $allowed, true));
    $allowedChannels = collect($channels)->filter(fn ($c, $key) => in_array($permissions::CHANNELS[$key], $allowed, true));
    $selectedAudience = old('audience', $allowedAudiences->count() === 1 ? $allowedAudiences->keys()->first() : '');
@endphp

<div class="container-fluid py-3 dispatch-page" dir="rtl">

    <div class="dispatch-hero">
        <div class="dispatch-hero__inner">
            <div class="dispatch-hero__title">
                <span class="dispatch-hero__icon"><i class="fa-solid fa-paper-plane"></i></span>
                <div>
                    <h4>إشعارات الأدمنز</h4>
                    <p>أرسل رسالة داخل النظام مع إمكانية إرسالها عبر القنوات الإضافية. تُحفظ نسخة لكل مستلم وتظهر في جرس الإشعارات.</p>
                </div>
            </div>
            <div class="dispatch-hero__stats">
                <div class="dispatch-stat">
                    <i class="fa-solid fa-inbox"></i>
                    <div>
                        <strong>{{ $history->total() }}</strong>
                        <span>إشعار أرسلته</span>
                    </div>
                </div>
                <div class="dispatch-stat">
                    <i class="fa-solid fa-tower-broadcast"></i>
                    <div>
                        <strong>{{ $allowedChannels->count() + 1 }}</strong>
                        <span>قنوات متاحة لك</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== Compose ===================== --}}
    <div class="dispatch-card">
        <div class="dispatch-card__header">
            <div class="dispatch-card__heading">
                <span class="dispatch-card__icon dispatch-card__icon--indigo"><i class="fa-solid fa-pen-to-square"></i></span>
                <div>
                    <h5>إنشاء إشعار جديد</h5>
                    <p>اكتب الرسالة، حدد المستلمين، ثم اختر القنوات الإضافية إن رغبت.</p>
                </div>
            </div>
        </div>
        <div class="dispatch-card__body">
            @if($canSend)
            <form method="post" action="{{ route('admin.dispatch.store') }}" id="admin-dispatch-form" novalidate>
                @csrf

                {{-- Step 1: content --}}
                <div class="dispatch-step">
                    <div class="dispatch-step__title">
                        <span class="dispatch-step__num">1</span>
                        <div>
                            <h6>محتوى الإشعار</h6>
                            <small>العنوان يظهر في جرس الإشعارات، والرسالة تُعرض عند فتحه.</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="dispatch-title">عنوان الإشعار</label>
                            <input class="form-control" id="dispatch-title" name="title" maxlength="200" required
                                placeholder="مثال: تذكير بإغلاق العقود قبل نهاية الشهر" value="{{ old('title') }}" data-counter>
                            <div class="field-counter" data-counter-for="dispatch-title"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="dispatch-message">الرسالة</label>
                            <textarea class="form-control" id="dispatch-message" name="message" rows="5" maxlength="5000" required
                                placeholder="اكتب نص الإشعار هنا..." data-counter>{{ old('message') }}</textarea>
                            <div class="field-counter" data-counter-for="dispatch-message"></div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: audience --}}
                <div class="dispatch-step">
                    <div class="dispatch-step__title">
                        <span class="dispatch-step__num">2</span>
                        <div>
                            <h6>المستلمون</h6>
                            <small>يصل الإشعار للحسابات النشطة فقط، ولا يُرسل لوكلاء المكاتب الخارجية.</small>
                        </div>
                    </div>

                    <div class="audience-picker" id="dispatch-audience">
                        @foreach($allowedAudiences as $key => $audience)
                            <input type="radio" class="btn-check" name="audience" value="{{ $key }}" id="audience-{{ $key }}"
                                autocomplete="off" required @checked($selectedAudience === $key)>
                            <label class="audience-option" for="audience-{{ $key }}">
                                <span class="audience-option__icon"><i class="{{ $audience['icon'] }}"></i></span>
                                <span class="audience-option__text">
                                    <strong>{{ $audience['label'] }}</strong>
                                    <span>{{ $audience['hint'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @if(in_array($permissions::AUDIENCES['users'], $allowed, true))
                    <div class="audience-target" data-audience="users" hidden>
                        <label class="form-label" for="dispatch-users">اختر المستخدمين</label>
                        <select class="form-select js-select2" id="dispatch-users" name="users[]" multiple
                            data-placeholder="ابحث باسم المستخدم...">
                            @foreach($admins as $recipient)
                                <option value="{{ $recipient->id }}" @selected(in_array($recipient->id, (array) old('users', [])))>{{ $recipient->name }} (#{{ $recipient->id }})</option>
                            @endforeach
                        </select>
                        <div class="field-help"><i class="fa-solid fa-circle-info me-1"></i>يمكنك اختيار أكثر من مستخدم، واكتب للبحث السريع.</div>
                    </div>
                    @endif

                    @if(in_array($permissions::AUDIENCES['roles'], $allowed, true))
                    <div class="audience-target" data-audience="roles" hidden>
                        <label class="form-label" for="dispatch-roles">اختر الأدوار</label>
                        <select class="form-select js-select2" id="dispatch-roles" name="roles[]" multiple
                            data-placeholder="ابحث باسم الدور...">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected(in_array($role->id, (array) old('roles', [])))>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <div class="field-help"><i class="fa-solid fa-circle-info me-1"></i>يصل الإشعار لكل أدمن يحمل أياً من الأدوار المختارة.</div>
                    </div>
                    @endif
                </div>

                {{-- Step 3: channels --}}
                <div class="dispatch-step">
                    <div class="dispatch-step__title">
                        <span class="dispatch-step__num">3</span>
                        <div>
                            <h6>قنوات الإرسال</h6>
                            <small>القنوات الإضافية تستخدم إعدادات الإرسال وبيانات التواصل المسجلة للمستلم.</small>
                        </div>
                    </div>
                    <div class="channel-grid">
                        <div class="channel-card is-locked" data-channel="system">
                            <span class="channel-card__lock"><i class="fa-solid fa-lock"></i> دائماً</span>
                            <span class="channel-card__icon"><i class="fa-solid fa-bell"></i></span>
                            <span class="channel-card__text">
                                <strong>داخل النظام</strong>
                                <span>يُحفظ في قاعدة البيانات ويظهر في الجرس</span>
                            </span>
                            <span class="channel-card__check"><i class="fa-solid fa-check"></i></span>
                        </div>
                        @foreach($allowedChannels as $key => $channel)
                            <input type="checkbox" class="btn-check" name="channels[]" value="{{ $key }}" id="channel-{{ $key }}"
                                autocomplete="off" @checked(in_array($key, (array) old('channels', [])))>
                            <label class="channel-card" data-channel="{{ $key }}" for="channel-{{ $key }}">
                                <span class="channel-card__icon"><i class="{{ $channel['icon'] }}"></i></span>
                                <span class="channel-card__text">
                                    <strong>{{ $channel['label'] }}</strong>
                                    <span>{{ $channel['hint'] }}</span>
                                </span>
                                <span class="channel-card__check"><i class="fa-solid fa-check"></i></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="dispatch-footer">
                    <div class="dispatch-footer__hint">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>تابع حالة الإرسال لكل قناة في السجل بالأسفل بعد الإرسال.</span>
                    </div>
                    <button class="btn-dispatch-send" type="submit" id="dispatch-submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>إرسال الإشعار</span>
                    </button>
                </div>
            </form>
            @else
            <div class="dispatch-locked">
                <i class="fa-solid fa-lock"></i>
                <strong>لا تملك صلاحية الإرسال</strong>
                <span class="small">يمكنك استعراض سجل إشعاراتك فقط. اطلب صلاحية "إرسال إشعارات للأدمنز" من مدير النظام.</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ===================== History ===================== --}}
    <div class="dispatch-card">
        <div class="dispatch-card__header">
            <div class="dispatch-card__heading">
                <span class="dispatch-card__icon dispatch-card__icon--teal"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <div>
                    <h5>سجل إشعاراتي المرسلة</h5>
                    <p>نسخة لكل مستلم مع حالة الإرسال في كل قناة.</p>
                </div>
            </div>
            @if($history->total() > 0)
                <span class="text-muted small">{{ $history->firstItem() }} - {{ $history->lastItem() }} من {{ $history->total() }}</span>
            @endif
        </div>

        @if($history->count() > 0)
        <div class="table-responsive">
            <table class="table history-table align-middle">
                <thead>
                    <tr>
                        <th style="min-width: 120px;">التاريخ</th>
                        <th style="min-width: 180px;">المستلم</th>
                        <th style="min-width: 260px;">الإشعار</th>
                        <th style="min-width: 200px;">قنوات الإرسال</th>
                        <th style="min-width: 120px;">القراءة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $item)
                        @php
                            $recipientName = $item->admin?->name;
                            $audienceKey = data_get($item->data, 'audience');
                        @endphp
                        <tr>
                            <td>
                                <div class="history-date">
                                    <strong>{{ optional($item->created_at)->format('Y-m-d') }}</strong>
                                    <span>{{ optional($item->created_at)->format('H:i') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="history-recipient">
                                    <span class="history-avatar {{ $recipientName ? '' : 'is-missing' }}">
                                        @if($recipientName)
                                            {{ mb_substr($recipientName, 0, 1) }}
                                        @else
                                            <i class="fa-solid fa-user-slash"></i>
                                        @endif
                                    </span>
                                    <span>{{ $recipientName ?? 'مستخدم غير متاح' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="history-title">{{ $item->title }}</div>
                                @if($audienceKey && isset($audiences[$audienceKey]))
                                    <span class="history-audience"><i class="{{ $audiences[$audienceKey]['icon'] }}"></i>{{ $audiences[$audienceKey]['label'] }}</span>
                                @endif
                                <details class="history-message">
                                    <summary><i class="fa-solid fa-chevron-down"></i> عرض الرسالة</summary>
                                    <p>{{ $item->message }}</p>
                                </details>
                            </td>
                            <td>
                                <div class="delivery-list">
                                    <span class="delivery-pill" data-state="saved"><i class="fa-solid fa-bell"></i>داخل النظام: محفوظ</span>
                                    @foreach($item->data['delivery'] ?? [] as $channel => $state)
                                        <span class="delivery-pill" data-state="{{ $state }}">
                                            <i class="{{ $states[$state]['icon'] ?? 'fa-solid fa-circle' }}"></i>
                                            {{ $channelLabels[$channel] ?? $channel }}: {{ $states[$state]['label'] ?? $state }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($item->read_at)
                                    <span class="read-pill is-read"><i class="fa-solid fa-envelope-open"></i>مقروء</span>
                                @else
                                    <span class="read-pill is-unread"><i class="fa-regular fa-envelope"></i>غير مقروء</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
            <div class="history-pagination">
                {{ $history->links('pagination::bootstrap-4') }}
            </div>
        @endif
        @else
        <div class="history-empty">
            <i class="fa-regular fa-paper-plane"></i>
            <strong>لا توجد إشعارات مرسلة</strong>
            <span class="small">ستظهر هنا نسخة لكل مستلم بمجرد إرسال أول إشعار.</span>
        </div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('admin-dispatch-form');
    if (!form) return;

    // select2 for multi-selects
    if (window.jQuery && jQuery.fn.select2) {
        jQuery(form).find('.js-select2').each(function () {
            jQuery(this).select2({
                width: '100%',
                dir: 'rtl',
                placeholder: this.dataset.placeholder || '',
                allowClear: true,
                closeOnSelect: false,
            });
        });
    }

    // audience toggle
    const audienceInputs = form.querySelectorAll('input[name="audience"]');
    function currentAudience() {
        const checked = form.querySelector('input[name="audience"]:checked');
        return checked ? checked.value : '';
    }
    function updateAudience() {
        const value = currentAudience();
        form.querySelectorAll('[data-audience]').forEach(function (group) {
            const active = group.dataset.audience === value;
            const select = group.querySelector('select');
            group.hidden = !active;
            select.disabled = !active;
            select.required = active;
            if (window.jQuery && jQuery.fn.select2) {
                jQuery(select).prop('disabled', !active);
            }
        });
    }
    audienceInputs.forEach(function (input) { input.addEventListener('change', updateAudience); });
    updateAudience();

    // character counters
    form.querySelectorAll('[data-counter]').forEach(function (field) {
        const counter = form.querySelector('[data-counter-for="' + field.id + '"]');
        const max = Number(field.getAttribute('maxlength')) || 0;
        if (!counter || !max) return;
        function render() {
            const len = field.value.length;
            counter.textContent = len + ' / ' + max;
            counter.classList.toggle('is-near', len >= max * 0.9 && len < max);
            counter.classList.toggle('is-full', len >= max);
        }
        field.addEventListener('input', render);
        render();
    });

    // submit guard
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }
        const button = document.getElementById('dispatch-submit');
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>جارٍ الإرسال...</span>';
    });
});
</script>
@endsection
