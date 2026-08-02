@extends('admin.settings.sections.layout')

@php($activeSection = 'cv-template')

@section('settings-title', 'إعداد قالب السيرة الذاتية')

@section('settings-description')
    ارفع ملف قالب Blade مستقل يمثل تصميم السيرة الذاتية كاملًا (هيدر + بودي + تنسيق)، وسيُستخدم لتوليد ملف PDF
    وحفظه في حقل <code>cv_preview</code> عند إنشاء السيرة الذاتية أو تعديلها.
@endsection

@section('settings-alerts')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('settings-content')
    <style>
        .cv-template-guide {
            --guide-accent: var(--bs-primary, #0d6efd);
            --guide-accent-rgb: var(--bs-primary-rgb, 13, 110, 253);
        }

        .cv-template-guide code {
            direction: ltr;
            unicode-bidi: isolate;
            background: #f3f5fa;
            color: #b3306f;
            border: 1px solid #e3e7f0;
            padding: 0.1rem 0.4rem;
            border-radius: 6px;
            font-size: 0.82em;
            white-space: nowrap;
        }

        .cv-template-guide .accordion-item {
            border: 1px solid var(--bs-border-color, #e6e9f0);
            border-radius: 14px !important;
            overflow: hidden;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 14px rgba(16, 24, 40, 0.04);
        }

        .cv-template-guide .accordion-button {
            font-weight: 600;
            background: #fbfcfe;
            box-shadow: none;
        }

        .cv-template-guide .accordion-button:not(.collapsed) {
            background: rgba(var(--guide-accent-rgb), 0.07);
            color: var(--guide-accent);
        }

        .cv-template-guide .guide-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(var(--guide-accent-rgb), 0.1);
            color: var(--guide-accent);
            font-size: 0.85rem;
        }

        .cv-template-guide .guide-card {
            height: 100%;
            border: 1px solid var(--bs-border-color, #e6e9f0);
            border-radius: 12px;
            background: #fff;
            padding: 0.9rem 1rem;
            transition: all 0.2s ease-in-out;
        }

        .cv-template-guide .guide-card:hover {
            border-color: rgba(var(--guide-accent-rgb), 0.45);
            box-shadow: 0 8px 20px rgba(var(--guide-accent-rgb), 0.09);
        }

        .cv-template-guide .guide-card h6 {
            font-size: 0.92rem;
            margin: 0 0 0.2rem;
            font-weight: 700;
        }

        .cv-template-guide .guide-card p {
            font-size: 0.82rem;
            line-height: 1.85;
            color: var(--bs-secondary-color, #6c757d);
            margin: 0;
        }

        .cv-template-guide .guide-card.is-warning {
            --guide-accent: #dc3545;
            --guide-accent-rgb: 220, 53, 69;
        }

        .cv-template-guide .guide-card.is-success {
            --guide-accent: #198754;
            --guide-accent-rgb: 25, 135, 84;
        }

        .cv-template-guide .code-window {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #2b2b40;
        }

        .cv-template-guide .code-window-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #15151f;
            padding: 0.5rem 0.85rem;
            border-bottom: 1px solid #2b2b40;
        }

        .cv-template-guide .code-window-bar .dots span {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-inline-end: 5px;
        }

        .cv-template-guide .code-window-bar .file-name {
            direction: ltr;
            color: #9aa2b1;
            font-size: 0.75rem;
        }

        .cv-template-guide pre {
            direction: ltr;
            text-align: left;
            background: #1e1e2d;
            color: #e6e6e6;
            padding: 1rem;
            margin: 0;
            max-height: 430px;
            overflow: auto;
            font-size: 0.78rem;
            line-height: 1.7;
            tab-size: 4;
        }

        .cv-template-guide pre::-webkit-scrollbar {
            width: 9px;
            height: 9px;
        }

        .cv-template-guide pre::-webkit-scrollbar-thumb {
            background: #3f3f5a;
            border-radius: 8px;
        }

        .cv-template-guide .vars-table th,
        .cv-template-guide .vars-table td {
            vertical-align: middle;
            font-size: 0.83rem;
        }

        .cv-template-guide .vars-table thead th {
            background: #f5f7fb;
            font-size: 0.8rem;
            text-transform: none;
        }

        .cv-template-guide .fields-cloud code {
            margin: 0 0 0.35rem 0.35rem;
        }

        .cv-template-guide .structure-map {
            direction: ltr;
            text-align: left;
            background: #f7f9fc;
            border: 1px dashed var(--bs-border-color, #d7dce5);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            font-size: 0.78rem;
            line-height: 1.9;
            color: #465064;
            margin: 0;
            overflow-x: auto;
        }
    </style>

    <div class="cv-template-guide mb-4">
        <div class="accordion" id="cvTemplateGuide">
            <div class="accordion-item">
                <h2 class="accordion-header" id="cvGuideRequirementsHeading">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#cvGuideRequirements" aria-expanded="true" aria-controls="cvGuideRequirements">
                        <span class="guide-icon me-2"><i class="fas fa-circle-info"></i></span>
                        متطلبات ملف القالب &mdash; اقرأ أولًا
                    </button>
                </h2>
                <div id="cvGuideRequirements" class="accordion-collapse collapse show"
                    aria-labelledby="cvGuideRequirementsHeading" data-bs-parent="#cvTemplateGuide">
                    <div class="accordion-body">
                        <p class="text-muted mb-4">
                            الملف المرفوع يُنفَّذ كصفحة HTML كاملة ومستقلة ثم يُحوَّل إلى PDF، لذلك يجب أن يكون
                            <strong class="text-body">ملف تصميم قائمًا بذاته</strong> وليس جزءًا من قوالب النظام.
                        </p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="guide-card is-warning">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-link-slash"></i></span>
                                        <h6 class="align-self-center">مستقل عن الـ layout</h6>
                                    </div>
                                    <p>
                                        القالب لا يرث أي تصميم من لوحة التحكم أو الموقع، لذلك لا تستخدم
                                        <code>@@extends</code> <code>@@section</code> <code>@@include</code>
                                        <code>@@yield</code>.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-file-code"></i></span>
                                        <h6 class="align-self-center">صفحة HTML كاملة</h6>
                                    </div>
                                    <p>
                                        يبدأ الملف بـ <code>&lt;!DOCTYPE html&gt;</code> ثم <code>&lt;html&gt;</code>
                                        وبداخله <code>&lt;head&gt;</code> و<code>&lt;body&gt;</code>.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-heading"></i></span>
                                        <h6 class="align-self-center">الهيدر &mdash; <code>&lt;head&gt;</code></h6>
                                    </div>
                                    <p>
                                        يحتوي على <code>&lt;meta charset="utf-8"&gt;</code> وكل تنسيقات CSS داخل وسم
                                        <code>&lt;style&gt;</code>، مع ضبط الاتجاه العربي عبر
                                        <code>&lt;html dir="rtl" lang="ar"&gt;</code>.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-align-right"></i></span>
                                        <h6 class="align-self-center">البودي &mdash; <code>&lt;body&gt;</code></h6>
                                    </div>
                                    <p>
                                        أقسام السيرة: ترويسة بالشعار واسم المكتب، صورة العاملة وبياناتها الأساسية،
                                        الجنسية والديانة والعمر، الخبرات، المهارات، اللغات، والملاحظات.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card is-warning">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-palette"></i></span>
                                        <h6 class="align-self-center">التنسيق داخلي فقط</h6>
                                    </div>
                                    <p>
                                        استخدم <code>&lt;style&gt;</code> داخل الملف. لا تعتمد على ملفات CSS خارجية أو
                                        Bootstrap أو خطوط من الإنترنت لأنها قد لا تُحمَّل أثناء توليد الـ PDF.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card is-warning">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fab fa-js"></i></span>
                                        <h6 class="align-self-center">بدون JavaScript</h6>
                                    </div>
                                    <p>
                                        لا يُبنى المحتوى عبر JS؛ كل شيء يجب أن يظهر في HTML مباشرة عند تحميل الصفحة.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card is-success">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-file-lines"></i></span>
                                        <h6 class="align-self-center">مقاس A4</h6>
                                    </div>
                                    <p>
                                        أضف <code>@@page { size: A4; margin: 0 }</code> واجعل عرض الصفحة
                                        <code>210mm</code> وارتفاعها <code>297mm</code>، ولصفحة جديدة استخدم
                                        <code>page-break-after: always</code>.
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="guide-card is-success">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-image"></i></span>
                                        <h6 class="align-self-center">الصور</h6>
                                    </div>
                                    <p>
                                        استخدم <code>showFile($maid->image)</code> للحصول على رابط الصورة الكامل،
                                        ويفضّل تحديد أبعاد ثابتة للصورة داخل CSS.
                                    </p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="guide-card">
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="guide-icon"><i class="fas fa-upload"></i></span>
                                        <h6 class="align-self-center">مواصفات الملف</h6>
                                    </div>
                                    <p>
                                        الامتداد <code>.blade.php</code> &nbsp;•&nbsp; الترميز <code>UTF-8</code>
                                        &nbsp;•&nbsp; الحجم الأقصى <code>2MB</code>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-sitemap me-1 text-primary"></i> الهيكل المتوقع للملف
                        </h6>
                        <pre class="structure-map mb-4">&lt;!DOCTYPE html&gt;
&lt;html dir="rtl" lang="ar"&gt;
├── &lt;head&gt;
│     ├── &lt;meta charset="utf-8"&gt;
│     └── &lt;style&gt; <span dir="rtl">كل تنسيقات السيرة</span> &lt;/style&gt;
└── &lt;body&gt;
      ├── <span dir="rtl">ترويسة: الشعار واسم المكتب</span>
      ├── <span dir="rtl">صورة العاملة وجدول بياناتها الأساسية</span>
      ├── <span dir="rtl">الخبرات</span>
      ├── <span dir="rtl">المهارات واللغات</span>
      └── <span dir="rtl">الملاحظات</span></pre>

                        <div class="alert alert-light border d-flex gap-2 mb-0">
                            <i class="fas fa-lightbulb text-warning mt-1"></i>
                            <div class="small">
                                <strong>نصيحة:</strong>
                                صمّم الملف كصفحة HTML عادية واختبرها في المتصفح
                                <code>Ctrl + P</code> ← حفظ كـ PDF قبل رفعها، فما تراه في معاينة الطباعة هو تقريبًا ما
                                سيظهر في الملف المولد.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="cvGuideVariablesHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#cvGuideVariables" aria-expanded="false" aria-controls="cvGuideVariables">
                        <span class="guide-icon me-2"><i class="fas fa-database"></i></span>
                        المتغيرات المتاحة داخل القالب
                    </button>
                </h2>
                <div id="cvGuideVariables" class="accordion-collapse collapse" aria-labelledby="cvGuideVariablesHeading"
                    data-bs-parent="#cvTemplateGuide">
                    <div class="accordion-body">
                        <p class="text-muted small mb-3">
                            هذه المتغيرات تُمرَّر تلقائيًا إلى القالب عند التوليد، ويمكنك استخدامها مباشرة داخل الملف.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle vars-table mb-4">
                                <thead>
                                    <tr>
                                        <th style="width: 20%">المتغير</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>$maid</code></td>
                                        <td>بيانات العاملة الكاملة، ومعها العلاقات المحمّلة مسبقًا:
                                            <code>nationality</code> <code>religion</code> <code>jobTypes</code>
                                            <code>jobTitles</code> <code>expertises</code> — ومع الأخيرة
                                            <code>country</code> و<code>jobTitle</code> — إضافة إلى
                                            <code>office</code> و<code>recruitmentOffice</code>.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>$settings</code></td>
                                        <td>إعدادات الموقع العامة: اسم الموقع، الشعار، الهاتف، العنوان… لاستخدامها في
                                            ترويسة وتذييل السيرة.</td>
                                    </tr>
                                    <tr>
                                        <td><code>$skills</code></td>
                                        <td>قائمة المهارات المعرّفة في النظام مع ترجماتها، لعرضها ومطابقتها مع
                                            <code>$maid->skills</code>.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>$experience</code></td>
                                        <td>مصفوفة تحتوي على <code>status</code> حالة الخبرة، و<code>countries</code>
                                            دول الخبرة، و<code>details</code> تفاصيل الخبرات.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>$type</code></td>
                                        <td>نوع الطلب الخاص بالعاملة: استقدام / تأجير…</td>
                                    </tr>
                                    <tr>
                                        <td><code>$cvPdfMode</code></td>
                                        <td>دائمًا <code>true</code> أثناء التوليد، ويمكن استخدامه لإخفاء أي عناصر
                                            تفاعلية.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-tags me-1 text-primary"></i> أهم حقول <code>$maid</code>
                        </h6>
                        <p class="fields-cloud mb-0">
                            <code>name</code> <code>age</code> <code>birth_date</code> <code>gender</code>
                            <code>passport_num</code> <code>passport_expiry_date</code> <code>salary</code>
                            <code>experience_years</code> <code>experience_status</code> <code>height</code>
                            <code>weight</code> <code>childrens_count</code> <code>education</code>
                            <code>languages</code> <code>language_levels</code> <code>has_driving_license</code>
                            <code>skills</code> <code>image</code> <code>additional_notes</code>
                            <code>nationality->name</code> <code>religion->name</code>
                        </p>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="cvGuideSampleHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#cvGuideSample" aria-expanded="false" aria-controls="cvGuideSample">
                        <span class="guide-icon me-2"><i class="fas fa-code"></i></span>
                        نموذج جاهز للبدء منه
                    </button>
                </h2>
                <div id="cvGuideSample" class="accordion-collapse collapse" aria-labelledby="cvGuideSampleHeading"
                    data-bs-parent="#cvTemplateGuide">
                    <div class="accordion-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="text-muted small">
                                انسخ المحتوى التالي واحفظه في ملف باسم <code>cv-template.blade.php</code> ثم عدّل عليه
                                وارفعه.
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="copyCvTemplateSample">
                                <i class="fas fa-copy me-1"></i> نسخ النموذج
                            </button>
                        </div>

                        <div class="code-window">
                            <div class="code-window-bar">
                                <span class="dots">
                                    <span style="background:#ff5f57"></span>
                                    <span style="background:#febc2e"></span>
                                    <span style="background:#28c840"></span>
                                </span>
                                <span class="file-name">cv-template.blade.php</span>
                            </div>
<pre id="cvTemplateSample">@verbatim<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>CV - {{ $maid->name }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DejaVu Sans", Tahoma, sans-serif;
            color: #222;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm;
            margin: 0 auto;
        }
        .cv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .cv-header h1 { font-size: 20px; margin: 0; }
        .cv-header img { height: 60px; }
        .cv-body { display: flex; gap: 16px; }
        .cv-photo img {
            width: 120px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 6px 8px;
            font-size: 12px;
            text-align: right;
        }
        th { background: #f5f7fb; width: 35%; }
        h2.section-title {
            font-size: 14px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #0d6efd;
        }
        .tags span {
            display: inline-block;
            background: #eef3ff;
            border-radius: 12px;
            padding: 3px 10px;
            margin: 2px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- الهيدر --}}
        <div class="cv-header">
            <h1>{{ $settings->website_name ?? '' }}</h1>
            @if (!empty($settings->logo))
                <img src="{{ showFile($settings->logo) }}" alt="logo">
            @endif
        </div>

        {{-- البودي --}}
        <div class="cv-body">
            <div class="cv-photo">
                @if (!empty($maid->image))
                    <img src="{{ showFile($maid->image) }}" alt="{{ $maid->name }}">
                @endif
            </div>

            <div style="flex:1">
                <table>
                    <tr><th>الاسم</th><td>{{ $maid->name }}</td></tr>
                    <tr><th>الجنسية</th><td>{{ $maid->nationality->name ?? '-' }}</td></tr>
                    <tr><th>الديانة</th><td>{{ $maid->religion->name ?? '-' }}</td></tr>
                    <tr><th>العمر</th><td>{{ $maid->age ?? '-' }}</td></tr>
                    <tr><th>الحالة الاجتماعية</th><td>{{ $maid->childrens_count ?? '-' }}</td></tr>
                    <tr><th>رقم الجواز</th><td>{{ $maid->passport_num ?? '-' }}</td></tr>
                    <tr><th>تاريخ انتهاء الجواز</th><td>{{ $maid->passport_expiry_date ?? '-' }}</td></tr>
                    <tr><th>الراتب</th><td>{{ $maid->salary ?? '-' }}</td></tr>
                    <tr><th>سنوات الخبرة</th><td>{{ $maid->experience_years ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        {{-- الخبرات --}}
        <h2 class="section-title">الخبرات</h2>
        <table>
            @forelse ($experience['details'] as $item)
                <tr>
                    <th>{{ $item->country->name ?? '-' }}</th>
                    <td>{{ $item->jobTitle->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="2">لا توجد خبرات مسجلة</td></tr>
            @endforelse
        </table>

        {{-- المهارات --}}
        <h2 class="section-title">المهارات</h2>
        <div class="tags">
            @foreach ($skills as $skill)
                <span>{{ $skill->name }}</span>
            @endforeach
        </div>

        {{-- ملاحظات --}}
        @if (!empty($maid->additional_notes))
            <h2 class="section-title">ملاحظات</h2>
            <p style="font-size:12px">{{ $maid->additional_notes }}</p>
        @endif
    </div>
</body>
</html>@endverbatim</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('settings.cv-template.update') }}" enctype="multipart/form-data"
        class="cv-template-guide row g-4">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="guide-card">
                <input type="hidden" name="cv_template_enabled" value="0">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" name="cv_template_enabled"
                        id="cv_template_enabled" value="1"
                        {{ old('cv_template_enabled', $settings->cv_template_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="cv_template_enabled">
                        تفعيل توليد السيرة الذاتية تلقائيًا
                    </label>
                </div>
                <p class="mb-0">عند التفعيل سيتم توليد الصيغة المختارة أدناه بعد إنشاء السيرة الذاتية أو تعديلها.</p>
            </div>
        </div>

        <div class="col-12">
            <div class="guide-card">
                <label class="form-label fw-bold d-block mb-3">
                    <i class="fas fa-file-export text-primary me-1"></i> نوع الملف الناتج عند التوليد
                </label>
                @php($selectedOutputType = old('cv_template_output_type', $settings->cv_template_output_type ?? 'pdf'))
                <div class="d-flex flex-wrap gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="cv_template_output_type"
                            id="cv_template_output_type_pdf" value="pdf"
                            {{ $selectedOutputType === 'pdf' ? 'checked' : '' }}>
                        <label class="form-check-label" for="cv_template_output_type_pdf">
                            <i class="fas fa-file-pdf text-danger me-1"></i> PDF
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="cv_template_output_type"
                            id="cv_template_output_type_image" value="image"
                            {{ $selectedOutputType === 'image' ? 'checked' : '' }}>
                        <label class="form-check-label" for="cv_template_output_type_image">
                            <i class="fas fa-file-image text-info me-1"></i> صورة
                        </label>
                    </div>
                </div>
                <div class="form-text mt-2">
                    يستخدم الخيار المختار في التوليد الفردي والجماعي والتوليد التلقائي بعد إنشاء أو تعديل السيرة.
                    تُنشأ PNG عند توفر Chrome، ويُستخدم SVG تلقائيًا على الخوادم التي لا تحتوي على متصفح.
                </div>
                @error('cv_template_output_type')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-bold" for="cv_template_file">
                <i class="fas fa-file-arrow-up text-primary me-1"></i> قالب السيرة الذاتية
            </label>
            <input type="file" class="form-control @error('cv_template_file') is-invalid @enderror"
                name="cv_template_file" id="cv_template_file" accept=".blade.php">
            <div class="form-text">
                ملف <code>.blade.php</code> مستقل بحد أقصى 2MB، يحتوي على صفحة HTML كاملة:
                <code>&lt;head&gt;</code> للتنسيق و<code>&lt;body&gt;</code> لمحتوى السيرة.
                راجع قسم <strong>متطلبات ملف القالب</strong> أعلاه والنموذج الجاهز قبل الرفع.
            </div>
            @error('cv_template_file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if (filled($settings->cv_template_blade))
            <div class="col-12">
                <div class="guide-card is-success d-flex align-items-center gap-3">
                    <span class="guide-icon"><i class="fas fa-circle-check"></i></span>
                    <div>
                        <h6 class="mb-1">القالب المحفوظ حاليًا</h6>
                        <p class="mb-0">
                            <code>{{ $settings->cv_template_filename ?: 'template.blade.php' }}</code>
                            &nbsp;•&nbsp;
                            {{ number_format(strlen($settings->cv_template_blade) / 1024, 1) }} KB
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="alert alert-warning d-flex gap-2 mb-0">
                <i class="fas fa-shield-halved mt-1"></i>
                <div class="small">
                    قالب Blade قابل لتنفيذ تعليمات Laravel، لذلك لا تمنح صلاحية هذه الصفحة إلا للمستخدمين الموثوقين.
                    يفضل أن يكون القالب مهيأ للطباعة بحجم A4 وألا يعتمد على JavaScript في بناء المحتوى.
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i> حفظ الإعدادات
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var button = document.getElementById('copyCvTemplateSample');
            var sample = document.getElementById('cvTemplateSample');

            if (!button || !sample) {
                return;
            }

            button.addEventListener('click', function () {
                var original = button.innerHTML;
                var done = function () {
                    button.innerHTML = '<i class="fas fa-check me-1"></i> تم النسخ';
                    setTimeout(function () {
                        button.innerHTML = original;
                    }, 2000);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(sample.innerText).then(done);
                    return;
                }

                var area = document.createElement('textarea');
                area.value = sample.innerText;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
                done();
            });
        });
    </script>
@endsection
