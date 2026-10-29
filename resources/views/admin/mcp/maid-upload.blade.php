<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
@php
    $brandName = optional(optional($settings ?? null)->translate(app()->getLocale()))->dashboard_name
        ?: optional(optional($settings ?? null)->translate(app()->getLocale()))->website_name
        ?: 'ClickERP';
    $brandLogo = null;
    foreach ([$settings->admin_logo ?? null, $settings->logo_header ?? null, $settings->favicon ?? null, $developSettings->logo_login ?? null] as $logoPath) {
        if (filled($logoPath)) {
            $brandLogo = showFile($logoPath);
            break;
        }
    }

    $hasImage = $maid->image && $maid->image !== 'default.png';
    $tiles = [
        'image' => ['title' => 'الصورة الشخصية', 'hint' => 'اختر صورة أو التقطها بالكاميرا', 'limit' => 'حتى 10 ميجابايت', 'accept' => 'image/*', 'max' => 10, 'current' => $hasImage, 'tone' => 'teal',
            'icon' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>'],
        'video' => ['title' => 'الفيديو التعريفي', 'hint' => 'اختر فيديو أو صوّره الآن', 'limit' => 'حتى 100 ميجابايت', 'accept' => 'video/*', 'max' => 100, 'current' => (bool) $maid->video, 'tone' => 'indigo',
            'icon' => '<rect x="3" y="6" width="13" height="12" rx="2.5"/><path d="m16 10.5 5-3v9l-5-3z"/>'],
        'cv_file' => ['title' => 'ملف السيرة الأصلي', 'hint' => 'PDF أو صورة من المكتب', 'limit' => 'حتى 20 ميجابايت', 'accept' => 'application/pdf,image/*', 'max' => 20, 'current' => filled($maid->cv_file), 'tone' => 'amber',
            'icon' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>'],
    ];
@endphp
    @include('admin.superadmin.partials.head', ['pageTitle' => 'رفع ملفات العاملة — ' . $brandName])
    <style>
        :root {
            --hero-a: #0b2540; --hero-b: #0f4c5c; --hero-c: #0f766e;
            --teal: #0f766e; --teal-soft: #e6f5f2;
            --indigo: #4338ca; --indigo-soft: #eef0ff;
            --amber: #b45309; --amber-soft: #fff7e6;
            --ok: #067647; --ok-soft: #ecfdf3;
        }

        body.upload-page {
            min-height: 100vh; margin: 0; background: #f2f5f9;
            display: flex; flex-direction: column; align-items: center; padding: 0 16px 32px;
        }
        /* شريط علوي بتدرج الهوية ونقش نقطي خفيف ينحني من الأسفل */
        body.upload-page::before {
            content: ""; position: fixed; inset: 0 0 auto 0; height: 330px; z-index: 0;
            background:
                radial-gradient(rgba(255, 255, 255, .09) 1px, transparent 1.4px) 0 0 / 20px 20px,
                radial-gradient(600px 260px at 15% 0%, rgba(20, 184, 166, .35), transparent 70%),
                linear-gradient(135deg, var(--hero-a) 0%, var(--hero-b) 55%, var(--hero-c) 100%);
            clip-path: ellipse(120% 100% at 50% 0%);
        }

        .wrap { position: relative; z-index: 1; width: min(520px, 100%); min-width: 0; }

        .hero { color: #fff; text-align: center; padding: 34px 0 26px; }
        .hero-logo { width: 62px; height: 62px; margin: 0 auto 14px; border-radius: 18px; background: #fff; display: grid; place-items: center; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); overflow: hidden; }
        .hero-logo img { max-width: 78%; max-height: 78%; object-fit: contain; }
        .hero-logo b { color: var(--hero-b); font-size: 1.5rem; }
        .hero-brand { font-size: .78rem; letter-spacing: .04em; opacity: .75; }
        .hero h1 { margin: 4px 0 6px; font-size: 1.5rem; font-weight: 700; color: #fff; }
        .hero p { margin: 0; font-size: .9rem; opacity: .82; }
        .secure { display: inline-flex; align-items: center; gap: 7px; margin-top: 14px; padding: 6px 14px; border-radius: 999px; background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .22); font-size: .8rem; backdrop-filter: blur(4px); }
        .secure svg { width: 14px; height: 14px; }
        .secure.expired { background: rgba(244, 63, 94, .25); border-color: rgba(254, 205, 211, .5); }

        .card { background: #fff; border-radius: 24px; box-shadow: 0 24px 60px rgba(11, 37, 64, .16), 0 4px 14px rgba(11, 37, 64, .06); overflow: hidden; }

        .profile { display: flex; align-items: center; gap: 16px; padding: 22px 22px 20px; border-bottom: 1px solid #edf1f5; }
        .avatar { width: 68px; height: 68px; flex: 0 0 auto; border-radius: 50%; padding: 3px; background: linear-gradient(135deg, #14b8a6, var(--hero-a)); }
        .avatar > * { width: 100%; height: 100%; border-radius: 50%; border: 3px solid #fff; object-fit: cover; display: grid; place-items: center; background: var(--teal-soft); color: var(--teal); font-weight: 700; font-size: 1.4rem; }
        .profile-info { min-width: 0; flex: 1; }
        .profile-label { font-size: .74rem; color: var(--muted); }
        .profile-name { font-size: 1.12rem; font-weight: 700; color: var(--text); direction: ltr; text-align: right; overflow-wrap: anywhere; margin: 1px 0 7px; }
        .meta { display: flex; flex-wrap: wrap; gap: 6px; }
        .meta span { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 8px; background: #f4f7fa; color: var(--text-soft); font-size: .76rem; }
        .meta svg { width: 13px; height: 13px; opacity: .7; }

        .body { padding: 20px 22px 22px; }
        .section-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
        .section-head b { font-size: .95rem; color: var(--text); }
        .section-head span { font-size: .78rem; color: var(--muted); }

        .notice { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; margin-bottom: 14px; font-size: .88rem; line-height: 1.7; }
        .notice svg { width: 20px; height: 20px; flex: 0 0 auto; margin-top: 1px; }
        .notice.ok { background: var(--ok-soft); color: var(--ok); border: 1px solid #abefc6; }
        .notice.err { background: #fef3f2; color: #b42318; border: 1px solid #fbd5d1; }
        .notice[hidden] { display: none; }

        .tiles { display: grid; gap: 10px; }
        .tile { position: relative; display: flex; align-items: center; gap: 14px; padding: 14px; border: 1px solid #e3e9ef; border-radius: 16px; background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s, background .15s; min-width: 0; }
        .tile:hover, .tile:focus-within { border-color: #b8c8d6; box-shadow: 0 6px 18px rgba(11, 37, 64, .07); }
        .tile input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
        .tile-icon { width: 50px; height: 50px; flex: 0 0 auto; border-radius: 14px; display: grid; place-items: center; overflow: hidden; }
        .tile-icon svg { width: 24px; height: 24px; }
        .tile-icon img { width: 100%; height: 100%; object-fit: cover; }
        .tone-teal .tile-icon { background: var(--teal-soft); color: var(--teal); }
        .tone-indigo .tile-icon { background: var(--indigo-soft); color: var(--indigo); }
        .tone-amber .tile-icon { background: var(--amber-soft); color: var(--amber); }
        .tile-body { min-width: 0; flex: 1; }
        .tile-title { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; font-weight: 700; font-size: .93rem; color: var(--text); }
        .tile-hint { color: var(--muted); font-size: .8rem; margin-top: 3px; overflow-wrap: anywhere; }
        .tile-hint bdi { direction: ltr; unicode-bidi: isolate; }
        .tile-limit { color: #94a3b8; }
        .badge { font-weight: 600; font-size: .68rem; padding: 2px 8px; border-radius: 999px; }
        .badge.current { background: #f1f5f9; color: #475569; }
        .badge.done { background: var(--ok-soft); color: var(--ok); }
        .tile-action { position: relative; z-index: 2; flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; border: 1px dashed #cbd5e1; color: #94a3b8; background: #fff; }
        .tile-action svg { width: 16px; height: 16px; }
        .tile-action .x { display: none; }
        .tile.chosen { border-color: var(--teal); background: linear-gradient(0deg, rgba(15, 118, 110, .04), rgba(15, 118, 110, .04)), #fff; box-shadow: 0 0 0 3px rgba(15, 118, 110, .10); }
        .tile.chosen .tile-hint { color: var(--teal); font-weight: 600; }
        .tile.invalid { border-color: #f04438; background: #fffbfa; box-shadow: 0 0 0 3px rgba(240, 68, 56, .10); }
        .tile.invalid .tile-hint { color: #b42318; font-weight: 600; }
        .tile.chosen .tile-action, .tile.invalid .tile-action { border-style: solid; cursor: pointer; color: #64748b; }
        .tile.chosen .tile-action .plus, .tile.invalid .tile-action .plus { display: none; }
        .tile.chosen .tile-action .x, .tile.invalid .tile-action .x { display: block; }

        .progress { margin-top: 16px; padding: 14px; border-radius: 14px; background: #f6f9fb; border: 1px solid #e8eef3; }
        .progress[hidden] { display: none; }
        .progress-top { display: flex; justify-content: space-between; font-size: .85rem; font-weight: 600; color: var(--text-soft); margin-bottom: 8px; }
        .progress-bar { height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .progress-bar span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #0f766e, #14b8a6); border-radius: 999px; transition: width .2s; }
        .progress small { display: block; margin-top: 8px; color: var(--muted); font-size: .76rem; }

        .submit { width: 100%; margin-top: 18px; min-height: 52px; border: 0; border-radius: 14px; display: flex; align-items: center; justify-content: center; gap: 9px;
            background: linear-gradient(135deg, var(--hero-b), var(--hero-c)); color: #fff; font: inherit; font-weight: 700; font-size: 1rem; cursor: pointer;
            box-shadow: 0 10px 24px rgba(15, 118, 110, .30); transition: transform .12s, box-shadow .15s, opacity .15s; }
        .submit svg { width: 19px; height: 19px; }
        .submit:hover { box-shadow: 0 12px 28px rgba(15, 118, 110, .38); }
        .submit:active { transform: translateY(1px); }
        .submit:disabled { opacity: .4; cursor: not-allowed; box-shadow: none; }

        .footer { display: flex; align-items: flex-start; justify-content: center; gap: 8px; margin: 18px auto 0; max-width: 440px; color: #64748b; font-size: .78rem; line-height: 1.7; text-align: center; }
        .footer svg { width: 15px; height: 15px; flex: 0 0 auto; margin-top: 3px; color: var(--teal); }
        .powered { margin-top: 8px; text-align: center; color: #94a3b8; font-size: .72rem; }

        @media (max-width: 420px) {
            .hero { padding: 26px 0 22px; }
            .hero h1 { font-size: 1.3rem; }
            .profile, .body { padding-left: 16px; padding-right: 16px; }
            .tile { padding: 12px; gap: 12px; }
        }
    </style>
</head>
<body class="upload-page">
<div class="wrap">
    <header class="hero">
        <div class="hero-logo">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}">
            @else
                <b>{{ mb_substr($brandName, 0, 1) }}</b>
            @endif
        </div>
        <div class="hero-brand">{{ $brandName }}</div>
        <h1>رفع ملفات العاملة</h1>
        <p>أضف الصورة والفيديو إلى السيرة مباشرة من جهازك</p>
        <div class="secure" id="expiry" data-expires="{{ $expiresAt->timestamp }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <span id="expiry-text">رابط آمن · صالح حتى <b dir="ltr">{{ $expiresAt->format('H:i') }}</b></span>
        </div>
    </header>

    <main class="card">
        <div class="profile">
            <div class="avatar" id="avatar">
                @if ($hasImage)
                    <img src="{{ showFile($maid->image) }}" alt="">
                @else
                    <span>{{ mb_strtoupper(mb_substr(trim($maid->name ?: '؟'), 0, 1)) }}</span>
                @endif
            </div>
            <div class="profile-info">
                <div class="profile-label">العاملة</div>
                <div class="profile-name">{{ $maid->name ?: '—' }}</div>
                <div class="meta">
                    @if ($maid->cv_code)
                        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg><bdi>{{ $maid->cv_code }}</bdi></span>
                    @endif
                    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>{{ $admin->name }}</span>
                </div>
            </div>
        </div>

        <div class="body">
            <div class="notice ok" id="ok" role="status" @unless (session('success')) hidden @endunless>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                <span id="ok-text">{{ session('success') }} يمكنك رفع ملف آخر أو إغلاق الصفحة.</span>
            </div>
            <div class="notice err" id="err" role="alert" @unless ($errors->any()) hidden @endunless>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
                <span id="err-text">{{ implode(' ', $errors->all()) }}</span>
            </div>

            <div class="section-head"><b>الملفات</b><span>اختر ملفاً واحداً أو أكثر</span></div>

            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="upload-form">
                @csrf
                <div class="tiles">
                    @foreach ($tiles as $field => $tile)
                        <label class="tile tone-{{ $tile['tone'] }}" data-max-mb="{{ $tile['max'] }}" data-hint="{{ $tile['hint'] }} · {{ $tile['limit'] }}">
                            <input type="file" name="{{ $field }}" accept="{{ $tile['accept'] }}">
                            <span class="tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $tile['icon'] !!}</svg></span>
                            <span class="tile-body">
                                <span class="tile-title">{{ $tile['title'] }}
                                    @if ($tile['current'])<span class="badge current">يوجد حالياً · الجديد يستبدله</span>@endif
                                </span>
                                <span class="tile-hint">{{ $tile['hint'] }} · <span class="tile-limit">{{ $tile['limit'] }}</span></span>
                            </span>
                            <span class="tile-action" role="button" aria-label="إلغاء الاختيار">
                                <svg class="plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                                <svg class="x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="progress" id="progress" hidden>
                    <div class="progress-top"><span>جارٍ الرفع…</span><span id="progress-pct" dir="ltr">0%</span></div>
                    <div class="progress-bar"><span id="progress-bar"></span></div>
                    <small>لا تغلق الصفحة حتى يكتمل الرفع.</small>
                </div>

                <button class="submit" type="submit" id="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
                    <span id="submit-text">رفع وحفظ</span>
                </button>
            </form>
        </div>
    </main>

    <p class="footer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v5c0 4.5 3 8.5 7 10 4-1.5 7-5.5 7-10V6z"/></svg>
        <span>هذا الرابط لك وحدك ولهذه العاملة فقط، فلا تشاركه مع أحد. كل رفع يُسجَّل باسمك.</span>
    </p>
    <div class="powered">ClickERP · المساعد الذكي</div>
</div>

<script>
(function () {
    var form = document.getElementById('upload-form');
    var submit = document.getElementById('submit'), submitText = document.getElementById('submit-text');
    var tiles = Array.prototype.slice.call(form.querySelectorAll('.tile'));
    var ok = document.getElementById('ok'), err = document.getElementById('err');
    var expired = false;

    function formatSize(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' ميجابايت' : Math.max(1, Math.round(bytes / 1024)) + ' كيلوبايت';
    }

    // اسم الملف معزول الاتجاه حتى لا يختلط مع النص العربي.
    function describe(hint, file, suffix) {
        var name = document.createElement('bdi');
        name.textContent = file.name;
        hint.textContent = '';
        hint.appendChild(name);
        hint.appendChild(document.createTextNode(' · ' + formatSize(file.size) + (suffix || '')));
    }

    function refresh() {
        var chosen = tiles.some(function (t) { return t.classList.contains('chosen'); });
        var invalid = tiles.some(function (t) { return t.classList.contains('invalid'); });
        submit.disabled = expired || !chosen || invalid;
    }

    // يمسح الشكل فقط؛ reset يمسح الملف المختار أيضاً.
    function clearView(tile) {
        var icon = tile.querySelector('.tile-icon');
        tile.classList.remove('chosen', 'invalid');
        tile.querySelector('.tile-hint').textContent = tile.dataset.hint;
        var img = icon.querySelector('img');
        if (img) { URL.revokeObjectURL(img.src); img.remove(); icon.querySelector('svg').style.display = ''; }
    }

    function reset(tile) {
        tile.querySelector('input').value = '';
        clearView(tile);
    }

    tiles.forEach(function (tile) {
        var input = tile.querySelector('input');
        input.addEventListener('change', function () {
            var file = input.files[0];
            var hint = tile.querySelector('.tile-hint'), icon = tile.querySelector('.tile-icon');
            if (!file) { reset(tile); refresh(); return; }
            clearView(tile);
            if (file.size > tile.dataset.maxMb * 1048576) {
                tile.classList.add('invalid');
                describe(hint, file, '، أكبر من الحد (' + tile.dataset.maxMb + ' ميجابايت)');
            } else {
                tile.classList.add('chosen');
                describe(hint, file);
                if (file.type.indexOf('image/') === 0) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    icon.querySelector('svg').style.display = 'none';
                    icon.appendChild(img);
                }
            }
            refresh();
        });
        tile.querySelector('.tile-action').addEventListener('click', function (e) {
            if (!tile.classList.contains('chosen') && !tile.classList.contains('invalid')) { return; }
            e.preventDefault(); e.stopPropagation(); reset(tile); refresh();
        });
    });

    function markUploaded(tile) {
        var title = tile.querySelector('.tile-title');
        var old = title.querySelector('.badge');
        if (old) { old.remove(); }
        var badge = document.createElement('span');
        badge.className = 'badge done';
        badge.textContent = 'تم الرفع ✓';
        title.appendChild(badge);

        // الصورة الشخصية الجديدة تظهر فوراً في بطاقة العاملة.
        var input = tile.querySelector('input');
        if (input.name === 'image' && input.files[0]) {
            var avatar = document.getElementById('avatar');
            var img = document.createElement('img');
            img.src = URL.createObjectURL(input.files[0]);
            avatar.innerHTML = '';
            avatar.appendChild(img);
        }
    }

    // الرفع مع شريط تقدم؛ بدون JavaScript يعمل النموذج بالإرسال العادي.
    form.addEventListener('submit', function (e) {
        if (!window.FormData || !window.XMLHttpRequest) { return; }
        e.preventDefault();
        if (submit.disabled) { return; }
        var data = new FormData(form);
        tiles.forEach(function (t) { if (!t.classList.contains('chosen')) { data.delete(t.querySelector('input').name); } });

        var progress = document.getElementById('progress'), bar = document.getElementById('progress-bar'), pct = document.getElementById('progress-pct');
        ok.hidden = true; err.hidden = true; progress.hidden = false; submit.disabled = true; submitText.textContent = 'جارٍ الرفع…';
        tiles.forEach(function (t) { t.querySelector('input').disabled = true; });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = function (ev) {
            if (!ev.lengthComputable) { return; }
            var p = Math.round(ev.loaded / ev.total * 100);
            bar.style.width = p + '%'; pct.textContent = p + '%';
            if (p === 100) { pct.textContent = 'جارٍ الحفظ…'; }
        };
        xhr.onloadend = function () {
            var body = {};
            try { body = JSON.parse(xhr.responseText || '{}'); } catch (ignore) {}
            progress.hidden = true; bar.style.width = '0'; submitText.textContent = 'رفع وحفظ';
            tiles.forEach(function (t) { t.querySelector('input').disabled = false; });
            if (xhr.status >= 200 && xhr.status < 300) {
                document.getElementById('ok-text').textContent = (body.message || 'تم الحفظ.') + ' يمكنك رفع ملف آخر أو إغلاق الصفحة.';
                ok.hidden = false;
                tiles.forEach(function (t) {
                    if (t.classList.contains('chosen')) { markUploaded(t); reset(t); }
                });
            } else {
                var messages = body.errors ? Object.keys(body.errors).map(function (k) { return body.errors[k].join(' '); }).join(' ') : '';
                document.getElementById('err-text').textContent =
                    xhr.status === 403 ? 'انتهت صلاحية الرابط أو لم تعد لديك الصلاحية. اطلب رابطاً جديداً من المساعد.'
                    : xhr.status === 413 ? 'حجم الملفات أكبر مما يسمح به السيرفر.'
                    : messages || body.message || 'تعذر الرفع. تحقق من الاتصال ثم أعد المحاولة.';
                err.hidden = false;
            }
            refresh();
        };
        xhr.send(data);
    });

    // تنبيه عند انتهاء صلاحية الرابط والصفحة مفتوحة.
    var expiry = document.getElementById('expiry');
    var expiresAt = parseInt(expiry.dataset.expires, 10) * 1000;
    setInterval(function () {
        if (!expired && Date.now() >= expiresAt) {
            expired = true;
            expiry.classList.add('expired');
            document.getElementById('expiry-text').textContent = 'انتهت صلاحية الرابط — اطلب رابطاً جديداً من المساعد';
            refresh();
        }
    }, 15000);

    refresh();
})();
</script>
</body>
</html>
