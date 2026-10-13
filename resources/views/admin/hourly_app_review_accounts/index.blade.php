@extends('admin.hourly_rental_settings.layout')
@section('page-title', 'حسابات اختبار التطبيقات')

@section('style')
    <style>
        .ra-page { --ra-ink: #0f172a; --ra-muted: #64748b; --ra-line: #e2e8f0; --ra-soft: #f8fafc; --ra-brand: #0e7490; }
        .ra-page .form-label { font-weight: 600; color: #334155; font-size: 0.86rem; margin-bottom: 0.35rem; }
        .ra-page .form-control, .ra-page .form-select { border-radius: 10px; border-color: #dbe3ec; min-height: 42px; }
        .ra-page .form-control:focus, .ra-page .form-select:focus { border-color: #67e8f9; box-shadow: 0 0 0 0.2rem rgba(14, 116, 144, 0.12); }
        .ra-page .form-text { font-size: 0.78rem; color: var(--ra-muted); line-height: 1.6; }
        .ra-page input[dir="ltr"] { font-family: ui-monospace, Menlo, Consolas, monospace; text-align: left; letter-spacing: 0.2em; }

        /* الترويسة */
        .ra-hero { border-radius: 20px; padding: 1.4rem 1.6rem; background: linear-gradient(135deg, #0f172a 0%, #164e63 100%); color: #e2e8f0; margin-bottom: 1.5rem; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.18); }
        .ra-hero h4 { margin: 0; font-weight: 700; color: #fff; font-size: 1.25rem; }
        .ra-hero p { margin: 0.35rem 0 0; color: #94a3b8; font-size: 0.86rem; line-height: 1.7; max-width: 760px; }
        .ra-chip { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; border-radius: 999px; font-size: 0.82rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
        .ra-chip-on { background: rgba(34, 197, 94, 0.15); color: #4ade80; border-color: rgba(34, 197, 94, 0.35); }
        .ra-chip-off { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border-color: rgba(148, 163, 184, 0.3); }

        /* البطاقات */
        .ra-card { border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06); background: #fff; margin-bottom: 1.25rem; overflow: hidden; }
        .ra-head { border-bottom: 1px solid #eef2f7; padding: 1.1rem 1.35rem; display: flex; align-items: center; gap: 0.85rem; }
        .ra-head-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; display: grid; place-items: center; background: #ecfeff; color: var(--ra-brand); font-size: 1.05rem; }
        .ra-head h5 { margin: 0; font-weight: 700; color: var(--ra-ink); font-size: 1.03rem; }
        .ra-head p { margin: 0.15rem 0 0; font-size: 0.82rem; color: var(--ra-muted); }
        .ra-head .ra-status { margin-inline-start: auto; }
        .ra-body { padding: 1.35rem; }

        /* الحالة */
        .ra-status { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; border-radius: 999px; padding: 0.25rem 0.65rem; white-space: nowrap; }
        .ra-status-on { background: #dcfce7; color: #166534; }
        .ra-status-off { background: #f1f5f9; color: #64748b; }
        .ra-status-warn { background: #fef3c7; color: #92400e; }

        /* الملاحظات */
        .ra-notes { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.75rem; }
        .ra-note { border-radius: 12px; padding: 0.8rem 1rem; font-size: 0.82rem; line-height: 1.7; border: 1px solid; display: flex; gap: 0.6rem; align-items: flex-start; margin: 0; }
        .ra-note-info { background: #f0f9ff; border-color: #bae6fd; color: #075985; }
        .ra-note-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .ra-note i { margin-top: 0.25rem; }

        /* البحث */
        .ra-search { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .ra-search .ra-search-input { position: relative; flex: 1 1 280px; }
        .ra-search .ra-search-input i { position: absolute; inset-inline-start: 0.85rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .ra-search .ra-search-input input { padding-inline-start: 2.3rem; }
        .ra-search .btn { border-radius: 10px; min-height: 42px; padding-inline: 1.1rem; }

        /* المفاتيح */
        .ra-switch { display: flex; align-items: flex-start; gap: 0.85rem; padding: 0.9rem 1rem; border: 1px solid var(--ra-line); border-radius: 14px; background: var(--ra-soft); margin: 0; transition: border-color .15s, background .15s; }
        .ra-switch:has(input:checked) { border-color: #a5f3fc; background: #f5feff; }
        .ra-switch .form-check-input { width: 2.6em; height: 1.4em; margin: 0.1em 0 0; flex: 0 0 auto; cursor: pointer; float: none; }
        .ra-switch label { font-weight: 700; color: var(--ra-ink); cursor: pointer; margin: 0; font-size: 0.9rem; }
        .ra-switch small { display: block; color: var(--ra-muted); margin-top: 0.2rem; font-size: 0.79rem; line-height: 1.6; }
        .ra-switch.ra-master { border-color: var(--ra-brand); background: #ecfeff; padding: 1.1rem 1.15rem; }
        .ra-switch.ra-master label { font-size: 1rem; }

        /* بطاقات الحسابات */
        .ra-role { height: 100%; display: flex; flex-direction: column; margin: 0; }
        .ra-role .ra-body { display: flex; flex-direction: column; gap: 1rem; flex: 1; }
        .ra-field { margin: 0; }
        .ra-add { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.65rem 0.85rem; border: 1px dashed #cbd5e1; border-radius: 12px; font-size: 0.78rem; color: var(--ra-muted); }
        .ra-add .btn { border-radius: 9px; font-size: 0.8rem; white-space: nowrap; }
        .ra-saved { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: var(--ra-muted); margin-top: 0.45rem; }
        .ra-saved code { background: #f1f5f9; color: var(--ra-ink); border-radius: 6px; padding: 0.1rem 0.45rem; font-size: 0.85rem; letter-spacing: 0.15em; }
        .ra-role .ra-switch { margin-top: auto; }

        /* select2 */
        .ra-page .select2-container { width: 100% !important; }
        .ra-page .select2-container .select2-selection--single { height: 42px; border-radius: 10px; border-color: #dbe3ec; display: flex; align-items: center; }
        .ra-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-inline: 0.85rem 2rem; color: #334155; flex: 1; }
        .ra-page .select2-container .select2-selection--single .select2-selection__arrow { height: 40px; }
        .ra-page .select2-container--focus .select2-selection--single,
        .ra-page .select2-container--open .select2-selection--single { border-color: #67e8f9; box-shadow: 0 0 0 0.2rem rgba(14, 116, 144, 0.12); }
        .ra-page .is-invalid + .select2-container .select2-selection--single { border-color: #dc3545; }
        .ra-page .is-invalid + .select2-container ~ .invalid-feedback { display: block; }
        .ra-dropdown { border-radius: 10px; border-color: #dbe3ec; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14); overflow: hidden; }
        .ra-dropdown .select2-search__field { border-radius: 8px; border-color: #dbe3ec; min-height: 36px; padding-inline: 0.6rem; }
        .ra-dropdown .select2-results__option { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
        .ra-dropdown .select2-results__option--highlighted { background: #0e7490 !important; }

        /* شريط الحفظ */
        .ra-savebar { position: sticky; bottom: 12px; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0.8rem 1rem; margin: 0.5rem 0 1.25rem; border-radius: 16px; background: rgba(255, 255, 255, 0.92); backdrop-filter: blur(8px); border: 1px solid var(--ra-line); box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14); }
        .ra-savebar span { font-size: 0.82rem; color: var(--ra-muted); }
        .ra-savebar .btn { border-radius: 11px; padding: 0.55rem 1.5rem; font-weight: 700; }
    </style>
@endsection

@section('content')
@php
    $roleIcons = ['customer' => 'fa-user', 'worker' => 'fa-broom', 'driver' => 'fa-car'];
    $masterEnabled = (bool) old('enabled', $settings['enabled']);
@endphp
<div class="ra-page">

    <div class="ra-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4><i class="fa fa-mobile-screen-button ms-2"></i> حسابات اختبار التطبيقات</h4>
                <p>اختر حسابًا لكل تطبيق وحدد رمز تحقق ثابتًا من خمسة أرقام. تظل شروط الدخول وحالة الحساب وصلاحية وثائق السائق سارية.</p>
            </div>
            @if ($settings['enabled'])
                <span class="ra-chip ra-chip-on"><i class="fa fa-circle-check"></i> حسابات الاختبار مفعّلة</span>
            @else
                <span class="ra-chip ra-chip-off"><i class="fa fa-circle-pause"></i> حسابات الاختبار متوقفة</span>
            @endif
        </div>
    </div>

    <div class="ra-notes mb-4">
        <p class="ra-note ra-note-info"><i class="fa fa-circle-info"></i><span>رقم العميل يكون من ٩ أرقام ويبدأ بـ 5، مع مفتاح الدولة 966، وفق صيغة تسجيل الدخول الحالية.</span></p>
        <p class="ra-note ra-note-warn"><i class="fa fa-triangle-exclamation"></i><span>التعطيل يوقف الرمز الثابت ويعيد التحقق المعتاد؛ لا يحذف الحساب ولا يسجل خروج الجلسات القائمة. هذه الحسابات تستخدم البيانات والعمليات الفعلية للنظام.</span></p>
    </div>

    <div class="ra-card">
        <div class="ra-head">
            <div class="ra-head-icon"><i class="fa fa-magnifying-glass"></i></div>
            <div>
                <h5>البحث عن حساب</h5>
                <p>تظهر أحدث ٥٠ نتيجة لكل نوع، مع الحساب المختار دائمًا. احفظ التغييرات قبل البحث.</p>
            </div>
        </div>
        <div class="ra-body">
            <form method="GET" action="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}" class="ra-search">
                <div class="ra-search-input">
                    <i class="fa fa-search"></i>
                    <input class="form-control" name="search" value="{{ request('search') }}" placeholder="البحث بالاسم أو رقم الدخول أو رقم الحساب" aria-label="البحث عن حساب">
                </div>
                <button class="btn btn-primary"><i class="fa fa-search ms-1"></i> بحث</button>
                @if (filled(request('search')))
                    <a class="btn btn-outline-secondary" href="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}"><i class="fa fa-xmark ms-1"></i> مسح</a>
                @endif
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('dev-superadmin.hourly-app-review-accounts.update') }}">
        @csrf @method('PUT')

        <div class="ra-card">
            <div class="ra-body">
                <input type="hidden" name="enabled" value="0">
                <div class="ra-switch ra-master form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="review-enabled" name="enabled" value="1" @checked($masterEnabled)>
                    <div>
                        <label for="review-enabled">تفعيل حسابات الاختبار</label>
                        <small>المفتاح الرئيسي؛ عند إيقافه تعود كل الحسابات أدناه إلى التحقق المعتاد مهما كانت إعداداتها.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
        @foreach(\App\Support\HourlyAppReviewAccounts::ROLES as $role => $label)
            @php
                $entry = $settings['accounts'][$role] ?? [];
                $createUrl = match ($role) {
                    'customer' => route('admin.hourly-customers.create'),
                    'driver' => route('admin.hourly-drivers.create'),
                    'worker' => route('maids.rental.create', ['rental_mode' => 'hourly']),
                };
                $selectedId = (string) old("accounts.$role.account_id", $entry['account_id'] ?? '');
                $roleEnabled = (bool) old("accounts.$role.enabled", $entry['enabled'] ?? false);
                $selectedAccount = $selectedId !== '' ? $options[$role]->firstWhere('id', (int) $selectedId) : null;
                $selectedAvailable = $selectedAccount && \App\Support\HourlyAppReviewAccounts::available($role, $selectedAccount);
                [$statusClass, $statusIcon, $statusText] = match (true) {
                    !$selectedAccount => ['ra-status-off', 'fa-circle-minus', 'لم يُختر حساب'],
                    !$selectedAvailable => ['ra-status-warn', 'fa-triangle-exclamation', 'غير متاح للجوال'],
                    $roleEnabled && $masterEnabled => ['ra-status-on', 'fa-circle-check', 'مفعّل'],
                    default => ['ra-status-off', 'fa-circle-pause', 'متوقف'],
                };
            @endphp
            <div class="col-lg-4">
                <div class="ra-card ra-role">
                    <div class="ra-head">
                        <div class="ra-head-icon"><i class="fa {{ $roleIcons[$role] ?? 'fa-user' }}"></i></div>
                        <div>
                            <h5>تطبيق {{ $label }}</h5>
                            <p>{{ $selectedAccount ? (\App\Support\HourlyAppReviewAccounts::phone($role, $selectedAccount) ?: 'بلا رقم دخول') : '—' }}</p>
                        </div>
                        <span class="ra-status {{ $statusClass }}"><i class="fa {{ $statusIcon }}"></i> {{ $statusText }}</span>
                    </div>
                    <div class="ra-body">
                        <div class="ra-field">
                            <label class="form-label" for="review-{{ $role }}">الحساب</label>
                            <select id="review-{{ $role }}" name="accounts[{{ $role }}][account_id]" data-review-select class="form-select @error("accounts.$role.account_id") is-invalid @enderror">
                                <option value="">اختر حسابًا</option>
                                @foreach($options[$role] as $account)
                                    @php($phone = \App\Support\HourlyAppReviewAccounts::phone($role, $account))
                                    <option value="{{ $account->id }}" @selected($selectedId === (string) $account->id)>
                                        #{{ $account->id }} — {{ $account->name }} — {{ $phone ?: 'بلا رقم دخول' }}{{ \App\Support\HourlyAppReviewAccounts::available($role, $account) ? '' : ' — غير متاح للجوال' }}
                                    </option>
                                @endforeach
                            </select>
                            @error("accounts.$role.account_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="ra-add">
                            <span>لا يوجد الحساب المطلوب؟</span>
                            <a href="{{ $createUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus ms-1"></i> إضافة {{ $label }}</a>
                        </div>
                        <small class="form-text mt-n2">يفتح نموذج الإضافة المعتاد بصلاحياته الحالية. بعد الحفظ ارجع وحدّث الصفحة ثم اختر الحساب. فعّل دخول الجوال وأدخل رقمًا سعوديًا.</small>

                        <div class="ra-field">
                            <label class="form-label" for="review-code-{{ $role }}">رمز التحقق الثابت</label>
                            <input id="review-code-{{ $role }}" class="form-control @error("accounts.$role.code") is-invalid @enderror" name="accounts[{{ $role }}][code]" inputmode="numeric" pattern="[0-9]{5}" minlength="5" maxlength="5" autocomplete="off" dir="ltr" placeholder="•••••">
                            @error("accounts.$role.code")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(!empty($entry['code']))
                                <div class="ra-saved"><i class="fa fa-key"></i> الرمز المحفوظ: <code>{{ $entry['code'] }}</code> — اترك الحقل فارغًا للاحتفاظ به.</div>
                            @else
                                <div class="ra-saved"><i class="fa fa-circle-info"></i> لا يوجد رمز محفوظ بعد.</div>
                            @endif
                        </div>

                        <input type="hidden" name="accounts[{{ $role }}][enabled]" value="0">
                        <div class="ra-switch form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="review-enabled-{{ $role }}" name="accounts[{{ $role }}][enabled]" value="1" @checked($roleEnabled)>
                            <div>
                                <label for="review-enabled-{{ $role }}">تفعيل الرمز الثابت لهذا الحساب</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        </div>

        <div class="ra-savebar">
            <span><i class="fa fa-circle-info ms-1"></i> تُطبَّق التغييرات فور الحفظ على تطبيقات الجوال.</span>
            <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-disk ms-1"></i> حفظ إعدادات الاختبار</button>
        </div>
    </form>
</div>
@endsection

@section('js')
    <script src="{{ asset('admin/assets/vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script>
        $(function () {
            $('[data-review-select]').each(function () {
                $(this).select2({
                    dir: document.documentElement.dir || 'rtl',
                    width: '100%',
                    placeholder: 'اختر حسابًا',
                    allowClear: true,
                    dropdownCssClass: 'ra-dropdown',
                    language: {
                        noResults: () => 'لا توجد نتائج — جرّب البحث أعلى الصفحة',
                        searching: () => 'جارٍ البحث…',
                    },
                });
            });
        });
    </script>
@endsection
