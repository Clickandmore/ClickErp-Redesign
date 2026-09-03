@extends('admin.layout.indexs.index')

@section('page-title', 'إعدادات إشعارات Firebase')

@section('style')
    <style>
        .fb-page {
            --fb-ink: #0f172a;
            --fb-muted: #64748b;
            --fb-line: #e2e8f0;
            --fb-soft: #f8fafc;
        }

        .fb-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            margin-bottom: 1.25rem;
            background: #fff;
        }

        .fb-card > .fb-head {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #eef2f7;
            padding: 1.1rem 1.35rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .fb-head .fb-head-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 1.05rem;
        }

        .fb-head h5 {
            margin: 0;
            font-weight: 700;
            color: var(--fb-ink);
            font-size: 1.03rem;
        }

        .fb-head p {
            margin: 0.15rem 0 0;
            font-size: 0.82rem;
            color: var(--fb-muted);
        }

        .fb-body { padding: 1.35rem; }
        .fb-foot {
            padding: 1rem 1.35rem;
            background: var(--fb-soft);
            border-top: 1px solid #eef2f7;
        }

        /* ---- شريط الحالة العلوي ---- */
        .fb-hero {
            border-radius: 18px;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            margin-bottom: 1.25rem;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.18);
        }

        .fb-hero h4 {
            margin: 0;
            font-weight: 700;
            color: #fff;
            font-size: 1.2rem;
        }

        .fb-hero p {
            margin: 0.3rem 0 0;
            color: #94a3b8;
            font-size: 0.86rem;
        }

        .fb-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .fb-chip-on    { background: rgba(34, 197, 94, 0.15);  color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
        .fb-chip-off   { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border-color: rgba(148, 163, 184, 0.3); }
        .fb-chip-warn  { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.35); }
        .fb-chip-error { background: rgba(239, 68, 68, 0.15);  color: #f87171; border-color: rgba(239, 68, 68, 0.35); }

        /* ---- بطاقات الأرقام ---- */
        .fb-stat {
            border: 1px solid var(--fb-line);
            border-radius: 14px;
            padding: 0.95rem 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
            height: 100%;
        }

        .fb-stat .fb-stat-label {
            font-size: 0.78rem;
            color: var(--fb-muted);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .fb-stat .fb-stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--fb-ink);
            line-height: 1.3;
        }

        /* ---- صندوق بيانات حساب الخدمة ---- */
        .fb-mono {
            background: var(--fb-soft);
            border: 1px solid var(--fb-line);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font-size: 0.8rem;
            color: #334155;
        }

        .fb-mono .fb-row {
            display: flex;
            gap: 0.6rem;
            padding: 0.2rem 0;
            align-items: baseline;
        }

        .fb-mono .fb-row span:first-child {
            color: var(--fb-muted);
            flex: 0 0 96px;
            font-weight: 600;
        }

        .fb-mono .fb-row span:last-child {
            word-break: break-all;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        /* ---- منطقة الرفع ---- */
        .fb-drop {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 1.35rem;
            background: var(--fb-soft);
            transition: border-color .15s, background .15s;
        }

        .fb-drop:hover { border-color: #94a3b8; background: #f1f5f9; }

        .fb-drop.is-invalid-drop { border-color: #ef4444; background: #fef2f2; }

        /* ---- العناوين الفرعية ---- */
        .fb-legend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--fb-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
            margin: 0 0 0.9rem;
        }

        .fb-legend::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--fb-line);
        }

        /* ---- المفاتيح ---- */
        .fb-switch {
            border: 1px solid var(--fb-line);
            border-radius: 14px;
            padding: 0.9rem 1rem;
            background: #fff;
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            margin-bottom: 0.85rem;
        }

        .fb-switch .form-check-input {
            width: 2.6rem;
            height: 1.35rem;
            margin: 0.1rem 0 0;
            flex: 0 0 auto;
            cursor: pointer;
        }

        .fb-switch label {
            font-weight: 600;
            color: var(--fb-ink);
            margin: 0;
            cursor: pointer;
        }

        .fb-switch small {
            display: block;
            color: var(--fb-muted);
            font-size: 0.79rem;
            margin-top: 0.15rem;
            line-height: 1.6;
        }

        .fb-page .form-label {
            font-size: 0.83rem;
            font-weight: 600;
            color: #334155;
        }

        .fb-page .form-text {
            font-size: 0.76rem;
            color: var(--fb-muted);
        }

        .fb-page .form-control,
        .fb-page .form-select {
            border-radius: 10px;
            border-color: #dbe2ea;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        /* ---- اختيار هدف الاختبار ---- */
        .fb-seg { display: flex; gap: 0.5rem; }

        .fb-seg label {
            flex: 1;
            border: 1px solid var(--fb-line);
            border-radius: 12px;
            padding: 0.6rem 0.75rem;
            text-align: center;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            background: #fff;
            margin: 0;
            transition: all .15s;
        }

        .fb-seg input { display: none; }
        .fb-seg input:checked + label {
            border-color: #4f46e5;
            background: #eef2ff;
            color: #4338ca;
        }

        .fb-result pre {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            font-size: 0.76rem;
            max-height: 260px;
            overflow: auto;
            margin: 0.75rem 0 0;
        }

        .fb-note {
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 0.82rem;
            border: 1px solid;
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
        }

        .fb-note-info { background: #f0f9ff; border-color: #bae6fd; color: #075985; }
        .fb-note-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .fb-note-mute { background: var(--fb-soft); border-color: var(--fb-line); color: #475569; }

        @media (max-width: 575.98px) {
            .fb-hero { padding: 1.1rem; }
            .fb-body { padding: 1.05rem; }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid fb-page">

        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3">
                <i class="fa fa-circle-check ms-1"></i> {{ session('success') }}
            </div>
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

        <div class="row justify-content-center">
            <div class="col-xxl-9 col-xl-10">

                {{-- ================= الترويسة ================= --}}
                <div class="fb-hero">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4><i class="fa fa-bell ms-2"></i> إشعارات Firebase</h4>
                            <p>التحكم في الإشعارات المرسلة لتطبيق الجوال دون تعديل ملف .env</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @if ($firebase['enabled'])
                                <span class="fb-chip fb-chip-on"><i class="fa fa-circle-check"></i> مفعّلة</span>
                            @else
                                <span class="fb-chip fb-chip-off"><i class="fa fa-circle-pause"></i> متوقفة</span>
                            @endif

                            @if ($firebaseCredentials['valid'])
                                <span class="fb-chip fb-chip-on"><i class="fa fa-key"></i> حساب الخدمة صالح</span>
                            @elseif ($firebaseCredentials['exists'])
                                <span class="fb-chip fb-chip-error"><i class="fa fa-triangle-exclamation"></i> ملف غير صالح</span>
                            @else
                                <span class="fb-chip fb-chip-warn"><i class="fa fa-upload"></i> لم يُرفع الملف</span>
                            @endif

                            <span class="fb-chip fb-chip-off"><i class="fa fa-mobile-screen"></i> {{ $tokensCount }} جهاز</span>
                        </div>
                    </div>
                </div>

                @if (session('test_result'))
                    @php($test = session('test_result'))
                    <div class="fb-card fb-result">
                        <div class="fb-head">
                            <div class="fb-head-icon" style="background:{{ $test['ok'] ? '#dcfce7' : '#fef3c7' }};color:{{ $test['ok'] ? '#16a34a' : '#d97706' }}">
                                <i class="fa {{ $test['ok'] ? 'fa-paper-plane' : 'fa-circle-exclamation' }}"></i>
                            </div>
                            <div>
                                <h5>نتيجة الإرسال التجريبي</h5>
                                <p>{{ $test['summary'] }}</p>
                            </div>
                        </div>
                        @if (!empty($test['details']))
                            <div class="fb-body pt-0">
                                <pre dir="ltr">{{ json_encode($test['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ================= الحالة ================= --}}
                <div class="fb-card">
                    <div class="fb-head">
                        <div class="fb-head-icon"><i class="fa fa-gauge-high"></i></div>
                        <div>
                            <h5>نظرة عامة</h5>
                            <p>جاهزية الإرسال والأجهزة المسجّلة</p>
                        </div>
                    </div>

                    <div class="fb-body">
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-lg-3">
                                <div class="fb-stat">
                                    <div class="fb-stat-label"><i class="fa fa-mobile-screen"></i> إجمالي الأجهزة</div>
                                    <div class="fb-stat-value">{{ $tokensCount }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="fb-stat">
                                    <div class="fb-stat-label"><i class="fa-brands fa-android"></i> أندرويد</div>
                                    <div class="fb-stat-value">{{ $androidTokens }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="fb-stat">
                                    <div class="fb-stat-label"><i class="fa-brands fa-apple"></i> iOS</div>
                                    <div class="fb-stat-value">{{ $iosTokens }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="fb-stat">
                                    <div class="fb-stat-label"><i class="fa fa-users"></i> عملاء لديهم أجهزة</div>
                                    <div class="fb-stat-value">{{ $usersWithTokens }}</div>
                                </div>
                            </div>
                        </div>

                        @if ($firebaseCredentials['valid'])
                            <div class="fb-mono" dir="ltr">
                                <div class="fb-row"><span>project_id</span><span>{{ $firebaseCredentials['project_id'] }}</span></div>
                                <div class="fb-row"><span>client_email</span><span>{{ $firebaseCredentials['client_email'] }}</span></div>
                                <div class="fb-row"><span>updated</span><span>{{ $firebaseCredentials['updated_at'] }}</span></div>
                            </div>
                        @elseif ($firebaseCredentials['error'])
                            <div class="fb-note fb-note-warn">
                                <i class="fa fa-triangle-exclamation mt-1"></i>
                                <div>{{ $firebaseCredentials['error'] }}</div>
                            </div>
                        @endif

                        @if (!$firebase['enabled'])
                            <div class="fb-note fb-note-mute mt-3">
                                <i class="fa fa-circle-info mt-1"></i>
                                <div>الإشعارات متوقفة حالياً: كل محاولات الإرسال تُتجاهل بهدوء دون رمي أخطاء.</div>
                            </div>
                        @elseif (!$firebaseCredentials['valid'])
                            <div class="fb-note fb-note-warn mt-3">
                                <i class="fa fa-triangle-exclamation mt-1"></i>
                                <div>الإشعارات مفعّلة لكن لا يوجد ملف حساب خدمة صالح، فلن تصل أي رسالة. ارفع الملف من القسم التالي.</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ================= ملف حساب الخدمة ================= --}}
                <div class="fb-card">
                    <div class="fb-head">
                        <div class="fb-head-icon" style="background:#fef3c7;color:#d97706"><i class="fa fa-key"></i></div>
                        <div>
                            <h5>ملف حساب الخدمة</h5>
                            <p>Firebase Console ← Project Settings ← Service accounts ← Generate new private key</p>
                        </div>
                    </div>

                    <div class="fb-body">
                        <form method="POST" action="{{ route('dash.firebase-settings.credentials.upload') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="fb-drop @error('credentials') is-invalid-drop @enderror">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label" for="credentials">
                                            <i class="fa fa-file-code ms-1"></i> اختر ملف JSON
                                        </label>
                                        <input type="file" accept=".json,application/json"
                                            class="form-control @error('credentials') is-invalid @enderror"
                                            id="credentials" name="credentials" required>
                                        @error('credentials')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 text-md-start">
                                        <button type="submit" class="btn btn-primary w-100 rounded-3">
                                            <i class="fa fa-upload ms-1"></i> رفع الملف
                                        </button>
                                    </div>
                                </div>
                                <div class="form-text mt-2" dir="ltr">{{ $firebaseCredentials['path'] }}</div>
                            </div>
                        </form>

                        <div class="fb-note fb-note-info mt-3">
                            <i class="fa fa-shield-halved mt-1"></i>
                            <div>
                                يُتحقق من الملف قبل حفظه: صيغة JSON، ووجود <code>project_id</code> و<code>client_email</code>
                                و<code>private_key</code>، وأن المفتاح الخاص صالح. الملف يحمل مفتاحاً سرياً ولا يُرفع إلى git.
                            </div>
                        </div>

                        @if ($firebaseCredentials['exists'])
                            <form method="POST" action="{{ route('dash.firebase-settings.credentials.delete') }}"
                                class="mt-3"
                                onsubmit="return confirm('سيتوقف إرسال كل الإشعارات حتى يُرفع ملف جديد. متأكد؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-3">
                                    <i class="fa fa-trash ms-1"></i> حذف الملف الحالي
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- ================= الإعدادات ================= --}}
                <form method="POST" action="{{ route('dash.firebase-settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="fb-card">
                        <div class="fb-head">
                            <div class="fb-head-icon" style="background:#e0f2fe;color:#0284c7"><i class="fa fa-sliders"></i></div>
                            <div>
                                <h5>الإعدادات</h5>
                                <p>تعلو قيم ملف .env — وتُطبَّق فوراً بعد الحفظ</p>
                            </div>
                        </div>

                        <div class="fb-body">
                            <div class="fb-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="enabled"
                                    name="enabled" value="1"
                                    {{ old('enabled', $firebase['enabled']) ? 'checked' : '' }}>
                                <div>
                                    <label for="enabled">تفعيل إشعارات Firebase</label>
                                    <small>عند الإلغاء تُتجاهل كل عمليات الإرسال دون رمي أخطاء — مناسب لبيئة التطوير.</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="project_id">معرّف المشروع (project_id)</label>
                                <input type="text" dir="ltr"
                                    class="form-control @error('project_id') is-invalid @enderror"
                                    id="project_id" name="project_id"
                                    value="{{ old('project_id', $firebase['project_id']) }}"
                                    placeholder="{{ $firebaseCredentials['project_id'] ?: 'يُقرأ تلقائياً من ملف حساب الخدمة' }}">
                                @error('project_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">اتركه فارغاً ليُقرأ من ملف حساب الخدمة.</div>
                            </div>

                            <div class="fb-legend"><i class="fa fa-bolt"></i> الأداء</div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label" for="timeout">مهلة الاتصال</label>
                                    <div class="input-group">
                                        <input type="number" min="3" max="120"
                                            class="form-control @error('timeout') is-invalid @enderror"
                                            id="timeout" name="timeout" value="{{ old('timeout', $firebase['timeout']) }}" required>
                                        <span class="input-group-text rounded-end-3">ثانية</span>
                                        @error('timeout')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="concurrency">طلبات متوازية</label>
                                    <input type="number" min="1" max="100"
                                        class="form-control @error('concurrency') is-invalid @enderror"
                                        id="concurrency" name="concurrency" value="{{ old('concurrency', $firebase['concurrency']) }}" required>
                                    @error('concurrency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">FCM v1 ترسل رسالة لكل جهاز على حدة، وهذا حجم الدفعة.</div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="token_cache_ttl">تخزين توكن المصادقة</label>
                                    <div class="input-group">
                                        <input type="number" min="60" max="3600"
                                            class="form-control @error('token_cache_ttl') is-invalid @enderror"
                                            id="token_cache_ttl" name="token_cache_ttl" value="{{ old('token_cache_ttl', $firebase['token_cache_ttl']) }}" required>
                                        <span class="input-group-text rounded-end-3">ثانية</span>
                                        @error('token_cache_ttl')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">جوجل تصدر توكناً صالحاً لساعة؛ 3300 تترك هامش أمان.</div>
                                </div>
                            </div>

                            <div class="fb-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="prune_invalid_tokens"
                                    name="prune_invalid_tokens" value="1"
                                    {{ old('prune_invalid_tokens', $firebase['prune_invalid_tokens']) ? 'checked' : '' }}>
                                <div>
                                    <label for="prune_invalid_tokens">حذف توكنات الأجهزة غير الصالحة تلقائياً</label>
                                    <small>عندما تُبلغ FCM أن الجهاز حذف التطبيق يُحذف توكنه، بدل أن تتراكم توكنات ميتة تُبطئ كل إرسال.</small>
                                </div>
                            </div>

                            <div class="fb-legend mt-4"><i class="fa fa-mobile-screen-button"></i> المنصات</div>

                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label" for="android_priority">أولوية أندرويد</label>
                                    <select class="form-select @error('android_priority') is-invalid @enderror"
                                        id="android_priority" name="android_priority" required>
                                        <option value="high" {{ old('android_priority', $firebase['android_priority']) === 'high' ? 'selected' : '' }}>high — توصيل فوري</option>
                                        <option value="normal" {{ old('android_priority', $firebase['android_priority']) === 'normal' ? 'selected' : '' }}>normal — توفير البطارية</option>
                                    </select>
                                    @error('android_priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label" for="android_channel_id">قناة أندرويد</label>
                                    <input type="text" dir="ltr"
                                        class="form-control @error('android_channel_id') is-invalid @enderror"
                                        id="android_channel_id" name="android_channel_id"
                                        value="{{ old('android_channel_id', $firebase['android_channel_id']) }}" required>
                                    @error('android_channel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">يجب أن تطابق القناة المُعرّفة داخل التطبيق.</div>
                                </div>

                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label" for="android_sound">صوت أندرويد</label>
                                    <input type="text" dir="ltr"
                                        class="form-control @error('android_sound') is-invalid @enderror"
                                        id="android_sound" name="android_sound"
                                        value="{{ old('android_sound', $firebase['android_sound']) }}" required>
                                    @error('android_sound')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label" for="apns_sound">صوت iOS</label>
                                    <input type="text" dir="ltr"
                                        class="form-control @error('apns_sound') is-invalid @enderror"
                                        id="apns_sound" name="apns_sound"
                                        value="{{ old('apns_sound', $firebase['apns_sound']) }}" required>
                                    @error('apns_sound')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="fb-foot text-start">
                            <button type="submit" class="btn btn-primary rounded-3 px-4">
                                <i class="fa fa-floppy-disk ms-1"></i> حفظ الإعدادات
                            </button>
                        </div>
                    </div>
                </form>

                {{-- ================= إشعار تجريبي ================= --}}
                <div class="fb-card">
                    <div class="fb-head">
                        <div class="fb-head-icon" style="background:#dcfce7;color:#16a34a"><i class="fa fa-paper-plane"></i></div>
                        <div>
                            <h5>إرسال إشعار تجريبي</h5>
                            <p>للتحقق من صحة الإعدادات قبل الاعتماد عليها</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('dash.firebase-settings.test') }}">
                        @csrf
                        <div class="fb-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">الهدف</label>
                                    <div class="fb-seg">
                                        <input type="radio" name="target_type" value="user" id="tt_user"
                                            {{ old('target_type', 'user') === 'user' ? 'checked' : '' }}>
                                        <label for="tt_user"><i class="fa fa-user ms-1"></i> عميل</label>

                                        <input type="radio" name="target_type" value="token" id="tt_token"
                                            {{ old('target_type') === 'token' ? 'checked' : '' }}>
                                        <label for="tt_token"><i class="fa fa-mobile-screen ms-1"></i> جهاز</label>
                                    </div>
                                </div>

                                <div class="col-md-8" data-target-field="user">
                                    <label class="form-label" for="user_id">معرّف العميل</label>
                                    <input type="number" class="form-control @error('user_id') is-invalid @enderror"
                                        id="user_id" name="user_id" value="{{ old('user_id') }}"
                                        placeholder="يُرسل لكل أجهزة هذا العميل">
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8 d-none" data-target-field="token">
                                    <label class="form-label" for="device_token">توكن الجهاز</label>
                                    <input type="text" dir="ltr" class="form-control @error('device_token') is-invalid @enderror"
                                        id="device_token" name="device_token" value="{{ old('device_token') }}"
                                        placeholder="FCM device token">
                                    @error('device_token')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="title">العنوان</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="{{ old('title', 'اختبار') }}" required>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label" for="body">النص</label>
                                    <input type="text" class="form-control" id="body" name="body"
                                        value="{{ old('body', 'رسالة تجريبية من لوحة التحكم') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="fb-foot d-flex flex-wrap justify-content-between align-items-center gap-2">
                            @if ($firebaseCredentials['valid'])
                                <span class="form-text mb-0">سيصل الإشعار خلال ثوانٍ إن كان الجهاز متصلاً.</span>
                            @else
                                <span class="form-text mb-0 text-danger">ارفع ملف حساب خدمة صالح لتفعيل الإرسال.</span>
                            @endif
                            <button type="submit" class="btn btn-outline-primary rounded-3 px-4"
                                {{ $firebaseCredentials['valid'] ? '' : 'disabled' }}>
                                <i class="fa fa-paper-plane ms-1"></i> إرسال تجريبي
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            var radios = document.querySelectorAll('input[name="target_type"]');
            if (!radios.length) return;

            function sync() {
                var value = document.querySelector('input[name="target_type"]:checked').value;
                document.querySelectorAll('[data-target-field]').forEach(function (el) {
                    el.classList.toggle('d-none', el.dataset.targetField !== value);
                });
            }

            radios.forEach(function (r) { r.addEventListener('change', sync); });
            sync();
        })();
    </script>
@endpush
