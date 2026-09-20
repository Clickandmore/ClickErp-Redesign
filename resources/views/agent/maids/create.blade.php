@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $defaultTypeId = $defaultTypeId ?? null;
    $selectedTypeId = (int) old('type_id', $defaultTypeId ?? 0);
    $agentOffice = $offices->first();
    $selectedNationalityId = (int) old('nationality_id', optional($agentOffice)->nationality_id);
@endphp

@section('page-title', $isAr ? 'إضافة سيرة ذاتية' : 'Add CV')

@push('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/sweetalert2.min.css">
    <style>
        :root {
            --mc-primary: #0f766e;
            --mc-primary-dark: #115e59;
            --mc-accent: #2563eb;
            --mc-ink: #0f172a;
            --mc-muted: #64748b;
            --mc-line: #e2e8f0;
            --mc-bg-soft: #f8fafc;
            --mc-danger: #dc2626;
            --mc-danger-soft: #fef2f2;
            --mc-success: #16a34a;
            --mc-warn: #f59e0b;
        }

        /* ---------- Page hero ---------- */
        .mc-hero {
            position: relative;
            border-radius: 18px;
            padding: 22px 24px;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #1e3a8a 100%);
            color: #fff;
            box-shadow: 0 18px 38px -18px rgba(15, 118, 110, .65);
            overflow: hidden;
            margin-bottom: 18px;
        }
        .mc-hero::after {
            content: "";
            position: absolute;
            inset: auto -60px -80px auto;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, .18), transparent 70%);
        }
        .mc-hero__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .mc-hero__title {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .mc-hero__icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
            backdrop-filter: blur(8px);
            font-size: 22px;
        }
        .mc-hero h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .2px;
        }
        .mc-hero p {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
        }
        .mc-hero__actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .mc-hero__btn {
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
            white-space: nowrap;
        }
        .mc-hero__btn:hover {
            background: rgba(255, 255, 255, .22);
            color: #fff;
            transform: translateY(-1px);
            text-decoration: none;
            box-shadow: 0 8px 18px -10px rgba(0, 0, 0, .35);
        }
        .mc-hero__btn--rapid {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-color: rgba(245, 158, 11, .55);
        }
        .mc-hero__btn--rapid:hover { background: linear-gradient(135deg, #d97706, #b45309); }
        .mc-hero__btn--import {
            background: linear-gradient(135deg, #16a34a, #15803d);
            border-color: rgba(34, 197, 94, .55);
        }
        .mc-hero__btn--import:hover { background: linear-gradient(135deg, #15803d, #14532d); }
        .mc-hero__btn--pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-color: rgba(220, 38, 38, .55);
        }
        .mc-hero__btn--pdf:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        .mc-hero__status {
            color: rgba(255, 255, 255, .85);
            font-size: 12px;
        }

        /* ---------- Sections ---------- */
        .mc-section {
            background: #fff;
            border: 1px solid var(--mc-line);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .08);
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .mc-section + .mc-section { margin-top: 14px; }
        .mc-section:hover {
            box-shadow: 0 12px 28px -18px rgba(15, 23, 42, .18);
        }
        .mc-section__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 1px dashed var(--mc-line);
            flex-wrap: wrap;
        }
        .mc-section__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: var(--mc-ink);
            font-size: 15px;
            margin: 0;
        }
        .mc-section__icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 118, 110, .08);
            color: var(--mc-primary);
            font-size: 15px;
        }
        .mc-section__hint {
            color: var(--mc-muted);
            font-size: 12px;
        }

        /* ---------- CV path picker ---------- */
        .mc-type-picker { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
        .mc-type-option {
            position: relative;
            width: 100%;
            min-height: 76px;
            border: 1.5px solid var(--mc-line);
            border-radius: 14px;
            background: #fff;
            color: var(--mc-ink);
            text-align: start;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease, background-color .2s ease;
        }
        .mc-type-option:hover {
            border-color: var(--mc-primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 22px -16px rgba(15, 118, 110, .45);
        }
        .mc-type-option.is-active {
            border-color: var(--mc-primary);
            background: linear-gradient(135deg, rgba(15, 118, 110, .08), rgba(37, 99, 235, .07));
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }
        .mc-type-option__icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(15, 118, 110, .1);
            color: var(--mc-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .mc-type-option.is-active .mc-type-option__icon {
            background: var(--mc-primary);
            color: #fff;
        }
        .mc-type-option__label { font-weight: 700; font-size: 14px; }
        .mc-type-option__check {
            position: absolute;
            inset-inline-end: 10px;
            top: 10px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--mc-primary);
            color: #fff;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .mc-type-option.is-active .mc-type-option__check { display: inline-flex; }
        .mc-type-native-select { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }

        /* ---------- Form controls ---------- */
        .mc-field { margin-bottom: 4px; }
        .mc-field-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: var(--mc-ink);
        }
        .mc-field-label .mc-required {
            color: var(--mc-danger);
            font-weight: 800;
        }
        .mc-field-label .mc-optional {
            color: var(--mc-muted);
            font-weight: 400;
            font-size: 11px;
        }
        .form-control, .form-select {
            min-height: 44px;
            border-radius: 10px;
            border-color: var(--mc-line);
            box-shadow: none !important;
            font-size: 14px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--mc-primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15) !important;
        }
        textarea.form-control { min-height: 80px; }

        /* ---------- Locked (read-only) field ---------- */
        .mc-locked-field {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1.5px dashed var(--mc-line);
            background: linear-gradient(135deg, rgba(15, 118, 110, .06), rgba(37, 99, 235, .04));
            color: var(--mc-ink);
            font-weight: 600;
            font-size: 14px;
        }
        .mc-locked-field--empty {
            background: var(--mc-danger-soft);
            border-color: rgba(220, 38, 38, .35);
            color: var(--mc-danger);
        }
        .mc-locked-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 118, 110, .12);
            color: var(--mc-primary);
            font-size: 13px;
            flex-shrink: 0;
        }
        .mc-locked-field--empty .mc-locked-icon {
            background: rgba(220, 38, 38, .12);
            color: var(--mc-danger);
        }
        .mc-locked-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ---------- Inline validation ---------- */
        .form-control.is-invalid,
        .form-select.is-invalid,
        .select2-container--default.is-invalid .select2-selection {
            border-color: var(--mc-danger) !important;
            background-color: var(--mc-danger-soft) !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12) !important;
        }
        .select2-container--default.is-invalid .select2-selection--single,
        .select2-container--default.is-invalid .select2-selection--multiple {
            border-color: var(--mc-danger) !important;
            background-color: var(--mc-danger-soft) !important;
        }
        .mc-error {
            display: none;
            margin-top: 6px;
            font-size: 12px;
            color: var(--mc-danger);
            font-weight: 600;
            align-items: center;
            gap: 6px;
        }
        .mc-error.is-visible { display: flex; }
        .mc-error::before {
            content: "\26A0";
            font-size: 13px;
        }
        .mc-file-error-target.is-invalid .dropify-wrapper {
            border-color: var(--mc-danger) !important;
            background-color: var(--mc-danger-soft) !important;
        }

        /* Select2 height */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 44px;
            border-radius: 10px;
            border-color: var(--mc-line);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }
        .select2-container--focus .select2-selection {
            border-color: var(--mc-primary) !important;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15) !important;
        }

        /* ---------- Language chips ---------- */
        .mc-lang-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .mc-lang-card {
            border: 1.5px solid var(--mc-line);
            border-radius: 12px;
            padding: 12px;
            background: var(--mc-bg-soft);
            transition: border-color .2s ease, background .2s ease;
        }
        .mc-lang-card:has(.lang-check:checked) {
            border-color: var(--mc-primary);
            background: rgba(15, 118, 110, .06);
        }
        .mc-lang-card .form-check {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0;
            min-height: auto;
        }
        .mc-lang-card .form-check-input {
            width: 20px;
            height: 20px;
            margin: 0;
            float: none;
            flex-shrink: 0;
            border: 1.5px solid var(--mc-line);
            cursor: pointer;
        }
        .mc-lang-card .form-check-input:checked {
            background-color: var(--mc-primary);
            border-color: var(--mc-primary);
        }
        .mc-lang-card .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
            border-color: var(--mc-primary);
        }
        .mc-lang-card .form-check-label {
            font-weight: 600;
            color: var(--mc-ink);
            font-size: 14px;
            cursor: pointer;
            margin: 0;
        }
        .mc-lang-card .lang-extra input {
            background: #fff;
        }

        /* ---------- Experience table ---------- */
        #experience_section table {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--mc-line);
        }
        #experience_section thead {
            background: var(--mc-bg-soft);
        }
        #experience_section thead th {
            font-size: 13px;
            color: var(--mc-muted);
            border: none;
        }
        #experience_section .btnAdd, #experience_section .btnRemove {
            font-size: 18px;
        }

        /* ---------- Sticky bottom actions ---------- */
        .mc-actions {
            position: sticky;
            bottom: 0;
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(10px);
            border: 1px solid var(--mc-line);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 -8px 24px -18px rgba(15, 23, 42, .25);
            margin-top: 18px;
            z-index: 5;
        }
        .mc-actions__hint {
            color: var(--mc-muted);
            font-size: 12px;
        }
        .mc-btn {
            min-height: 44px;
            padding: 0 22px;
            border-radius: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1.5px solid transparent;
            transition: transform .15s ease, background .2s ease, box-shadow .2s ease;
        }
        .mc-btn-primary {
            background: linear-gradient(135deg, var(--mc-primary), var(--mc-primary-dark));
            color: #fff;
            box-shadow: 0 12px 22px -14px rgba(15, 118, 110, .65);
        }
        .mc-btn-primary:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 18px 28px -16px rgba(15, 118, 110, .8);
        }
        .mc-btn-secondary {
            background: #fff;
            border-color: var(--mc-line);
            color: var(--mc-ink);
        }
        .mc-btn-secondary:hover {
            background: var(--mc-bg-soft);
            color: var(--mc-ink);
        }

        /* ---------- Video preview ---------- */
        .maid-video-preview {
            border-radius: 12px;
            overflow: hidden;
        }

        /* ---------- Animation on shake ---------- */
        @keyframes mc-shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-4px); }
            40% { transform: translateX(4px); }
            60% { transform: translateX(-2px); }
            80% { transform: translateX(2px); }
        }
        .mc-shake { animation: mc-shake .3s ease; }

        /* ---------- Dropify polish ---------- */
        .dropify-wrapper {
            border-radius: 12px !important;
            min-height: 160px;
        }

        @media (max-width: 768px) {
            .mc-hero { padding: 16px; }
            .mc-hero h2 { font-size: 18px; }
            .mc-actions { flex-direction: column; align-items: stretch; }
        }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('agent.maids.store') }}"
        enctype="multipart/form-data" id="maidForm" novalidate>
        @csrf

        {{-- Hero --}}
        <div class="mc-hero">
            <div class="mc-hero__row">
                <div class="mc-hero__title">
                    <span class="mc-hero__icon"><i class="fa fa-id-card-clip"></i></span>
                    <div>
                        <h2>{{ $isAr ? 'إضافة سيرة ذاتية جديدة' : 'Add New CV' }}</h2>
                        <p>{{ $isAr ? 'املأ بيانات العاملة بدقة. الحقول المطلوبة محددة بنجمة حمراء.' : 'Fill the worker data accurately. Required fields are marked with a red asterisk.' }}</p>
                    </div>
                </div>
                <div class="mc-hero__actions">
                    <input type="file" id="pdf_extract_input" accept=".pdf" class="d-none">
                    <a href="{{ route('agent.maids.create_rapid_cv') }}" class="mc-hero__btn mc-hero__btn--rapid" title="{{ $isAr ? 'إضافة سيرة مختصرة' : 'Add Short CV' }}">
                        <i class="fa fa-bolt"></i>
                        <span>{{ $isAr ? 'سيرة مختصرة' : 'Short CV' }}</span>
                    </a>
                    <a href="{{ route('agent.maids.bulk-import.create') }}" class="mc-hero__btn mc-hero__btn--import" title="{{ $isAr ? 'استيراد من ملف Excel' : 'Import from Excel' }}">
                        <i class="fa fa-file-excel"></i>
                        <span>{{ $isAr ? 'استيراد' : 'Import' }}</span>
                    </a>
                    <button type="button" class="mc-hero__btn mc-hero__btn--pdf" id="extract_cv_data_btn">
                        <i class="fa fa-file-pdf"></i>
                        <span class="extract-btn-label">{{ $isAr ? 'قراءة بيانات السيرة' : 'Read CV from PDF' }}</span>
                    </button>
                    <span class="mc-hero__status w-100" id="cv_extract_status"></span>
                </div>
            </div>
        </div>

        {{-- مسار السيرة --}}
        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-route"></i></span>
                    {{ $isAr ? 'مسار السيرة' : 'CV Path' }}
                </h3>
                <span class="mc-section__hint">{{ $isAr ? 'اختر نوع السيرة قبل المتابعة' : 'Choose CV type before proceeding' }}</span>
            </div>

            <div class="mc-type-picker" id="maid_type_picker">
                @foreach ($types as $type)
                    @php
                        $title = $type->title ?? '';
                        $normalizedTitle = mb_strtolower(str_replace(['أ', 'إ', 'آ'], 'ا', $title));
                        $isInstantReceipt = \Illuminate\Support\Str::contains(mb_strtolower($title), ['استلام', 'إستلام', 'فوري', 'instant', 'immediate']);
                        $isRentalType = \Illuminate\Support\Str::contains($normalizedTitle, ['ايجار', 'تاجير', 'rent']);
                        $isSelectedType = $selectedTypeId === (int) $type->id;
                    @endphp
                    @if (!$isInstantReceipt)
                        <button type="button"
                            class="mc-type-option {{ $isSelectedType ? 'is-active' : '' }}"
                            data-type-id="{{ $type->id }}">
                            <span class="mc-type-option__icon">
                                <i class="fa {{ $isRentalType ? 'fa-key' : 'fa-user-plus' }}"></i>
                            </span>
                            <span class="mc-type-option__label">{{ $type->title }}</span>
                            <span class="mc-type-option__check"><i class="fa fa-check"></i></span>
                        </button>
                    @endif
                @endforeach
            </div>

            <select name="type_id" id="type_id" class="form-control mc-type-native-select" data-label="{{ $isAr ? 'نوع السيرة' : 'CV Type' }}" required>
                <option value="">{{ $isAr ? 'اختر نوع السيرة' : 'Select CV Type' }}</option>
                @foreach ($types as $type)
                    @php
                        $title = $type->title ?? '';
                        $isInstantReceipt = \Illuminate\Support\Str::contains(mb_strtolower($title), ['استلام', 'إستلام', 'فوري', 'instant', 'immediate']);
                    @endphp
                    @if (!$isInstantReceipt)
                        <option value="{{ $type->id }}" {{ $selectedTypeId === (int) $type->id ? 'selected' : '' }}>
                            {{ $type->title }}
                        </option>
                    @endif
                @endforeach
            </select>
            <div class="mc-error" data-error-for="type_id">{{ $isAr ? 'يرجى اختيار نوع السيرة' : 'Please select a CV type' }}</div>
        </div>

        {{-- الملفات --}}
        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-images"></i></span>
                    {{ $isAr ? 'الملفات والصور' : 'Files & Photos' }}
                </h3>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mc-field mc-file-error-target" data-error-host="image">
                        <label class="mc-field-label">
                            {{ $isAr ? 'الصورة الشخصية' : 'Personal Photo' }}
                            <span class="mc-required">*</span>
                        </label>
                        <input type="file" data-default-file="" name="image" id="maid_image"
                            data-label="{{ $isAr ? 'الصورة الشخصية' : 'Personal Photo' }}"
                            class="dropify" required accept="image/*">
                        <div class="mc-error" data-error-for="image">{{ $isAr ? 'يرجى رفع الصورة الشخصية' : 'Please upload the personal photo' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mc-field">
                        <label class="mc-field-label">
                            {{ $isAr ? 'ملف السيرة الكامل' : 'Full CV File' }}
                            <span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span>
                        </label>
                        <input type="file" data-default-file="" name="maid_cv_file" id="cv_file"
                            class="dropify" accept=".pdf,image/*">
                    </div>
                </div>
            </div>
        </div>

        {{-- بيانات العمل --}}
        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-briefcase"></i></span>
                    {{ $isAr ? 'بيانات العمل والاستقدام' : 'Work & Recruitment Data' }}
                </h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'اسم العامل/ة' : 'Worker Name (EN)' }}<span class="mc-required">*</span></label>
                        <input type="text" name="name:en" data-label="{{ $isAr ? 'اسم العامل/ة' : 'Worker Name' }}" class="form-control" value="{{ old('name:en') }}" required>
                        <div class="mc-error" data-error-for="name:en">{{ $isAr ? 'يرجى إدخال اسم العاملة' : 'Please enter the worker name' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'الجنسية' : 'Nationality' }}<span class="mc-required">*</span></label>
                        <select name="nationality_id" id="Pnationality_id" data-label="{{ $isAr ? 'الجنسية' : 'Nationality' }}" class="form-control" required>
                            <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                            @foreach ($nationalities as $nationality)
                                <option value="{{ $nationality->id }}"
                                    data-price="{{ $nationality->price }}"
                                    data-salary="{{ $nationality->monthly_salary }}"
                                    data-service_transportation_cost="{{ $nationality->service_transportation_cost }}"
                                    data-rental_cost="{{ $nationality->rental_cost }}"
                                    {{ $selectedNationalityId === (int) $nationality->id ? 'selected' : '' }}>
                                    {{ $nationality->sub_title ?: $nationality->title }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mc-error" data-error-for="nationality_id">{{ $isAr ? 'يرجى اختيار الجنسية' : 'Please choose nationality' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">
                            {{ $isAr ? 'مكتب الاستقدام' : 'Recruitment Office' }}
                            <i class="fa fa-lock text-muted ms-1" title="{{ $isAr ? 'مكتبك المسجل' : 'Your registered office' }}"></i>
                        </label>
                        @if ($agentOffice)
                            <div class="mc-locked-field">
                                <span class="mc-locked-icon"><i class="fa fa-building"></i></span>
                                <span class="mc-locked-text">{{ $agentOffice->title }}</span>
                            </div>
                            <input type="hidden" name="office_id" value="{{ $agentOffice->id }}">
                        @else
                            <div class="mc-locked-field mc-locked-field--empty">
                                <span class="mc-locked-icon"><i class="fa fa-triangle-exclamation"></i></span>
                                <span class="mc-locked-text">{{ $isAr ? 'لا يوجد مكتب مرتبط بحسابك' : 'No office linked to your account' }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        @php $oldJobIds = array_map('intval', (array) old('job_id', [])); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'الوظائف' : 'Jobs' }}<span class="mc-required">*</span></label>
                        <select name="job_id[]" id="job_id"
                            data-placeholder="{{ $isAr ? 'اختر' : 'Choose' }}"
                            data-label="{{ $isAr ? 'الوظائف' : 'Jobs' }}"
                            class="form-control js-example-basic-multiple" multiple required>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" {{ in_array((int) $job->id, $oldJobIds, true) ? 'selected' : '' }}>{{ $job->title }}</option>
                            @endforeach
                        </select>
                        <div class="mc-error" data-error-for="job_id">{{ $isAr ? 'يرجى اختيار وظيفة واحدة على الأقل' : 'Please select at least one job' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'نوع الطلب' : 'Request Type' }}</label>
                        @php
                            $requestType = old('request_type', \App\Models\ingaz\Maid::REQUEST_TYPE_CUSTOM);
                        @endphp
                        <select name="request_type" id="request_type" class="form-control">
                            @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                <option value="{{ $value }}" {{ $requestType === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'الراتب' : 'Salary' }}</label>
                        <input type="number" name="salary" class="form-control" value="{{ old('salary', 0) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'تاريخ الميلاد' : 'Birth Date' }}<span class="mc-required">*</span></label>
                        <input type="date" name="birth_date" id="birth_date" data-label="{{ $isAr ? 'تاريخ الميلاد' : 'Birth Date' }}" class="form-control" value="{{ old('birth_date') }}" required>
                        <div class="mc-error" data-error-for="birth_date">{{ $isAr ? 'يرجى إدخال تاريخ الميلاد' : 'Please enter birth date' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'العمر' : 'Age' }}</label>
                        <input type="number" min="18" max="90" name="age" id="age" class="form-control" value="{{ old('age') }}" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'الطول (سم)' : 'Height (cm)' }}</label>
                        <input type="text" name="height" class="form-control" value="{{ old('height', 160) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'الوزن (كجم)' : 'Weight (kg)' }}</label>
                        <input type="text" name="weight" class="form-control" value="{{ old('weight', 60) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        @php $oldReligionId = (int) old('religion_id', 4); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'الديانة' : 'Religion' }}</label>
                        <select name="religion_id" class="form-control">
                            @foreach ($religions as $religion)
                                <option value="{{ $religion->id }}" {{ $oldReligionId === (int) $religion->id ? 'selected' : '' }}>{{ $religion->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        @php $oldMarital = (string) old('marital_status:ar', ''); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'الحالة الاجتماعية' : 'Marital Status' }}<span class="mc-required">*</span></label>
                        <select name="marital_status:ar" data-label="{{ $isAr ? 'الحالة الاجتماعية' : 'Marital Status' }}" class="form-control" required>
                            <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                            <option value="عازبة" {{ $oldMarital === 'عازبة' ? 'selected' : '' }}>{{ $isAr ? 'عازبة' : 'Single (F)' }}</option>
                            <option value="أعزب" {{ $oldMarital === 'أعزب' ? 'selected' : '' }}>{{ $isAr ? 'أعزب' : 'Single (M)' }}</option>
                            <option value="متزوجة" {{ $oldMarital === 'متزوجة' ? 'selected' : '' }}>{{ $isAr ? 'متزوجة' : 'Married (F)' }}</option>
                            <option value="متزوج" {{ $oldMarital === 'متزوج' ? 'selected' : '' }}>{{ $isAr ? 'متزوج' : 'Married (M)' }}</option>
                        </select>
                        <input type="hidden" name="marital_status:en" id="marital_status_en" value="{{ old('marital_status:en') }}">
                        <div class="mc-error" data-error-for="marital_status:ar">{{ $isAr ? 'يرجى اختيار الحالة الاجتماعية' : 'Please choose marital status' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'النوع' : 'Gender' }}</label>
                        @php $selectedGender = old('gender', 'female'); @endphp
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="female" {{ $selectedGender === 'female' ? 'selected' : '' }}>{{ $isAr ? 'أنثى' : 'Female' }}</option>
                            <option value="male" {{ $selectedGender === 'male' ? 'selected' : '' }}>{{ $isAr ? 'ذكر' : 'Male' }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'عدد الأطفال' : 'Children Count' }}</label>
                        <input type="number" name="childrens_count" class="form-control" min="0" step="1" value="{{ old('childrens_count', 0) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'مدة العقد' : 'Contract Duration' }}</label>
                        <input type="text" name="duration" class="form-control"
                            value="{{ old('duration', $isAr ? '24 شهر' : '24 months') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- بيانات العاملة - ظاهرة ومفتوحة افتراضياً --}}
        <div class="mc-section" id="additional_worker_information">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-circle-info"></i></span>
                    {{ $isAr ? 'بيانات العاملة' : 'Worker Information' }}
                </h3>
                <span class="mc-section__hint">{{ $isAr ? 'بيانات التواصل والمؤهل وخبرة الطبخ' : 'Contact, education, and cooking experience' }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'رقم جوال العاملة' : 'Worker Phone' }}<span class="mc-required">*</span></label>
                    <input type="tel" name="phone" class="form-control" maxlength="50" value="{{ old('phone') }}" required>
                </div></div>
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'اسم أحد أقارب العاملة' : 'Relative Name' }}<span class="mc-required">*</span></label>
                    <input type="text" name="relative_name" class="form-control" maxlength="255" value="{{ old('relative_name') }}" required>
                </div></div>
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'رقم جوال قريب العاملة' : 'Relative Phone' }}<span class="mc-required">*</span></label>
                    <input type="tel" name="relative_phone" class="form-control" maxlength="50" value="{{ old('relative_phone') }}" required>
                </div></div>
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'مدينة سكن العاملة' : 'Residence City' }}<span class="mc-required">*</span></label>
                    <input type="text" name="residence_city" class="form-control" maxlength="255" value="{{ old('residence_city') }}" required>
                </div></div>
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'مكان إصدار الجواز' : 'Passport Issue Place' }}<span class="mc-required">*</span></label>
                    <input type="text" name="passport_issue_place" class="form-control" maxlength="255" value="{{ old('passport_issue_place') }}" required>
                </div></div>
                @include('shared.education-selects', ['educationMaid' => null, 'educationRequired' => true])
                <div class="col-md-4" id="cooking_experience_field"
                    style="{{ old('experience_status') === 'has_experience' ? '' : 'display: none;' }}">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'خبرة الطبخ' : 'Cooking Experience' }}<span class="mc-required">*</span></label>
                        <select name="cooking_experience" id="cooking_experience" class="form-control"
                            {{ old('experience_status') === 'has_experience' ? 'required' : '' }}>
                            <option value="">{{ $isAr ? 'اختر خبرة الطبخ' : 'Select cooking experience' }}</option>
                            <option value="arabic_cooking" {{ old('cooking_experience') === 'arabic_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ العربي' : 'Experienced in Arabic cooking' }}</option>
                            <option value="regular_cooking" {{ old('cooking_experience') === 'regular_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ العادي' : 'Experienced in regular cooking' }}</option>
                            <option value="willing_to_learn" {{ old('cooking_experience') === 'willing_to_learn' ? 'selected' : '' }}>{{ $isAr ? 'ليس لديها خبرة بالطبخ لكن قابلة للتعلم' : 'No experience, but willing to learn' }}</option>
                            <option value="general_cooking" {{ old('cooking_experience') === 'general_cooking' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة بالطبخ عامة' : 'Has general cooking experience' }}</option>
                            <option value="no_cooking_experience" {{ old('cooking_experience') === 'no_cooking_experience' ? 'selected' : '' }}>{{ $isAr ? 'لا يوجد لديها خبرة في الطبخ' : 'Has no cooking experience' }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4"><div class="mc-field">
                    <label class="mc-field-label">{{ $isAr ? 'هل تحمل رخصة قيادة؟' : 'Has a driving license?' }}<span class="mc-required">*</span></label>
                    <select name="has_driving_license" class="form-control" required>
                        <option value="">{{ $isAr ? 'اختر' : 'Select' }}</option>
                        <option value="1" {{ old('has_driving_license') === '1' ? 'selected' : '' }}>{{ $isAr ? 'نعم' : 'Yes' }}</option>
                        <option value="0" {{ old('has_driving_license') === '0' ? 'selected' : '' }}>{{ $isAr ? 'لا' : 'No' }}</option>
                    </select>
                </div></div>
            </div>
        </div>

        {{-- بيانات الجواز --}}
        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-passport"></i></span>
                    {{ $isAr ? 'بيانات الجواز والهوية' : 'Passport & ID Data' }}
                </h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'رقم جواز العاملة' : 'Passport Number' }}<span class="mc-required">*</span></label>
                        <input type="text" name="passport_num" id="passport_num" data-label="{{ $isAr ? 'رقم الجواز' : 'Passport Number' }}" class="form-control" value="{{ old('passport_num') }}" required>
                        <div class="mc-error" data-error-for="passport_num">{{ $isAr ? 'يرجى إدخال رقم الجواز' : 'Please enter passport number' }}</div>
                    </div>
                </div>

                <div class="col-md-4 passport-extra-field">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'رقم الحدود' : 'Border Number' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="text" name="border_number" id="border_number" class="form-control" maxlength="50" value="{{ old('border_number') }}">
                    </div>
                </div>

                <div class="col-md-4 passport-extra-field">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'تاريخ إصدار الجواز' : 'Passport Issue Date' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="date" name="passport_issue_date" id="passport_issue_date" class="form-control" value="{{ old('passport_issue_date') }}">
                    </div>
                </div>

                <div class="col-md-4 passport-extra-field">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'تاريخ انتهاء الجواز' : 'Passport Expiry Date' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="date" name="passport_expiry_date" id="passport_expiry_date" class="form-control" value="{{ old('passport_expiry_date') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'رقم الهوية' : 'ID Number' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="text" name="id_number" id="id_number" class="form-control" maxlength="30" value="{{ old('id_number') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'تاريخ انتهاء الهوية' : 'ID Expiry Date' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="date" name="id_expiry_date" class="form-control" value="{{ old('id_expiry_date') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- المهارات والخبرة --}}
        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-star"></i></span>
                    {{ $isAr ? 'المهارات والخبرة والملاحظات' : 'Skills, Experience & Notes' }}
                </h3>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="mc-field">
                        @php $oldSkillIds = array_map('intval', (array) old('skills', [])); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'المهارات' : 'Skills' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <select name="skills[]" class="form-control js-example-basic-multiple" multiple>
                            @foreach ($skills as $skill)
                                <option value="{{ $skill->id }}" {{ in_array((int) $skill->id, $oldSkillIds, true) ? 'selected' : '' }}>{{ $skill->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        @php $oldExperienceStatus = old('experience_status'); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'حالة الخبرة' : 'Experience Status' }}<span class="mc-required">*</span></label>
                        <select name="experience_status" id="experience_status" data-label="{{ $isAr ? 'حالة الخبرة' : 'Experience Status' }}" class="form-control" required>
                            <option value="">{{ $isAr ? 'اختر' : 'Select' }}</option>
                            <option value="has_experience" {{ $oldExperienceStatus === 'has_experience' ? 'selected' : '' }}>{{ $isAr ? 'لديها خبرة' : 'Has Experience' }}</option>
                            <option value="no_experience" {{ $oldExperienceStatus === 'no_experience' ? 'selected' : '' }}>{{ $isAr ? 'بدون خبرة' : 'No Experience' }}</option>
                        </select>
                        <div class="mc-error" data-error-for="experience_status">{{ $isAr ? 'يرجى اختيار حالة الخبرة' : 'Please select experience status' }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'رابط الفيديو' : 'Video Link' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="text" name="video_link" class="form-control js-video-link-input" value="{{ old('video_link') }}"
                            placeholder="{{ $isAr ? 'رابط الفيديو أو كود iframe' : 'Video link or iframe code' }}">
                        <div class="maid-video-preview mt-2 d-none">
                            <div class="border rounded overflow-hidden" style="height: 220px;">
                                <iframe class="js-video-preview-frame" src="" width="100%" height="100%" style="border: 0;" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mc-field">
                        <label class="mc-field-label">
                            {{ $isAr ? 'رفع فيديو' : 'Upload Video' }}
                            <span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span>
                        </label>
                        <input type="file" name="video" id="maid_video" class="form-control"
                            accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/webm,video/x-matroska,video/x-m4v">
                        <small class="text-muted">{{ $isAr ? 'يمكن رفع ملف فيديو أو استخدام الرابط، والحد الأقصى 100 ميجابايت.' : 'Upload a video file or use the link; maximum size is 100 MB.' }}</small>
                        <button type="button" class="btn btn-outline-danger btn-sm mt-2 js-clear-maid-video">
                            {{ __('ingaz.clear_video_value') }}
                        </button>
                    </div>
                </div>

                <div class="col-12">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'ملاحظات إضافية' : 'Additional Notes' }}</label>
                        <textarea name="additional_notes" class="form-control" rows="3">{{ old('additional_notes') }}</textarea>
                    </div>
                </div>

                {{-- اللغات --}}
                <div class="col-12">
                    <label class="mc-field-label">{{ $isAr ? 'اللغات' : 'Languages' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                    <div class="mc-lang-grid">
                        @php
                            $languagesList = [
                                'arabic' => $isAr ? 'العربية' : 'Arabic',
                                'english' => $isAr ? 'الإنجليزية' : 'English',
                                'amharic' => $isAr ? 'الأمهرية' : 'Amharic',
                                'mother' => $isAr ? 'لغة الأم' : 'Mother Language',
                            ];
                            $oldLanguages = (array) old('languages', []);
                            $oldPercentages = (array) old('percentage', []);
                        @endphp
                        @foreach ($languagesList as $key => $label)
                            @php
                                $isChecked = !empty($oldLanguages[$key]);
                                $oldPct = $oldPercentages[$key] ?? null;
                            @endphp
                            <div class="mc-lang-card">
                                <div class="form-check">
                                    <input class="form-check-input lang-check" type="checkbox"
                                        name="languages[{{ $key }}]" id="{{ $key }}" value="{{ $key }}"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                                </div>
                                <div class="lang-extra mt-2 {{ $isChecked ? '' : 'd-none' }}">
                                    @if ($isChecked)
                                        <input type="number" class="form-control" name="percentage[{{ $key }}]"
                                            placeholder="{{ $isAr ? 'النسبة %' : 'Percentage %' }}" min="0" max="100"
                                            value="{{ $oldPct }}">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- الخبرات --}}
                <div class="col-12" id="experience_section" style="display: none;">
                    <label class="mc-field-label">{{ $isAr ? 'الخبرات السابقة' : 'Previous Experience' }}</label>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="experience_table">
                            <thead>
                                <tr>
                                    <th>{{ $isAr ? 'الدولة' : 'Country' }}</th>
                                    <th style="width:150px">{{ $isAr ? 'من' : 'From' }}</th>
                                    <th style="width:150px">{{ $isAr ? 'إلى' : 'To' }}</th>
                                    <th>{{ $isAr ? 'مدة الخبرة' : 'Duration' }}</th>
                                    <th>{{ $isAr ? 'المسمى الوظيفي' : 'Job Title' }}</th>
                                    <th style="width:120px">{{ $isAr ? 'الإجراءات' : 'Actions' }}</th>
                                </tr>
                            </thead>
                            <tbody id="json_array">
                                <tr class="y-group">
                                    <td>
                                        <select name="expertises[0][country_id]" class="form-control select2" data-exp-required="true">
                                            <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}">{{ $isAr ? $country->name_ar : $country->name_en }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="month" name="expertises[0][start_date]"
                                            class="form-control exp-start-date" max="{{ now()->format('Y-m') }}"
                                            data-exp-required="true">
                                    </td>
                                    <td>
                                        <input type="month" name="expertises[0][end_date]"
                                            class="form-control exp-end-date" max="{{ now()->format('Y-m') }}"
                                            data-exp-required="true">
                                    </td>
                                    <td>
                                        <div class="experience-duration-wrapper">
                                            <input type="text" class="form-control exp-duration-label"
                                                placeholder="{{ $isAr ? 'تُحسب تلقائياً' : 'Calculated' }}" readonly
                                                tabindex="-1">
                                            <input type="hidden" name="expertises[0][number_of_years]" class="exp-duration-years">
                                            <div class="invalid-feedback d-block text-danger small exp-duration-error"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="expertises[0][job_title_id]" class="form-control select2" data-exp-required="true">
                                            <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                                            @foreach ($jobs as $job)
                                                <option value="{{ $job->id }}">{{ $job->title }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-link p-0 me-2 btnAdd" title="{{ $isAr ? 'إضافة' : 'Add' }}">
                                            <i class="fa fa-plus-circle text-success fs-5"></i>
                                        </button>
                                        <button type="button" class="btn btn-link p-0 btnRemove" title="{{ $isAr ? 'حذف' : 'Delete' }}">
                                            <i class="fa fa-trash text-danger fs-5"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sticky actions --}}
        <div class="mc-actions">
            <span class="mc-actions__hint">
                <i class="fa fa-circle-info me-1"></i>
                {{ $isAr ? 'سيتم إرسال السيرة للمراجعة قبل الظهور للعملاء.' : 'CV will be submitted for review before becoming visible.' }}
            </span>
            <div class="d-flex gap-2">
                <a href="{{ route('agent.maids.index') }}" class="mc-btn mc-btn-secondary">
                    <i class="fa fa-xmark"></i>
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </a>
                <button class="mc-btn mc-btn-primary" type="submit" id="maid_submit_btn">
                    <i class="fa fa-floppy-disk"></i>
                    <span class="btn-label">{{ $isAr ? 'حفظ السيرة' : 'Save CV' }}</span>
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    @include('admin.ingaz.maids.partials.driving-license-gender')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/sweetalert2.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    @include('admin.ingaz.maids._experience_duration_js')
    <script>
        const agentCreateTranslations = {
            cvFileRequiredTitle: @json($isAr ? 'ملف السيرة مطلوب' : 'CV File Required'),
            cvFileRequiredText: @json($isAr ? 'يرجى رفع ملف السيرة أولاً' : 'Please upload the CV file first'),
            ok: @json($isAr ? 'موافق' : 'OK'),
            reading: @json($isAr ? 'جاري القراءة' : 'Reading'),
            cvReadingMayTakeSeconds: @json($isAr ? 'قد تستغرق القراءة بضع ثوانٍ' : 'Reading may take a few seconds'),
            cvReadSuccessTitle: @json($isAr ? 'تم استخراج البيانات' : 'Data Extracted'),
            cvReadSuccessText: @json($isAr ? 'تم استخراج البيانات بنجاح' : 'Data extracted successfully'),
            cvReadFailed: @json($isAr ? 'فشل استخراج البيانات' : 'Extraction failed'),
            fixErrors: @json($isAr ? 'يرجى تصحيح الحقول المظللة بالأحمر' : 'Please fix the fields highlighted in red'),
            errorTitle: @json($isAr ? 'بيانات ناقصة' : 'Missing data'),
            saving: @json($isAr ? 'جاري الحفظ...' : 'Saving...'),
            save: @json($isAr ? 'حفظ السيرة' : 'Save CV'),
        };

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        $(function () {
            $('.dropify').dropify();
            $('.js-example-basic-multiple').select2({ width: '100%' });

            // ---------- Inline validation helpers ----------
            function clearFieldError($field) {
                $field.removeClass('is-invalid');
                var name = $field.attr('name') || $field.data('error-name');
                if (!name) return;
                var $error = $('[data-error-for="' + name + '"]').first();
                if ($error.length) $error.removeClass('is-visible');
                // Select2 sync
                $field.next('.select2-container').removeClass('is-invalid');
            }

            function markFieldError($field, message) {
                $field.addClass('is-invalid');
                $field.next('.select2-container').addClass('is-invalid');
                var name = $field.attr('name');
                var $error = $('[data-error-for="' + name + '"]').first();
                if ($error.length) {
                    if (message) $error.text(message);
                    $error.addClass('is-visible');
                }
            }

            // Clear error on user input/change
            $(document).on('input change', '.form-control, .form-select', function () {
                clearFieldError($(this));
            });
            $(document).on('change', 'select.js-example-basic-multiple, #Pnationality_id, select[name="marital_status:ar"], select[name="experience_status"], select[name="type_id"]', function () {
                clearFieldError($(this));
            });
            $(document).on('change', '#maid_image', function () {
                if (this.files && this.files.length) {
                    $('[data-error-host="image"]').removeClass('is-invalid');
                    $('[data-error-for="image"]').removeClass('is-visible');
                }
            });

            // ---------- Languages percentage ----------
            $('.lang-check').on('change', function () {
                const $wrapper = $(this).closest('.mc-lang-card').find('.lang-extra');
                const val = $(this).val();
                if ($(this).is(':checked')) {
                    $wrapper.removeClass('d-none').html(
                        `<input type="number" class="form-control" name="percentage[${val}]" placeholder="{{ $isAr ? 'النسبة %' : 'Percentage %' }}" min="0" max="100" />`
                    );
                } else {
                    $wrapper.addClass('d-none').empty();
                }
            });

            // ---------- Maid type picker ----------
            function syncMaidTypePicker() {
                var selectedTypeId = $('select[name="type_id"]').val();
                $('.mc-type-option').removeClass('is-active');
                $('.mc-type-option[data-type-id="' + selectedTypeId + '"]').addClass('is-active');
            }
            $('#maid_type_picker').on('click', '.mc-type-option', function () {
                $('select[name="type_id"]').val($(this).data('type-id')).trigger('change');
            });
            $('select[name="type_id"]').on('change', syncMaidTypePicker);
            syncMaidTypePicker();

            // ---------- Marital status translation ----------
            var maritalStatusEnField = $('#marital_status_en');
            var maritalStatusTranslations = { 'عازبة': 'single', 'أعزب': 'single', 'متزوجة': 'married', 'متزوج': 'married' };
            function updateMaritalStatusTranslation() {
                var v = $('select[name="marital_status:ar"]').val();
                maritalStatusEnField.val(v && maritalStatusTranslations[v] ? maritalStatusTranslations[v] : '');
            }
            $('select[name="marital_status:ar"]').on('change', updateMaritalStatusTranslation);
            updateMaritalStatusTranslation();

            // ---------- ID validation ----------
            $('#id_number').on('input', function () {
                $(this).val($(this).val().replace(/[^0-9a-zA-Z-]/g, ''));
            });

            // ---------- Birth date <-> age ----------
            $('#birth_date').on('change', function () {
                var birthDate = new Date($(this).val());
                if (isNaN(birthDate.getTime())) return;
                var today = new Date();
                var age = today.getFullYear() - birthDate.getFullYear();
                var monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age--;
                $('#age').val(age);
            });

            // ---------- Experience section toggle ----------
            function toggleExperienceSection() {
                var status = $('#experience_status').val();
                var $section = $('#experience_section');
                var $req = $section.find('[data-exp-required]');
                var $cookingField = $('#cooking_experience_field');
                var $cookingSelect = $('#cooking_experience');
                if (status === 'has_experience') {
                    $section.slideDown();
                    $req.attr('required', 'required');
                    $cookingField.slideDown();
                    $cookingSelect.attr('required', 'required');
                    $section.find('select.select2').each(function () {
                        if (!$(this).data('select2')) $(this).select2({ width: '100%' });
                    });
                } else {
                    $req.removeAttr('required');
                    $section.slideUp();
                    $cookingSelect.removeAttr('required').val('');
                    $cookingField.slideUp();
                }
            }
            $('#experience_status').on('change', toggleExperienceSection);
            toggleExperienceSection();

            // ---------- Sync experience duration ----------
            // المنطق مشترك مع شاشات لوحة التحكم: admin.ingaz.maids._experience_duration_js
            function syncExperienceRow($row) {
                if (typeof window.syncExperienceRow === 'function') {
                    window.syncExperienceRow($row);
                }
            }
            $(document).on('input change', '.exp-start-date, .exp-end-date', function () {
                syncExperienceRow($(this).closest('tr.y-group'));
            });

            // ---------- Add/remove experience rows ----------
            function reindexRows() {
                $('#json_array tr.y-group').each(function (i) {
                    $(this).find('input, select, textarea').each(function () {
                        var name = $(this).attr('name') || '';
                        name = name.replace(/\[\d+\]/, '[' + i + ']');
                        $(this).attr('name', name);
                    });
                });
            }
            $(document).on('click', '.btnAdd', function () {
                var $row = $(this).closest('tr');
                var $clone = $row.clone();
                $clone.find('input, textarea').val('');
                $clone.find('span.select2').remove();
                $clone.find('select.select2').each(function () {
                    $(this).removeAttr('data-select2-id').removeClass('select2-hidden-accessible').prop('selectedIndex', 0);
                });
                $row.after($clone);
                reindexRows();
                $clone.find('select.select2').select2({ width: '100%' });
            });
            $(document).on('click', '.btnRemove', function () {
                var $rows = $('#json_array tr.y-group');
                if ($rows.length <= 1) return;
                $(this).closest('tr').remove();
                reindexRows();
            });

            // ---------- Video link preview ----------
            function extractVideoLink(value) {
                value = (value || '').trim();
                if (!value) return '';
                var m = value.match(/<iframe\b[^>]*\bsrc\s*=\s*(["']?)([^"'\s>]+)\1/i);
                return m && m[2] ? $('<textarea/>').html(m[2]).text().trim() : value;
            }
            function updatePreview() {
                var v = extractVideoLink($('.js-video-link-input').val());
                var $p = $('.maid-video-preview'), $f = $('.js-video-preview-frame');
                if (/^https?:\/\//i.test(v)) {
                    $f.attr('src', v); $p.removeClass('d-none');
                } else {
                    $f.attr('src', ''); $p.addClass('d-none');
                }
            }
            $('.js-video-link-input').on('input change paste', function () { setTimeout(updatePreview, 0); });
            updatePreview();

            $('.js-clear-maid-video').on('click', function () {
                $('#maid_video').val('');
                $('.js-video-link-input').val('').trigger('change');
            });

            // ---------- CV extract from PDF (upload-then-read flow) ----------
            // Step 1: clicking the hero button opens the hidden file picker.
            $('#extract_cv_data_btn').on('click', function () {
                $('#pdf_extract_input').val('').trigger('click');
            });

            // Step 2: once a PDF is chosen, read it and POST text to the extractor.
            $('#pdf_extract_input').on('change', async function () {
                var file = this.files && this.files.length ? this.files[0] : null;
                if (!file) return;

                // Also push the file into the CV file Dropify so it is submitted with the form.
                try {
                    var $cvDropify = $('#cv_file');
                    var dropifyInst = $cvDropify.data('dropify');
                    if (typeof DataTransfer !== 'undefined') {
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        $cvDropify.get(0).files = dt.files;
                    }
                    if (dropifyInst && file.type === 'application/pdf') {
                        dropifyInst.resetPreview();
                        dropifyInst.clearElement();
                        dropifyInst.input.files = $cvDropify.get(0).files;
                        $cvDropify.trigger('change');
                    }
                } catch (e) { /* non-fatal */ }

                var $btn = $('#extract_cv_data_btn');
                var $label = $btn.find('.extract-btn-label');
                var $status = $('#cv_extract_status');
                var originalLabel = $label.text();

                $btn.prop('disabled', true);
                $label.text(agentCreateTranslations.reading);
                $status.text(agentCreateTranslations.cvReadingMayTakeSeconds);

                var resetBtn = function () {
                    $btn.prop('disabled', false);
                    $label.text(originalLabel);
                };

                var reader = new FileReader();
                reader.onload = async function () {
                    try {
                        var typedarray = new Uint8Array(this.result);
                        var pdf = await pdfjsLib.getDocument(typedarray).promise;
                        var fullText = '';
                        for (var i = 1; i <= pdf.numPages; i++) {
                            var page = await pdf.getPage(i);
                            var tc = await page.getTextContent();
                            fullText += tc.items.map(it => it.str).join(' ') + '\n';
                        }

                        var resp = await fetch("{{ route('agent.maids.extract-cv') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content') },
                            body: JSON.stringify({ text: fullText })
                        });
                        var data = await resp.json();

                        if (data.name) $('input[name="name:en"]').val(data.name).trigger('change');
                        if (data.passport_num) $('#passport_num').val(data.passport_num).trigger('change');
                        if (data.nationality_id) $('#Pnationality_id').val(data.nationality_id).trigger('change');
                        if (data.age) $('#age').val(data.age);
                        if (data.religion_id) $('select[name="religion_id"]').val(data.religion_id).trigger('change');

                        $status.text(agentCreateTranslations.cvReadSuccessText);
                        Swal.fire({
                            icon: 'success',
                            title: agentCreateTranslations.cvReadSuccessTitle,
                            text: agentCreateTranslations.cvReadSuccessText,
                            confirmButtonText: agentCreateTranslations.ok,
                            timer: 1800,
                            timerProgressBar: true,
                        });
                    } catch (e) {
                        console.error(e);
                        $status.text(agentCreateTranslations.cvReadFailed);
                        Swal.fire({
                            icon: 'error',
                            title: agentCreateTranslations.cvReadFailed,
                            text: agentCreateTranslations.cvReadFailed,
                            confirmButtonText: agentCreateTranslations.ok
                        });
                    } finally {
                        resetBtn();
                    }
                };
                reader.onerror = function () {
                    $status.text(agentCreateTranslations.cvReadFailed);
                    resetBtn();
                };
                reader.readAsArrayBuffer(file);
            });

            // ---------- Form submission validation (field-level) ----------
            function validateForm() {
                var firstInvalid = null;

                // Clear previous errors
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.select2-container').removeClass('is-invalid');
                $('.mc-error').removeClass('is-visible');
                $('[data-error-host="image"]').removeClass('is-invalid');

                // Required text/select fields
                $('#maidForm').find('[required]').each(function () {
                    var $f = $(this);
                    if ($f.is(':disabled') || $f.is(':hidden') && $f.attr('type') !== 'file' && !$f.hasClass('mc-type-native-select')) return;
                    if ($f.attr('type') === 'file') return; // handled separately

                    var v = $f.val();
                    var empty = (v === null || v === '' || (Array.isArray(v) && v.length === 0));
                    if (empty) {
                        markFieldError($f);
                        if (!firstInvalid) firstInvalid = $f;
                    }
                });

                // Personal photo file
                var imageInput = $('#maid_image').get(0);
                if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
                    var $host = $('[data-error-host="image"]');
                    $host.addClass('is-invalid');
                    $('[data-error-for="image"]').addClass('is-visible');
                    if (!firstInvalid) firstInvalid = $host;
                }

                return firstInvalid;
            }

            $('#maidForm').on('submit', function (e) {
                var videoFile = $('#maid_video')[0].files[0];
                if (videoFile && videoFile.size > 100 * 1024 * 1024) {
                    e.preventDefault();
                    Swal.fire({ icon: 'error', text: @json(__('ingaz.maid_video_too_large')) });
                    return false;
                }
                $('.js-video-link-input').val(extractVideoLink($('.js-video-link-input').val()));
                var firstInvalid = validateForm();
                if (firstInvalid) {
                    e.preventDefault();
                    // Shake + scroll
                    var $target = firstInvalid.closest('.mc-section, .mc-field, [data-error-host]');
                    if ($target.length) {
                        $target.addClass('mc-shake');
                        setTimeout(function () { $target.removeClass('mc-shake'); }, 350);
                        $('html, body').animate({
                            scrollTop: Math.max(0, $target.offset().top - 100)
                        }, 350);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: agentCreateTranslations.errorTitle,
                        text: agentCreateTranslations.fixErrors,
                        confirmButtonText: agentCreateTranslations.ok,
                        timer: 2200,
                        timerProgressBar: true,
                    });
                    return false;
                }

                // Sync experience rows before submit
                $('#json_array tr.y-group').each(function () { syncExperienceRow($(this)); });

                // Disable submit + show spinner
                var $btn = $('#maid_submit_btn');
                $btn.prop('disabled', true);
                $btn.find('.btn-label').text(agentCreateTranslations.saving);
            });
        });
    </script>
@endpush
