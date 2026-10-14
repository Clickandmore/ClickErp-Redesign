@extends('admin.hourly_rental_settings.layout')

@section('page-title', __('موديل التأجير بالساعة'))

@section('style')
    <style>
        .hr-page { --hr-ink: #0f172a; --hr-muted: #64748b; --hr-line: #e2e8f0; --hr-soft: #f8fafc; --hr-brand: #0e7490; --hr-accent: #6861ee; }
        .hr-page .form-label { font-weight: 600; color: #334155; font-size: 0.86rem; margin-bottom: 0.35rem; }
        .hr-page .form-control, .hr-page .form-select { border-radius: 10px; border-color: #dbe3ec; min-height: 42px; }
        .hr-page .form-control:focus, .hr-page .form-select:focus { border-color: #67e8f9; box-shadow: 0 0 0 0.2rem rgba(14, 116, 144, 0.12); }
        .hr-page .form-text { font-size: 0.78rem; color: var(--hr-muted); }

        /* الترويسة */
        .hr-hero { border-radius: 20px; padding: 1.4rem 1.6rem; background: linear-gradient(135deg, #0f172a 0%, #164e63 100%); color: #e2e8f0; margin-bottom: 1.5rem; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.18); }
        .hr-hero h4 { margin: 0; font-weight: 700; color: #fff; font-size: 1.25rem; }
        .hr-hero p  { margin: 0.35rem 0 0; color: #94a3b8; font-size: 0.86rem; }
        .hr-hero-top { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(148, 163, 184, 0.2); }
        .hr-hero-top .btn { border-radius: 10px; font-size: 0.82rem; padding: 0.4rem 0.9rem; }
        .hr-hero-btn { background: rgba(255, 255, 255, 0.08); color: #e2e8f0; border: 1px solid rgba(255, 255, 255, 0.18); }
        .hr-hero-btn:hover { background: rgba(255, 255, 255, 0.16); color: #fff; }
        .hr-chip { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; border-radius: 999px; font-size: 0.82rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
        .hr-chip-on   { background: rgba(34, 197, 94, 0.15); color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
        .hr-chip-off  { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border-color: rgba(148, 163, 184, 0.3); }
        .hr-chip-warn { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.35); }

        /* القائمة الجانبية */
        .hr-aside { position: sticky; top: 16px; }
        .hr-nav { background: #fff; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06); padding: 0.6rem; }
        .hr-nav-title { font-size: 0.72rem; font-weight: 700; color: var(--hr-muted); letter-spacing: 0.04em; padding: 0.5rem 0.75rem 0.4rem; }
        .hr-nav a { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.75rem; border-radius: 11px; color: #334155; font-size: 0.86rem; font-weight: 600; text-decoration: none; transition: background .15s, color .15s; }
        .hr-nav a i { width: 18px; text-align: center; color: #94a3b8; transition: color .15s; }
        .hr-nav a:hover { background: var(--hr-soft); color: var(--hr-ink); }
        .hr-nav a.active { background: #ecfeff; color: var(--hr-brand); }
        .hr-nav a.active i { color: var(--hr-brand); }
        .hr-progress { background: #fff; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06); padding: 1rem 1.1rem; margin-top: 1rem; }
        .hr-progress-bar { height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin: 0.55rem 0 0.35rem; }
        .hr-progress-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #22c55e, #16a34a); }

        /* البطاقات */
        .hr-card { border: 0; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06); overflow: hidden; margin-bottom: 1.25rem; background: #fff; scroll-margin-top: 16px; }
        .hr-card > .hr-head { border-bottom: 1px solid #eef2f7; padding: 1.1rem 1.35rem; display: flex; align-items: center; gap: 0.85rem; }
        .hr-head .hr-head-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; display: grid; place-items: center; background: #ecfeff; color: var(--hr-brand); font-size: 1.05rem; }
        .hr-head h5 { margin: 0; font-weight: 700; color: var(--hr-ink); font-size: 1.03rem; }
        .hr-head p  { margin: 0.15rem 0 0; font-size: 0.82rem; color: var(--hr-muted); line-height: 1.6; }
        .hr-body { padding: 1.35rem; }
        .hr-legend { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 700; color: #475569; margin: 1.5rem 0 0.9rem; }
        .hr-legend::after { content: ''; flex: 1; height: 1px; background: var(--hr-line); }
        .hr-legend i { color: #94a3b8; }
        .hr-body > .hr-legend:first-child { margin-top: 0; }

        /* المفاتيح */
        .hr-switches { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.75rem; }
        .hr-switch { display: flex; align-items: flex-start; gap: 0.85rem; padding: 0.9rem 1rem; border: 1px solid var(--hr-line); border-radius: 14px; background: var(--hr-soft); margin: 0; transition: border-color .15s, background .15s; }
        .hr-switch:has(input:checked) { border-color: #a5f3fc; background: #f5feff; }
        .hr-switch .form-check-input { width: 2.6em; height: 1.4em; margin: 0.1em 0 0; flex: 0 0 auto; cursor: pointer; }
        .hr-switch label { font-weight: 700; color: var(--hr-ink); cursor: pointer; margin: 0; font-size: 0.9rem; }
        .hr-switch small { display: block; color: var(--hr-muted); margin-top: 0.2rem; font-size: 0.79rem; line-height: 1.6; }
        .hr-switch.hr-master { border-color: var(--hr-brand); background: #ecfeff; padding: 1.1rem 1.15rem; }
        .hr-switch.hr-master label { font-size: 1rem; }

        /* أيام الأسبوع والمدن */
        .hr-days { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .hr-days label, .hr-cities label { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; border: 1px solid var(--hr-line); border-radius: 10px; background: #fff; font-size: 0.85rem; font-weight: 600; color: #334155; cursor: pointer; margin: 0; transition: border-color .15s, background .15s, color .15s; }
        .hr-days label:hover, .hr-cities label:hover { border-color: #94a3b8; }
        .hr-days input, .hr-cities input { margin: 0; cursor: pointer; }
        .hr-days label:has(input:checked) { border-color: #fca5a5; background: #fef2f2; color: #b91c1c; }
        .hr-cities { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.5rem; }
        .hr-cities label:has(input:checked) { border-color: #67e8f9; background: #ecfeff; color: var(--hr-brand); }

        /* الملاحظات */
        .hr-note { border-radius: 12px; padding: 0.8rem 1rem; font-size: 0.82rem; line-height: 1.7; border: 1px solid; display: flex; gap: 0.6rem; align-items: flex-start; }
        .hr-note-info { background: #f0f9ff; border-color: #bae6fd; color: #075985; }
        .hr-note-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }

        /* مزود الخرائط */
        .hr-picks { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; }
        .hr-pick { display: flex; align-items: flex-start; gap: 0.7rem; padding: 0.9rem 1rem; border: 1px solid var(--hr-line); border-radius: 14px; background: var(--hr-soft); cursor: pointer; margin: 0; transition: border-color .15s, background .15s, box-shadow .15s; }
        .hr-pick:hover { border-color: #a5b4fc; }
        .hr-pick .form-check-input { margin: 0.2rem 0 0; flex: 0 0 auto; cursor: pointer; }
        .hr-pick strong { display: block; color: var(--hr-ink); font-size: 0.92rem; }
        .hr-pick small { display: block; color: var(--hr-muted); font-size: 0.79rem; margin-top: 0.15rem; }
        .hr-pick-tag { display: inline-block; font-size: 0.7rem; font-weight: 700; border-radius: 999px; padding: 0.1rem 0.5rem; margin-top: 0.35rem; }
        .hr-pick-free .hr-pick-tag { background: #dcfce7; color: #166534; }
        .hr-pick-paid .hr-pick-tag { background: #fef3c7; color: #92400e; }
        .hr-pick:has(input:checked) { border-color: var(--hr-accent); background: #f5f4ff; box-shadow: 0 6px 18px rgba(104, 97, 238, .12); }
        .hr-maps-key { margin-top: 1rem; padding: 1rem; border: 1px dashed #fbbf24; border-radius: 14px; background: #fffdf5; }
        .hr-maps-key input, .hr-page input[dir="ltr"] { font-family: ui-monospace, Menlo, Consolas, monospace; text-align: left; }

        /* صندوق رمضان */
        .hr-subbox { margin-top: 1rem; border: 1px solid var(--hr-line); border-radius: 14px; overflow: hidden; }
        .hr-subbox .hr-switch { border: 0; border-radius: 0; border-bottom: 1px solid var(--hr-line); }
        .hr-subbox-body { padding: 1rem; }

        /* حالة البناء */
        .hr-ready { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.5rem; }
        .hr-ready li { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.85rem; border: 1px solid var(--hr-line); border-radius: 12px; font-size: 0.85rem; }
        .hr-ready li i { flex: 0 0 auto; }
        .hr-ready li.done i { color: #16a34a; }
        .hr-ready li.todo { color: var(--hr-muted); background: var(--hr-soft); }
        .hr-ready li.todo i { color: #cbd5e1; }

        /* شريط الحفظ الثابت */
        .hr-savebar { position: sticky; bottom: 12px; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.8rem 1rem 0.8rem 0.8rem; margin-bottom: 1.25rem; border-radius: 16px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid var(--hr-line); box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14); }
        .hr-savebar span { font-size: 0.82rem; color: var(--hr-muted); }
        .hr-savebar .btn { border-radius: 11px; padding: 0.55rem 1.5rem; font-weight: 700; }
    </style>
@endsection

@section('content')
@php
    $doneCount = collect($readiness)->where('done', true)->count();
    $readyPercent = count($readiness) ? round($doneCount * 100 / count($readiness)) : 0;
    $selectedCities = array_map('intval', (array) old('city_ids', $hourly['city_ids']));
    $closedDays = array_map('intval', (array) old('closed_weekdays', $hourly['closed_weekdays']));
    $mapProvider = old('map_provider', $hourly['map_provider']);
    $sections = [
        'sec-activation' => ['fa-toggle-on', __('التفعيل والربط')],
        'sec-schedule'   => ['fa-calendar-days', __('أوقات الحجز والنطاق')],
        'sec-maps'       => ['fa-map-location-dot', __('مزود الخرائط')],
        'sec-websocket'  => ['fa-satellite-dish', __('الاتصال المباشر')],
        'sec-routing'    => ['fa-route', __('المسارات وتتبع GPS')],
        'sec-rules'      => ['fa-sliders', __('قواعد التشغيل')],
        'sec-caps'       => ['fa-scale-balanced', __('سقوف ساعات العمل')],
        'sec-readiness'  => ['fa-layer-group', __('حالة البناء')],
    ];
@endphp
<div class="hr-page">

    <div class="hr-hero">
        <div class="hr-hero-top">
            <a class="btn hr-hero-btn" href="{{ route('dev-superadmin.modules') }}"><i class="fa fa-arrow-right ms-1"></i> {{ __('العودة إلى لوحة السوبر أدمن') }}</a>
            <form method="POST" action="{{ route('dev-superadmin.logout') }}" class="m-0">
                @csrf
                <button class="btn hr-hero-btn" type="submit"><i class="fa fa-right-from-bracket ms-1"></i> {{ __('تسجيل الخروج') }}</button>
            </form>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4><i class="fa fa-clock ms-2"></i> {{ __('موديل التأجير بالساعة') }}</h4>
                <p>{{ __('باقات بالساعة، عاملات تخرج من الإيواء صباحًا وتعود مساءً، وسائقون يديرون التوصيل والاستلام') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($hourly['is_enabled'])
                    <span class="hr-chip hr-chip-on"><i class="fa fa-circle-check"></i> {{ __('الموديل مفعّل') }}</span>
                @else
                    <span class="hr-chip hr-chip-off"><i class="fa fa-circle-pause"></i> {{ __('الموديل متوقف') }}</span>
                @endif
                <span class="hr-chip {{ $doneCount === count($readiness) ? 'hr-chip-on' : 'hr-chip-warn' }}">
                    <i class="fa fa-layer-group"></i> {{ $doneCount }}/{{ count($readiness) }} {{ __('مراحل مبنية') }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- القائمة الجانبية --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="hr-aside">
                <nav class="hr-nav" id="hr-nav">
                    <div class="hr-nav-title">{{ __('أقسام الإعدادات') }}</div>
                    @foreach ($sections as $anchor => [$icon, $label])
                        <a href="#{{ $anchor }}"><i class="fa {{ $icon }}"></i> {{ $label }}</a>
                    @endforeach
                </nav>
                <div class="hr-progress">
                    <div class="d-flex justify-content-between small fw-bold">
                        <span>{{ __('جاهزية الموديل') }}</span>
                        <span>{{ $readyPercent }}%</span>
                    </div>
                    <div class="hr-progress-bar"><span style="width: {{ $readyPercent }}%"></span></div>
                    @if ($hourly['updated_at'])
                        <div class="form-text m-0">{{ __('آخر حفظ:') }} {{ $hourly['updated_at'] }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-9">

            @if (session('success'))
                <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger border-0 rounded-3">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($blockers)
                <div class="hr-note hr-note-warn mb-3">
                    <i class="fa fa-triangle-exclamation mt-1"></i>
                    <div>
                        <strong>{{ __('لا يمكن إطفاء الموديل الآن.') }}</strong>
                        {{ implode(' ', $blockers) }}
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('dev-superadmin.hourly-rental-settings.update') }}">
                @csrf
                @method('PUT')

                {{-- التفعيل والربط --}}
                <div class="hr-card" id="sec-activation">
                    <div class="hr-head">
                        <div class="hr-head-icon"><i class="fa fa-toggle-on"></i></div>
                        <div>
                            <h5>{{ __('التفعيل والربط بالمنظومة') }}</h5>
                            <p>{{ __('الموديل إضافة جانبية: إطفاؤه يخفي كل شاشاته ومساراته دون أي أثر على الاستقدام أو الإيجار الشهري') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <p class="alert alert-info">تفعيل نظام الساعات وإيقافه من صفحة الموديولات في السوبر أدمن فقط.</p>

                        <div class="hr-switches">
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="link_maid_profiles" name="link_maid_profiles" value="1"
                                    {{ old('link_maid_profiles', $hourly['link_maid_profiles']) ? 'checked' : '' }}>
                                <div>
                                    <label for="link_maid_profiles">{{ __('الربط بسير العاملات') }}</label>
                                    <small>{{ __('تظهر في سيرة العاملة بطاقة «التأجير بالساعة» لتفعيلها في المجمّع وضبط أجر الساعة وسقوف العمل. لا يُعدَّل أي حقل قائم في السيرة.') }}</small>
                                </div>
                            </div>
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="link_shelter" name="link_shelter" value="1"
                                    {{ old('link_shelter', $hourly['link_shelter']) ? 'checked' : '' }}>
                                <div>
                                    <label for="link_shelter">{{ __('الربط بشاشات الإيواء') }}</label>
                                    <small>{{ __('تظهر حركة الخروج والعودة اليومية وحالة «خارج الآن» في كشوف الإيواء. حالة الإقامة في الإيواء نفسها لا تُمس إطلاقًا.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- أوقات الحجز والنطاق التشغيلي --}}
                <div class="hr-card" id="sec-schedule">
                    <div class="hr-head">
                        <div class="hr-head-icon" style="background:#fce7f3;color:#be185d"><i class="fa fa-calendar-days"></i></div>
                        <div>
                            <h5>{{ __('أوقات الحجز والنطاق التشغيلي') }}</h5>
                            <p>{{ __('متى تُتاح فترات الحجز للعملاء، ومن أين تخرج العاملات، وفي أي المدن تعمل الخدمة') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <div class="hr-legend"><i class="fa fa-clock"></i> {{ __('فترات الحجز') }}</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="working_start">{{ __('بداية فترات الحجز') }}</label>
                                <input class="form-control @error('working_start') is-invalid @enderror" type="time" id="working_start" name="working_start" value="{{ old('working_start', $hourly['working_start']) }}" required>
                                @error('working_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="working_end">{{ __('نهاية فترات الحجز') }}</label>
                                <input class="form-control @error('working_end') is-invalid @enderror" type="time" id="working_end" name="working_end" value="{{ old('working_end', $hourly['working_end']) }}" required>
                                @error('working_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="slot_minutes">{{ __('الفاصل بين الفترات') }}</label>
                                <select class="form-select" id="slot_minutes" name="slot_minutes">
                                    @foreach ([15, 30, 60] as $minutes)
                                        <option value="{{ $minutes }}" {{ (int) old('slot_minutes', $hourly['slot_minutes']) === $minutes ? 'selected' : '' }}>{{ $minutes }} {{ __('دقيقة') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label d-block">{{ __('أيام الإغلاق') }}</label>
                            <div class="hr-days">
                                @foreach (['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] as $day => $label)
                                    <label>
                                        <input type="checkbox" class="form-check-input" name="closed_weekdays[]" value="{{ $day }}" {{ in_array($day, $closedDays, true) ? 'checked' : '' }}>
                                        {{ __($label) }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="form-text">{{ __('لا تُعرض فترات حجز في الأيام المحددة.') }}</div>
                        </div>

                        <div class="hr-legend"><i class="fa fa-map-location-dot"></i> {{ __('النطاق التشغيلي') }}</div>

                        <div class="mb-3">
                            <label class="form-label" for="default_shelter_place_id">{{ __('مكان الإيواء الافتراضي للخروج والعودة') }}</label>
                            <select class="form-select @error('default_shelter_place_id') is-invalid @enderror" id="default_shelter_place_id" name="default_shelter_place_id">
                                <option value="">{{ __('— يُحدد لكل عاملة من عقد إيوائها —') }}</option>
                                @foreach ($shelters as $shelter)
                                    <option value="{{ $shelter->id }}" {{ (string) old('default_shelter_place_id', $hourly['default_shelter_place_id']) === (string) $shelter->id ? 'selected' : '' }}>
                                        {{ $shelter->title ?? ('#' . $shelter->id) }}{{ $shelter->city ? ' — ' . $shelter->city : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('default_shelter_place_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ __('يُستخدم فقط حين لا يكون لعقد إيواء العاملة مكان محدد.') }}</div>
                        </div>

                        <div>
                            <label class="form-label">{{ __('المدن المغطاة بالخدمة') }}</label>
                            @if ($cities->isEmpty())
                                <div class="hr-note hr-note-warn">
                                    <i class="fa fa-circle-info mt-1"></i>
                                    <div>{{ __('لا توجد مدن نشطة في جدول المدن بعد. أضف المدن أولًا ثم اخترها هنا.') }}</div>
                                </div>
                            @else
                                <div class="hr-cities">
                                    @foreach ($cities as $city)
                                        <label>
                                            <input type="checkbox" class="form-check-input" name="city_ids[]" value="{{ $city->id }}"
                                                {{ in_array((int) $city->id, $selectedCities, true) ? 'checked' : '' }}>
                                            <span>{{ $city->name_ar ?: $city->name_en }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            <div class="form-text">{{ __('تُعرض باقات الساعات وتُقبل الحجوزات في هذه المدن فقط. ابدأ بمدينة واحدة.') }}</div>
                        </div>
                    </div>
                </div>

                {{-- مزود الخرائط --}}
                <div class="hr-card" id="sec-maps">
                    <div class="hr-head">
                        <div class="hr-head-icon" style="background:#ede9fe;color:#6d28d9"><i class="fa fa-map-location-dot"></i></div>
                        <div>
                            <h5>{{ __('مزود الخرائط') }}</h5>
                            <p>{{ __('يُطبَّق على جميع خرائط نظام الساعات: عناوين العملاء، المدن والأحياء وحدودها، ومواقع الفرق ومناطق تغطيتها. يظهر التغيير بعد حفظ الإعداد وتحديث الشاشة.') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <div class="hr-picks">
                            <label class="hr-pick hr-pick-free">
                                <input class="form-check-input" type="radio" name="map_provider" value="{{ \App\Support\HourlyRentalSettings::MAP_OSM }}"
                                    {{ $mapProvider === \App\Support\HourlyRentalSettings::MAP_OSM ? 'checked' : '' }}>
                                <span>
                                    <strong><i class="fa fa-map ms-1"></i> {{ __('خرائط مجانية') }} — OpenStreetMap</strong>
                                    <small>{{ __('بلا مفتاح وبلا فوترة، وهي المستخدمة أصلًا في صفحات الفرق والأحياء ونموذج الطلب.') }}</small>
                                    <span class="hr-pick-tag">{{ __('مجاني بالكامل') }}</span>
                                </span>
                            </label>
                            <label class="hr-pick hr-pick-paid">
                                <input class="form-check-input" type="radio" name="map_provider" value="{{ \App\Support\HourlyRentalSettings::MAP_GOOGLE }}"
                                    {{ $mapProvider === \App\Support\HourlyRentalSettings::MAP_GOOGLE ? 'checked' : '' }}>
                                <span>
                                    <strong><i class="fa fa-map-location-dot ms-1"></i> {{ __('خرائط Google') }}</strong>
                                    <small>{{ __('بحث بالاسم أدق داخل السعودية، ويحتاج مفتاحًا وحساب فوترة على Google Cloud.') }}</small>
                                    <span class="hr-pick-tag">{{ __('مجاني ضمن حصة شهرية ثم بالاستهلاك') }}</span>
                                </span>
                            </label>
                        </div>

                        <div class="hr-maps-key" id="maps-key-box" {{ $mapProvider !== \App\Support\HourlyRentalSettings::MAP_GOOGLE ? 'hidden' : '' }}>
                            <label class="form-label" for="google_maps_browser_key">{{ __('مفتاح Google Maps للمتصفح') }}</label>
                            <input type="text" dir="ltr" spellcheck="false" autocomplete="off" placeholder="AIza..."
                                class="form-control @error('google_maps_browser_key') is-invalid @enderror"
                                id="google_maps_browser_key" name="google_maps_browser_key"
                                value="{{ old('google_maps_browser_key', $hourly['google_maps_browser_key']) }}">
                            @error('google_maps_browser_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                {{ __('فعّل Maps JavaScript API وGeocoding API، وقيّد المفتاح بنطاق اللوحة — فهو ظاهر في مصدر الصفحة.') }}
                            </div>
                        </div>

                        <div class="hr-legend"><i class="fa fa-gear"></i> {{ __('إعداد متقدم') }}</div>
                        <label class="form-label" for="geocoding_url">{{ __('رابط خدمة البحث عن المواقع (Geocoding)') }}</label>
                        <input type="url" class="form-control @error('geocoding_url') is-invalid @enderror" dir="ltr" id="geocoding_url" name="geocoding_url" value="{{ old('geocoding_url', $hourly['geocoding_url']) }}" required>
                        @error('geocoding_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('خدمة متوافقة مع Nominatim. اترك الرابط الافتراضي ما لم تستخدم خادم بحث خاصًا.') }}</div>

                        <div class="hr-note hr-note-warn mt-3">
                            <i class="fa fa-triangle-exclamation mt-1"></i>
                            <div>{{ __('إن رفضت Google المفتاح لاحقًا تعود الشاشات تلقائيًا إلى الخرائط المجانية بدل أن تبقى بلا خريطة. لصق روابط الخرائط وإدخال الإحداثيات يعملان مع المزودَين دون أي مفتاح.') }}</div>
                        </div>
                    </div>
                </div>

                @include('admin.hourly_rental_settings._websocket')

                {{-- المسارات وتتبع GPS --}}
                <div class="hr-card" id="sec-routing">
                    <div class="hr-head">
                        <div class="hr-head-icon" style="background:#e0f2fe;color:#0369a1"><i class="fa fa-route"></i></div>
                        <div>
                            <h5>{{ __('hourly_routing.settings.title') }}</h5>
                            <p>{{ __('hourly_routing.settings.intro') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <div class="hr-switches">
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="route_client_to_client" name="route_client_to_client" value="1"
                                    {{ old('route_client_to_client', $hourly['route_client_to_client']) ? 'checked' : '' }}>
                                <div>
                                    <label for="route_client_to_client">{{ __('hourly_routing.settings.c2c') }}</label>
                                    <small>{{ __('hourly_routing.settings.c2c_hint') }}</small>
                                </div>
                            </div>
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="tracking_enabled" name="tracking_enabled" value="1"
                                    {{ old('tracking_enabled', $hourly['tracking_enabled']) ? 'checked' : '' }}>
                                <div>
                                    <label for="tracking_enabled">{{ __('hourly_routing.settings.enabled') }}</label>
                                    <small>{{ __('hourly_routing.settings.enabled_hint') }}</small>
                                </div>
                            </div>
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="tracking_background" name="tracking_background" value="1"
                                    {{ old('tracking_background', $hourly['tracking_background']) ? 'checked' : '' }}>
                                <div>
                                    <label for="tracking_background">{{ __('hourly_routing.settings.background') }}</label>
                                    <small>{{ __('hourly_routing.settings.background_hint') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label" for="tracking_interval_seconds">{{ __('hourly_routing.settings.interval') }}</label>
                                <input type="number" min="5" max="600" class="form-control @error('tracking_interval_seconds') is-invalid @enderror" id="tracking_interval_seconds" name="tracking_interval_seconds" value="{{ old('tracking_interval_seconds', $hourly['tracking_interval_seconds']) }}" required>
                                @error('tracking_interval_seconds')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('hourly_routing.settings.interval_hint') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tracking_active_minutes">{{ __('hourly_routing.settings.active_minutes') }}</label>
                                <input type="number" min="1" max="240" class="form-control @error('tracking_active_minutes') is-invalid @enderror" id="tracking_active_minutes" name="tracking_active_minutes" value="{{ old('tracking_active_minutes', $hourly['tracking_active_minutes']) }}" required>
                                @error('tracking_active_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="route_distance_warning_km">{{ __('hourly_routing.settings.distance_warning') }}</label>
                                <input type="number" min="1" max="1000" class="form-control @error('route_distance_warning_km') is-invalid @enderror" id="route_distance_warning_km" name="route_distance_warning_km" value="{{ old('route_distance_warning_km', $hourly['route_distance_warning_km']) }}" required>
                                @error('route_distance_warning_km')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('hourly_routing.settings.distance_warning_hint') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- قواعد التشغيل --}}
                <div class="hr-card" id="sec-rules">
                    <div class="hr-head">
                        <div class="hr-head-icon" style="background:#fef3c7;color:#d97706"><i class="fa fa-sliders"></i></div>
                        <div>
                            <h5>{{ __('قواعد التشغيل الافتراضية') }}</h5>
                            <p>{{ __('تُطبَّق على كل الباقات ما لم تحدد الباقة قيمة خاصة بها') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <div class="hr-legend"><i class="fa fa-calendar-check"></i> {{ __('الزيارات والمواعيد') }}</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="max_visits_per_day">{{ __('أقصى زيارات للعاملة في اليوم') }}</label>
                                <input type="number" min="1" max="5" class="form-control @error('max_visits_per_day') is-invalid @enderror" id="max_visits_per_day" name="max_visits_per_day" value="{{ old('max_visits_per_day', $hourly['max_visits_per_day']) }}" required>
                                @error('max_visits_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="min_notice_hours">{{ __('أقل مهلة قبل موعد الزيارة (ساعة)') }}</label>
                                <input type="number" min="0" max="168" class="form-control @error('min_notice_hours') is-invalid @enderror" id="min_notice_hours" name="min_notice_hours" value="{{ old('min_notice_hours', $hourly['min_notice_hours']) }}" required>
                                @error('min_notice_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="pickup_grace_minutes">مهلة السماح للاستلام بعد انتهاء الخدمة (دقيقة)</label>
                                <input type="number" class="form-control mb-3" id="pickup_grace_minutes" name="pickup_grace_minutes" min="0" max="240" value="{{ old('pickup_grace_minutes', $hourly['pickup_grace_minutes']) }}" required>
                                <label class="form-label" for="delay_escalation_minutes">التصعيد بعد مهلة السماح (دقيقة)</label>
                                <input type="number" class="form-control mb-3" id="delay_escalation_minutes" name="delay_escalation_minutes" min="5" max="1440" value="{{ old('delay_escalation_minutes', $hourly['delay_escalation_minutes']) }}" required>
                                <label class="form-label" for="curfew_time">{{ __('وقت الحظر للعودة إلى الإيواء') }}</label>
                                <input type="time" class="form-control @error('curfew_time') is-invalid @enderror" id="curfew_time" name="curfew_time" value="{{ old('curfew_time', $hourly['curfew_time']) }}" required>
                                @error('curfew_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('أي سجل حركة لم يُغلق قبله يتحول إلى «متأخر» وينبّه مشرف الإيواء.') }}</div>
                            </div>
                        </div>

                        <div class="hr-legend"><i class="fa fa-ban"></i> {{ __('الإلغاء والتجديد') }}</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="cancellation_window_hours">{{ __('نافذة الإلغاء المتأخر (ساعة)') }}</label>
                                <input type="number" min="0" max="168" class="form-control @error('cancellation_window_hours') is-invalid @enderror" id="cancellation_window_hours" name="cancellation_window_hours" value="{{ old('cancellation_window_hours', $hourly['cancellation_window_hours']) }}" required>
                                @error('cancellation_window_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="cancellation_fee_percent">{{ __('رسوم الإلغاء المتأخر (%)') }}</label>
                                <input type="number" min="0" max="100" class="form-control @error('cancellation_fee_percent') is-invalid @enderror" id="cancellation_fee_percent" name="cancellation_fee_percent" value="{{ old('cancellation_fee_percent', $hourly['cancellation_fee_percent']) }}" required>
                                @error('cancellation_fee_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="renewal_reminder_days">{{ __('تذكير بتجديد الباقة قبل آخر زيارة (يوم)') }}</label>
                                <input type="number" min="0" max="60" class="form-control @error('renewal_reminder_days') is-invalid @enderror" id="renewal_reminder_days" name="renewal_reminder_days" value="{{ old('renewal_reminder_days', $hourly['renewal_reminder_days']) }}" required>
                                @error('renewal_reminder_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('يُرسل للعميل إشعار «تذكير بتجديد الباقة» مرة واحدة لكل طلب، ويُستخدم أيضًا لعداد «التجديدات». 0 = بلا تذكير.') }}</div>
                            </div>
                        </div>
                        <div class="hr-switch form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="renewal_count_free_visits" name="renewal_count_free_visits" value="1"
                                {{ old('renewal_count_free_visits', $hourly['renewal_count_free_visits']) ? 'checked' : '' }}>
                            <div>
                                <label for="renewal_count_free_visits">{{ __('احتساب الزيارات المجانية والتعويضية في تاريخ انتهاء العقد') }}</label>
                                <small>{{ __('عند الإيقاف (الافتراضي) ينتهي العقد بآخر زيارة مدفوعة. الزيارات الملغاة لا تُحسب أبدًا.') }}</small>
                            </div>
                        </div>
                        <div class="hr-note hr-note-info mt-3">
                            <i class="fa fa-shield-halved mt-1"></i>
                            <div>{{ __('الإلغاء داخل النافذة يستهلك الزيارة ويُحتسب عليه الرسم. خارجها تعود الزيارة إلى رصيد العميل كاملة.') }}</div>
                        </div>

                        <div class="hr-legend"><i class="fa fa-mobile-screen"></i> {{ __('تطبيق الفريق والاستحقاقات') }}</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="offer_timeout_minutes">{{ __('مهلة قبول السائق للمشوار (دقيقة)') }}</label>
                                <input type="number" min="1" max="120" class="form-control @error('offer_timeout_minutes') is-invalid @enderror" id="offer_timeout_minutes" name="offer_timeout_minutes" value="{{ old('offer_timeout_minutes', $hourly['offer_timeout_minutes']) }}" required>
                                @error('offer_timeout_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="staff_app_otp_minutes">{{ __('صلاحية رمز دخول الفريق (دقيقة)') }}</label>
                                <input type="number" min="1" max="60" class="form-control @error('staff_app_otp_minutes') is-invalid @enderror" id="staff_app_otp_minutes" name="staff_app_otp_minutes" value="{{ old('staff_app_otp_minutes', $hourly['staff_app_otp_minutes']) }}" required>
                                @error('staff_app_otp_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="staff_app_stop_radius_meters">{{ __('نطاق التحقق عند المحطة (متر)') }}</label>
                                <input type="number" min="0" max="5000" class="form-control @error('staff_app_stop_radius_meters') is-invalid @enderror" id="staff_app_stop_radius_meters" name="staff_app_stop_radius_meters" value="{{ old('staff_app_stop_radius_meters', $hourly['staff_app_stop_radius_meters']) }}" required>
                                @error('staff_app_stop_radius_meters')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('0 = إيقاف التحقق الجغرافي.') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="staff_app_driver_stop_rate">{{ __('استحقاق السائق لكل محطة (ر.س)') }}</label>
                                <input type="number" min="0" max="9999999" step="0.01" class="form-control @error('staff_app_driver_stop_rate') is-invalid @enderror" id="staff_app_driver_stop_rate" name="staff_app_driver_stop_rate" value="{{ old('staff_app_driver_stop_rate', $hourly['staff_app_driver_stop_rate']) }}" required>
                                @error('staff_app_driver_stop_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="staff_app_worker_hour_rate">{{ __('استحقاق العاملة لكل ساعة (ر.س)') }}</label>
                                <input type="number" min="0" max="9999999" step="0.01" class="form-control @error('staff_app_worker_hour_rate') is-invalid @enderror" id="staff_app_worker_hour_rate" name="staff_app_worker_hour_rate" value="{{ old('staff_app_worker_hour_rate', $hourly['staff_app_worker_hour_rate']) }}" required>
                                @error('staff_app_worker_hour_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="hr-switches mt-3">
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="staff_app_driver_acceptance" name="staff_app_driver_acceptance" value="1" {{ old('staff_app_driver_acceptance', $hourly['staff_app_driver_acceptance']) ? 'checked' : '' }}>
                                <div><label for="staff_app_driver_acceptance">{{ __('يتطلب المشوار قبول السائق') }}</label><small>{{ __('عند الإيقاف يُعد المشوار مقبولًا فور إسناده من اللوحة.') }}</small></div>
                            </div>
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="staff_app_require_delivery_code" name="staff_app_require_delivery_code" value="1" {{ old('staff_app_require_delivery_code', $hourly['staff_app_require_delivery_code']) ? 'checked' : '' }}>
                                <div><label for="staff_app_require_delivery_code">{{ __('طلب رمز العميل عند التسليم') }}</label><small>{{ __('يمنع إتمام التسليم حتى يُدخل السائق رمز الزيارة الصحيح.') }}</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- سقوف ساعات العمل --}}
                <div class="hr-card" id="sec-caps">
                    <div class="hr-head">
                        <div class="hr-head-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fa fa-scale-balanced"></i></div>
                        <div>
                            <h5>{{ __('سقوف ساعات العمل — الحد القانوني العام') }}</h5>
                            <p>{{ __('حد أعلى على كل عاملة؛ ملف العاملة يستطيع خفضه ولا يستطيع رفعه') }}</p>
                        </div>
                    </div>
                    <div class="hr-body">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <label class="form-label" for="max_hours_per_day">{{ __('ساعات / يوم') }}</label>
                                <input type="number" min="1" max="12" class="form-control @error('max_hours_per_day') is-invalid @enderror" id="max_hours_per_day" name="max_hours_per_day" value="{{ old('max_hours_per_day', $hourly['max_hours_per_day']) }}" required>
                                @error('max_hours_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label" for="max_hours_per_week">{{ __('ساعات / أسبوع') }}</label>
                                <input type="number" min="1" max="72" class="form-control @error('max_hours_per_week') is-invalid @enderror" id="max_hours_per_week" name="max_hours_per_week" value="{{ old('max_hours_per_week', $hourly['max_hours_per_week']) }}" required>
                                @error('max_hours_per_week')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label" for="max_hours_per_month">{{ __('ساعات / شهر') }}</label>
                                <input type="number" min="1" max="312" class="form-control @error('max_hours_per_month') is-invalid @enderror" id="max_hours_per_month" name="max_hours_per_month" value="{{ old('max_hours_per_month', $hourly['max_hours_per_month']) }}" required>
                                @error('max_hours_per_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label" for="min_rest_hours_between_visits">{{ __('أقل راحة بين زيارتين (ساعة)') }}</label>
                                <input type="number" min="0" max="12" class="form-control @error('min_rest_hours_between_visits') is-invalid @enderror" id="min_rest_hours_between_visits" name="min_rest_hours_between_visits" value="{{ old('min_rest_hours_between_visits', $hourly['min_rest_hours_between_visits']) }}" required>
                                @error('min_rest_hours_between_visits')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="hr-subbox">
                            <div class="hr-switch form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="ramadan_mode" name="ramadan_mode" value="1"
                                    {{ old('ramadan_mode', $hourly['ramadan_mode']) ? 'checked' : '' }}>
                                <div>
                                    <label for="ramadan_mode"><i class="fa fa-moon ms-1"></i> {{ __('تفعيل سقوف رمضان') }}</label>
                                    <small>{{ __('عند التفعيل تُستبدل السقوف اليومية والأسبوعية بالقيم أدناه.') }}</small>
                                </div>
                            </div>
                            <div class="hr-subbox-body">
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" for="ramadan_max_hours_per_day">{{ __('رمضان: ساعات / يوم') }}</label>
                                        <input type="number" min="1" max="12" class="form-control @error('ramadan_max_hours_per_day') is-invalid @enderror" id="ramadan_max_hours_per_day" name="ramadan_max_hours_per_day" value="{{ old('ramadan_max_hours_per_day', $hourly['ramadan_max_hours_per_day']) }}" required>
                                        @error('ramadan_max_hours_per_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <label class="form-label" for="ramadan_max_hours_per_week">{{ __('رمضان: ساعات / أسبوع') }}</label>
                                        <input type="number" min="1" max="72" class="form-control @error('ramadan_max_hours_per_week') is-invalid @enderror" id="ramadan_max_hours_per_week" name="ramadan_max_hours_per_week" value="{{ old('ramadan_max_hours_per_week', $hourly['ramadan_max_hours_per_week']) }}" required>
                                        @error('ramadan_max_hours_per_week')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hr-note hr-note-warn mt-3">
                            <i class="fa fa-circle-info mt-1"></i>
                            <div>{{ __('هذه السقوف حد أعلى عام على كل عاملة. ملف العاملة في التأجير بالساعة يستطيع خفضها لعاملة بعينها ولا يستطيع رفعها. الافتراضي مأخوذ من نظام العمل (م. 98) ويُراجع مع الشؤون القانونية.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="hr-savebar">
                    <span><i class="fa fa-circle-info ms-1"></i> {{ __('تُحفظ جميع الأقسام أعلاه معًا.') }}</span>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-floppy-disk ms-1"></i> {{ __('حفظ الإعدادات') }}
                    </button>
                </div>
            </form>

            {{-- حالة البناء --}}
            <div class="hr-card" id="sec-readiness">
                <div class="hr-head">
                    <div class="hr-head-icon" style="background:#dcfce7;color:#16a34a"><i class="fa fa-layer-group"></i></div>
                    <div>
                        <h5>{{ __('حالة بناء الموديل') }}</h5>
                        <p>{{ __('ما الذي سيظهر فعلًا بعد التفعيل — يُحدَّث تلقائيًا مع كل مرحلة تُنشر') }}</p>
                    </div>
                </div>
                <div class="hr-body">
                    <ul class="hr-ready">
                        @foreach ($readiness as $step)
                            <li class="{{ $step['done'] ? 'done' : 'todo' }}">
                                <i class="fa {{ $step['done'] ? 'fa-circle-check' : 'fa-circle' }}"></i>
                                <span>{{ $step['label'] }}</span>
                                <span class="ms-auto small">{{ $step['done'] ? __('مبني') : __('لم يُبنَ بعد') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($hourly['updated_at'])
                        <div class="form-text mt-3 d-lg-none">{{ __('آخر حفظ:') }} {{ $hourly['updated_at'] }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('js')
<script>
// حقل مفتاح Google لا يظهر إلا عند اختيار المزود المدفوع
document.querySelectorAll('input[name="map_provider"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('maps-key-box').hidden = radio.value !== 'google' || !radio.checked;
    });
});

// تمييز القسم الظاهر في القائمة الجانبية
(() => {
    const links = [...document.querySelectorAll('#hr-nav a')];
    if (!links.length || !('IntersectionObserver' in window)) return;
    const byId = Object.fromEntries(links.map(a => [a.getAttribute('href').slice(1), a]));
    const observer = new IntersectionObserver(entries => {
        entries.filter(e => e.isIntersecting).forEach(e => {
            links.forEach(a => a.classList.toggle('active', a === byId[e.target.id]));
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    Object.keys(byId).forEach(id => { const el = document.getElementById(id); if (el) observer.observe(el); });
})();
</script>
@endsection
