@extends('admin.settings.sections.layout')

@section('settings-title', 'كاش المواقع الخارجية')

@section('settings-description')
    تحكم في مدة احتفاظ الموقع الخارجي ببيانات الداشبورد. الكاش يخفف الضغط على السيرفر ويُبقي الموقع
    يعمل حتى لو تعطلت الداشبورد مؤقتًا. تُطبَّق التغييرات على الموقع خلال {{ $refreshSeconds }} ثانية تقريبًا.
@endsection

@section('settings-alerts')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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
    @php
        $canControl = @AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL)
            || @AdminGuardHasPerm(\App\PermKeys::WEBSITE_SETTINGS_CONTROL);

        $numberField = function (string $name, string $label, string $unit, string $hint) use ($cacheSettings, $ranges) {
            return compact('name', 'label', 'unit', 'hint') + [
                'value' => old($name, $cacheSettings[$name]),
                'min' => $ranges[$name][0],
                'max' => $ranges[$name][1],
            ];
        };

        $ttlFields = [
            $numberField('settings_ttl_minutes', 'إعدادات الموقع وشريط الأخبار', 'دقيقة', 'الشعار، أرقام التواصل، أكواد التتبع، الأخبار العاجلة.'),
            $numberField('home_ttl_minutes', 'محتوى الصفحة الرئيسية', 'دقيقة', 'السلايدر، الإحصائيات، الخدمات، الأقسام. الحد الأقصى 20 دقيقة لأن روابط الصور مؤقتة.'),
            $numberField('workers_ttl_minutes', 'أحدث السير الذاتية', 'دقيقة', 'قوائم العاملات المعروضة في الرئيسية. صفحة "كل العاملات" بفلاترها لا تُخزَّن. الحد الأقصى 20 دقيقة.'),
            $numberField('content_ttl_minutes', 'المحتوى الثابت', 'دقيقة', 'العروض، المدونة، الأسئلة الشائعة، من نحن، السياسات.'),
        ];

        $protectionFields = [
            $numberField('stale_ttl_hours', 'مدة الاحتفاظ بالنسخة الاحتياطية', 'ساعة', 'آخر بيانات ناجحة تُعرض عند تعطل الداشبورد.'),
            $numberField('failure_backoff_seconds', 'الانتظار قبل إعادة المحاولة بعد فشل', 'ثانية', 'يمنع أن ينتظر كل زائر مهلة الطلب كاملة أثناء التعطل. 0 = بلا انتظار.'),
            $numberField('request_timeout_seconds', 'مهلة الطلب الكاملة', 'ثانية', 'أقصى وقت ينتظره الموقع رد الداشبورد.'),
            $numberField('connect_timeout_seconds', 'مهلة فتح الاتصال', 'ثانية', 'يجب ألا تتجاوز مهلة الطلب.'),
        ];
    @endphp

    <form method="post" action="{{ route('settings.site-cache.update') }}" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <input type="hidden" name="enabled" value="0">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="enabled" id="enabled"
                            value="1" {{ old('enabled', $cacheSettings['enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="enabled">تفعيل الكاش في المواقع الخارجية</label>
                    </div>
                    <p class="text-muted mb-0">
                        عند الإيقاف يطلب الموقع كل البيانات من الداشبورد في كل زيارة (أبطأ وأثقل على السيرفر)،
                        لكن تظهر التعديلات فورًا.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <h6 class="fw-bold mb-0">مدة الكاش</h6>
            <small class="text-muted">كلما طالت المدة قلّ الضغط على السيرفر وتأخر ظهور التعديلات.</small>
        </div>

        @foreach ($ttlFields as $field)
            @include('admin.settings.partials.site-cache-number', ['field' => $field])
        @endforeach

        <div class="col-12 mt-4">
            <h6 class="fw-bold mb-0">الحماية عند تعطل الداشبورد</h6>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <input type="hidden" name="stale_fallback_enabled" value="0">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="stale_fallback_enabled"
                            id="stale_fallback_enabled" value="1"
                            {{ old('stale_fallback_enabled', $cacheSettings['stale_fallback_enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="stale_fallback_enabled">
                            عرض آخر نسخة ناجحة عند فشل الداشبورد
                        </label>
                    </div>
                    <p class="text-muted mb-0">
                        بدل أن يظهر الموقع فارغًا من العاملات، يعرض آخر بيانات وصلته. يعمل حتى لو كان الكاش موقوفًا.
                    </p>
                </div>
            </div>
        </div>

        @foreach ($protectionFields as $field)
            @include('admin.settings.partials.site-cache-number', ['field' => $field])
        @endforeach

        @if ($canControl)
            <div class="col-12">
                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
            </div>
        @endif
    </form>

    <hr class="my-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h6 class="fw-bold mb-1">مسح كاش المواقع الآن</h6>
            <p class="text-muted small mb-0">
                استخدمه بعد تعديل محتوى تريد ظهوره فورًا. تعيد المواقع جلب البيانات خلال {{ $refreshSeconds }} ثانية تقريبًا.
                @if ($cacheSettings['flushed_at'])
                    <br>آخر مسح: {{ $cacheSettings['flushed_at'] }}
                @endif
            </p>
        </div>
        @if ($canControl)
            <form method="post" action="{{ route('settings.site-cache.flush') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger">مسح الكاش</button>
            </form>
        @endif
    </div>
@endsection
