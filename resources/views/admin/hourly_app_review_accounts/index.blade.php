<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'حسابات اختبار التطبيقات — Super Admin'])
    <style>
        /* صفحة حسابات اختبار التطبيقات: تبني على نظام تصميم Super Admin الموحّد في partials.head */
        .ra-icon { width: 18px; height: 18px; flex: 0 0 auto; }
        .ra-stack { display: grid; gap: 18px; }
        .ra-head-title { display: flex; align-items: center; gap: 12px; }
        .ra-head-icon { width: 40px; height: 40px; flex: 0 0 auto; border-radius: var(--r-sm); display: grid; place-items: center;
            background: var(--accent-soft); color: var(--accent-dark); border: 1px solid var(--accent-line); }
        .ra-head-icon.is-navy { background: var(--navy-soft); color: var(--navy); border-color: #c9d8e8; }
        .ra-help { margin: 0; color: var(--muted); font-size: .79rem; line-height: 1.7; }
        .ra-error { margin: 0; color: var(--danger); font-size: .8rem; font-weight: 600; line-height: 1.6; }
        .field .is-invalid { border-color: var(--danger); }

        /* المفتاح الرئيسي */
        .ra-power { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 18px 20px;
            border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); transition: border-color .2s, background .2s; }
        .ra-power.is-on { border-color: var(--accent-line); background: linear-gradient(180deg, #f2fbf9 0%, var(--accent-soft) 100%); }
        .ra-power h3 { font-size: .98rem; margin: 0 0 3px; }
        .ra-power p { margin: 0; color: var(--muted); font-size: .84rem; line-height: 1.7; }
        .ra-power .switch { transform: scale(1.18); }
        .ra-state { font-weight: 700; font-size: .82rem; margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; }
        .ra-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .ra-power.is-on .ra-dot { background: var(--success); box-shadow: 0 0 0 4px rgba(6, 118, 71, .14); }

        .ra-notes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .ra-notes .alert { margin: 0; font-size: .84rem; }

        /* البحث في رأس لوحة الحسابات */
        .ra-search { display: flex; gap: 8px; align-items: center; flex: 1 1 380px; max-width: 520px; }
        .ra-search-input { position: relative; flex: 1; }
        .ra-search-input .ra-icon { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .ra-search-input input { padding-inline-start: 38px; }

        /* بطاقات التطبيقات */
        .ra-roles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .ra-role { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--r-md); background: #fff; overflow: hidden; transition: border-color .2s, box-shadow .2s; }
        .ra-role.is-on { border-color: var(--accent-line); box-shadow: 0 0 0 3px var(--ring); }
        .ra-role-head { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--border); background: var(--surface-soft); }
        .ra-role-head h3 { margin: 0; font-size: .95rem; }
        .ra-role-head .badge { margin-inline-start: auto; }
        .ra-role-body { display: grid; gap: 16px; padding: 16px; flex: 1; align-content: start; }
        .ra-role-foot { padding: 12px 16px; border-top: 1px solid var(--border); background: var(--surface-soft); }

        .badge.is-on { background: var(--success-soft); color: var(--success); border-color: var(--success-line); }
        .badge.is-off { background: var(--surface-alt); color: var(--muted); border-color: var(--border); }
        .badge.is-warn { background: var(--warning-soft); color: var(--warning); border-color: var(--warning-line); }

        /* ملخص الحساب المختار */
        .ra-account { display: grid; gap: 3px; padding: 12px 14px; border-radius: var(--r-sm); background: var(--navy-soft); border: 1px solid #d3e0ee; }
        .ra-account strong { font-size: .9rem; color: var(--text); overflow-wrap: anywhere; }
        .ra-account span { font-size: .8rem; color: var(--muted); }
        .ra-account.is-empty { background: var(--surface-soft); border-style: dashed; border-color: var(--border-strong); }
        .ra-ltr { direction: ltr; unicode-bidi: isolate; font-family: var(--mono); }

        .ra-code { direction: ltr; text-align: center; font-family: var(--mono); letter-spacing: .5em; font-size: 1.05rem !important; }
        .ra-saved { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: .78rem; color: var(--muted); }
        .ra-saved code { font-family: var(--mono); background: var(--surface-alt); border: 1px solid var(--border); border-radius: 6px; padding: 1px 8px; color: var(--text-soft); direction: ltr; letter-spacing: .15em; }
        .ra-add { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; color: var(--accent-dark); text-decoration: none; }
        .ra-add:hover { text-decoration: underline; }

        /* شريط الحفظ */
        .ra-savebar { position: sticky; bottom: 14px; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
            margin-top: 18px; padding: 12px 16px; border-radius: var(--r-md); background: rgba(255, 255, 255, .94); backdrop-filter: blur(8px);
            border: 1px solid var(--border); box-shadow: var(--shadow-md); }
        .ra-savebar span { display: inline-flex; align-items: center; gap: 6px; font-size: .83rem; color: var(--muted); }

        @media (max-width: 1080px) {
            .ra-roles { grid-template-columns: 1fr; }
        }
        @media (max-width: 760px) {
            .ra-notes { grid-template-columns: 1fr; }
            .ra-search { max-width: none; flex-wrap: wrap; }
            .ra-search-input { flex-basis: 100%; }
            .ra-power { align-items: flex-start; }
            .ra-savebar .btn { width: 100%; }
        }
    </style>
</head>
<body>
@php
    $review = \App\Support\HourlyAppReviewAccounts::class;
    $masterEnabled = (bool) old('enabled', $settings['enabled']);
    $search = trim((string) request('search', ''));
    $icons = [
        'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2.2"/><path d="M11 18.5h2"/>',
        'power' => '<path d="M12 3v8"/><path d="M6.4 6.9a8 8 0 1 0 11.2 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14a6.5 6.5 0 0 1 3.5 6"/>',
        'customer' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'worker' => '<path d="M12 3l1.8 4.6L18.5 9l-4.7 1.5L12 15l-1.8-4.5L5.5 9l4.7-1.4z"/><path d="M18.5 15l.8 2 2 .8-2 .7-.8 2-.7-2-2-.7 2-.8z"/>',
        'driver' => '<path d="M5 16V11l2-5h10l2 5v5"/><path d="M3.5 16h17v3h-17z"/><circle cx="7.5" cy="13" r="1"/><circle cx="16.5" cy="13" r="1"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M18 5l2 2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
        'save' => '<path d="M5 4h11l3 3v13H5z"/><path d="M8 4v5h7V4"/><path d="M8 20v-6h8v6"/>',
    ];
    $icon = fn (string $name, string $class = 'ra-icon') => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$name] . '</svg>';

    $roles = [];
    foreach ($review::ROLES as $role => $label) {
        $entry = $settings['accounts'][$role] ?? [];
        $selectedId = (string) old("accounts.$role.account_id", $entry['account_id'] ?? '');
        $selected = $selectedId !== '' ? $options[$role]->firstWhere('id', (int) $selectedId) : null;
        $available = $selected && $review::available($role, $selected);
        $enabled = (bool) old("accounts.$role.enabled", $entry['enabled'] ?? false);
        $roles[$role] = [
            'label' => $label,
            'entry' => $entry,
            'selectedId' => $selectedId,
            'selected' => $selected,
            'phone' => $selected ? $review::phone($role, $selected) : null,
            'enabled' => $enabled,
            'active' => $masterEnabled && $enabled && $available,
            'status' => match (true) {
                !$selected => ['is-off', 'لم يُختر حساب'],
                !$available => ['is-warn', 'غير متاح للجوال'],
                $enabled && $masterEnabled => ['is-on', 'مفعّل'],
                default => ['is-off', 'متوقف'],
            },
            'createUrl' => match ($role) {
                'customer' => route('admin.hourly-customers.create'),
                'driver' => route('admin.hourly-drivers.create'),
                'worker' => route('maids.rental.create', ['rental_mode' => 'hourly']),
            },
        ];
    }
    $activeCount = collect($roles)->where('active', true)->count();
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">حسابات اختبار التطبيقات</h1>
                <p class="brand-text">حسابات مراجعة متاجر التطبيقات لتطبيقات التأجير بالساعة: العميل، العاملة، والسائق.</p>
            </div>
        </div>
        <div class="topbar-actions">
            <a class="btn" href="{{ route('dev-superadmin.modules') }}">لوحة Super Admin</a>
            <a class="btn" href="{{ route('dev-superadmin.hourly-rental-settings.index') }}">إعدادات الساعات</a>
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    @if (session('success') || session('error') || $errors->any())
        <div class="alerts">
            @if (session('success'))<div class="alert">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h2>{!! $icon('phone') !!} حسابات الاختبار ورموز التحقق الثابتة</h2>
            <p>اختر حسابًا لكل تطبيق وحدد رمز تحقق ثابتًا من خمسة أرقام. تظل شروط الدخول وحالة الحساب وصلاحية وثائق السائق سارية.</p>
        </div>
        <div class="page-heading-meta">
            <span class="heading-chip {{ $settings['enabled'] ? 'is-good' : 'is-bad' }}">الحالة: <b>{{ $settings['enabled'] ? 'مفعّلة' : 'متوقفة' }}</b></span>
            <span class="heading-chip">الحسابات العاملة: <b>{{ $activeCount }} / {{ count($roles) }}</b></span>
        </div>
    </div>

    {{-- نموذج البحث منفصل عن نموذج الحفظ؛ عناصره داخل لوحة الحسابات مرتبطة به عبر خاصية form --}}
    <form method="GET" action="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}" id="reviewSearch"></form>

    <form method="POST" action="{{ route('dev-superadmin.hourly-app-review-accounts.update') }}" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="ra-stack">
            <section class="panel">
                <div class="panel-head">
                    <div class="ra-head-title">
                        <span class="ra-head-icon">{!! $icon('power') !!}</span>
                        <div>
                            <h2>التشغيل</h2>
                            <p class="panel-subtitle">المفتاح الرئيسي يتحكم في كل الحسابات أدناه مهما كانت إعداداتها الفردية.</p>
                        </div>
                    </div>
                </div>
                <div class="panel-body ra-stack">
                    <div class="ra-power {{ $masterEnabled ? 'is-on' : '' }}" id="powerCard">
                        <div>
                            <h3>تفعيل حسابات الاختبار</h3>
                            <p>عند الإيقاف تعود كل الحسابات إلى التحقق المعتاد برسالة SMS.</p>
                            <span class="ra-state"><span class="ra-dot"></span><span id="powerState">{{ $masterEnabled ? 'الرموز الثابتة تعمل' : 'الرموز الثابتة متوقفة' }}</span></span>
                        </div>
                        <label class="switch" title="تفعيل أو إيقاف حسابات الاختبار">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" name="enabled" value="1" id="review-enabled" @checked($masterEnabled)>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="ra-notes">
                        <div class="alert alert-info">رقم العميل يكون من ٩ أرقام ويبدأ بـ 5، مع مفتاح الدولة 966، وفق صيغة تسجيل الدخول الحالية.</div>
                        <div class="alert alert-warning">الإيقاف يعطّل الرمز الثابت فقط؛ لا يحذف الحساب ولا يسجّل خروج الجلسات القائمة. هذه الحسابات تستخدم بيانات النظام وعملياته الفعلية.</div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="ra-head-title">
                        <span class="ra-head-icon is-navy">{!! $icon('users') !!}</span>
                        <div>
                            <h2>حسابات التطبيقات</h2>
                            <p class="panel-subtitle">تظهر أحدث ٥٠ نتيجة لكل نوع مع الحساب المختار دائمًا. احفظ التغييرات قبل البحث.</p>
                        </div>
                    </div>
                    <div class="ra-search">
                        <div class="field ra-search-input">
                            {!! $icon('search') !!}
                            <input form="reviewSearch" name="search" value="{{ $search }}" placeholder="الاسم أو رقم الدخول أو رقم الحساب" aria-label="البحث عن حساب">
                        </div>
                        <button form="reviewSearch" class="btn" type="submit">بحث</button>
                        @if ($search !== '')
                            <a class="btn" href="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}">مسح</a>
                        @endif
                    </div>
                </div>
                <div class="panel-body">
                    <div class="ra-roles">
                        @foreach ($roles as $role => $r)
                            <article class="ra-role {{ $r['active'] ? 'is-on' : '' }}">
                                <div class="ra-role-head">
                                    <span class="ra-head-icon">{!! $icon($role) !!}</span>
                                    <h3>تطبيق {{ $r['label'] }}</h3>
                                    <span class="badge {{ $r['status'][0] }}">{{ $r['status'][1] }}</span>
                                </div>

                                <div class="ra-role-body">
                                    @if ($r['selected'])
                                        <div class="ra-account">
                                            <strong>{{ $r['selected']->name }}</strong>
                                            <span>رقم الحساب <span class="ra-ltr">#{{ $r['selected']->id }}</span> — الدخول: <span class="ra-ltr">{{ $r['phone'] ?: 'بلا رقم دخول' }}</span></span>
                                        </div>
                                    @else
                                        <div class="ra-account is-empty">
                                            <strong>لم يُختر حساب بعد</strong>
                                            <span>اختر حسابًا من القائمة أدناه.</span>
                                        </div>
                                    @endif

                                    <div class="field">
                                        <label for="review-{{ $role }}">الحساب</label>
                                        <select id="review-{{ $role }}" name="accounts[{{ $role }}][account_id]" class="@error("accounts.$role.account_id") is-invalid @enderror">
                                            <option value="">— بدون حساب —</option>
                                            @foreach ($options[$role] as $account)
                                                @php($phone = $review::phone($role, $account))
                                                <option value="{{ $account->id }}" @selected($r['selectedId'] === (string) $account->id)>
                                                    #{{ $account->id }} — {{ $account->name }} — {{ $phone ?: 'بلا رقم دخول' }}{{ $review::available($role, $account) ? '' : ' (غير متاح للجوال)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("accounts.$role.account_id")<p class="ra-error">{{ $message }}</p>@enderror
                                        <a class="ra-add" href="{{ $r['createUrl'] }}" target="_blank" rel="noopener">{!! $icon('plus') !!} إضافة {{ $r['label'] }} جديد</a>
                                        <p class="ra-help">بعد الإضافة حدّث الصفحة واختر الحساب. فعّل دخول الجوال وأدخل رقمًا سعوديًا.</p>
                                    </div>

                                    <div class="field">
                                        <label for="review-code-{{ $role }}">رمز التحقق الثابت</label>
                                        <input id="review-code-{{ $role }}" class="ra-code @error("accounts.$role.code") is-invalid @enderror" name="accounts[{{ $role }}][code]" inputmode="numeric" pattern="[0-9]{5}" minlength="5" maxlength="5" autocomplete="off" placeholder="•••••">
                                        @error("accounts.$role.code")<p class="ra-error">{{ $message }}</p>@enderror
                                        @if (!empty($r['entry']['code']))
                                            <div class="ra-saved">{!! $icon('key') !!} المحفوظ: <code>{{ $r['entry']['code'] }}</code> — اترك الحقل فارغًا للإبقاء عليه.</div>
                                        @else
                                            <div class="ra-saved">{!! $icon('info') !!} لا يوجد رمز محفوظ بعد.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="ra-role-foot">
                                    <input type="hidden" name="accounts[{{ $role }}][enabled]" value="0">
                                    <label class="switch-row" for="review-enabled-{{ $role }}">
                                        <span>تفعيل الرمز الثابت لهذا الحساب</span>
                                        <span class="switch">
                                            <input type="checkbox" id="review-enabled-{{ $role }}" name="accounts[{{ $role }}][enabled]" value="1" @checked($r['enabled'])>
                                            <span class="slider"></span>
                                        </span>
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <div class="ra-savebar">
            <span>{!! $icon('info') !!} تُطبَّق التغييرات فور الحفظ على تطبيقات الجوال.</span>
            <button class="btn btn-primary" type="submit">{!! $icon('save') !!} حفظ إعدادات الاختبار</button>
        </div>
    </form>
</main>

<script>
    (function () {
        var input = document.getElementById('review-enabled');
        var card = document.getElementById('powerCard');
        var state = document.getElementById('powerState');
        input.addEventListener('change', function () {
            card.classList.toggle('is-on', input.checked);
            state.textContent = input.checked ? 'الرموز الثابتة تعمل' : 'الرموز الثابتة متوقفة';
        });
    })();
</script>
</body>
</html>
