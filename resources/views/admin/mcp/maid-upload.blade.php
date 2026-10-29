<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'رفع ملفات العاملة — ClickERP'])
    <style>
        body.sa-login { padding: 16px; }
        .login-shell { min-width: 0; }
        .login-panel { padding: 22px; }

        .worker { display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: var(--r-md); background: var(--surface-soft); border: 1px solid var(--border); }
        .worker-photo { width: 58px; height: 58px; flex: 0 0 auto; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: var(--shadow-sm); background: var(--accent-soft); color: var(--accent); display: grid; place-items: center; font-weight: 700; font-size: 1.2rem; }
        .worker-info { min-width: 0; flex: 1; }
        .worker-name { font-weight: 700; font-size: 1.02rem; direction: ltr; text-align: right; overflow-wrap: anywhere; }
        .worker-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; background: #fff; border: 1px solid var(--border); color: var(--text-soft); font-size: .76rem; }
        .chip.time { background: var(--warning-soft); border-color: var(--warning-line); color: var(--warning); }

        .notice { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: var(--r-sm); margin-top: 14px; font-size: .9rem; line-height: 1.7; }
        .notice.ok { background: var(--success-soft); color: var(--success); border: 1px solid var(--success-line); }
        .notice.err { background: var(--danger-soft); color: var(--danger); border: 1px solid var(--danger-line); }
        .notice[hidden] { display: none; }

        .tiles { display: grid; gap: 10px; margin-top: 16px; }
        .tile { position: relative; display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px dashed var(--border-strong); border-radius: var(--r-md); background: #fff; cursor: pointer; transition: border-color .15s, background .15s; min-width: 0; }
        .tile:hover, .tile:focus-within { border-color: var(--accent); background: var(--accent-soft); }
        .tile.chosen { border-style: solid; border-color: var(--accent); background: var(--accent-soft); }
        .tile.invalid { border-style: solid; border-color: var(--danger); background: var(--danger-soft); }
        .tile input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
        .tile-icon { width: 46px; height: 46px; flex: 0 0 auto; border-radius: var(--r-sm); display: grid; place-items: center; background: var(--navy-soft); color: var(--navy); overflow: hidden; }
        .tile-icon svg { width: 24px; height: 24px; }
        .tile-icon img { width: 100%; height: 100%; object-fit: cover; }
        .tile-body { min-width: 0; flex: 1; }
        .tile-title { font-weight: 700; font-size: .93rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .tile-badge { font-weight: 500; font-size: .7rem; padding: 1px 7px; border-radius: 999px; background: var(--info-soft); color: var(--info); border: 1px solid var(--info-line); }
        .tile-hint bdi { direction: ltr; unicode-bidi: isolate; }
        .tile-hint { color: var(--muted); font-size: .8rem; margin-top: 2px; overflow-wrap: anywhere; }
        .tile.chosen .tile-hint { color: var(--accent-dark); font-weight: 600; }
        .tile.invalid .tile-hint { color: var(--danger); font-weight: 600; }
        .tile-clear { position: relative; z-index: 2; flex: 0 0 auto; width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--border); background: #fff; color: var(--muted); cursor: pointer; display: none; font-size: 1rem; line-height: 1; }
        .tile.chosen .tile-clear, .tile.invalid .tile-clear { display: grid; place-items: center; }

        .progress { margin-top: 16px; }
        .progress[hidden] { display: none; }
        .progress-top { display: flex; justify-content: space-between; font-size: .85rem; font-weight: 600; color: var(--text-soft); margin-bottom: 6px; }
        .progress-bar { height: 10px; border-radius: 999px; background: var(--surface-alt); overflow: hidden; }
        .progress-bar span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, var(--accent), #14b8a6); border-radius: 999px; transition: width .2s; }
        .progress small { display: block; margin-top: 6px; color: var(--muted); font-size: .78rem; }

        .login-submit { margin-top: 16px; }
        .login-submit:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
        .foot-note { display: flex; gap: 8px; align-items: flex-start; margin: 16px 0 0; padding-top: 14px; border-top: 1px solid var(--border); color: var(--muted); font-size: .8rem; line-height: 1.7; }
        .foot-note svg { width: 16px; height: 16px; flex: 0 0 auto; margin-top: 3px; color: var(--accent); }
    </style>
</head>
<body class="sa-login">
@php
    $hasImage = $maid->image && $maid->image !== 'default.png';
    $tiles = [
        'image' => ['title' => 'الصورة الشخصية', 'hint' => 'اختر صورة أو التقطها — حتى 10 ميجابايت', 'accept' => 'image/*', 'max' => 10, 'current' => $hasImage,
            'icon' => '<path d="M4 7h3l2-3h6l2 3h3v13H4z"/><circle cx="12" cy="13" r="4"/>'],
        'video' => ['title' => 'الفيديو', 'hint' => 'اختر فيديو أو صوّره — حتى 100 ميجابايت', 'accept' => 'video/*', 'max' => 100, 'current' => (bool) $maid->video,
            'icon' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/>'],
        'cv_file' => ['title' => 'ملف السيرة الأصلي', 'hint' => 'PDF أو صورة — حتى 20 ميجابايت', 'accept' => 'application/pdf,image/*', 'max' => 20, 'current' => filled($maid->cv_file),
            'icon' => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>'],
    ];
@endphp
<main class="login-shell">
    <div class="login-brand">
        <div class="login-mark">C</div>
        <div>
            <h1>رفع ملفات العاملة</h1>
            <p>من المساعد الذكي إلى سيرة العاملة مباشرة</p>
        </div>
    </div>

    <section class="login-panel">
        <div class="worker">
            @if ($hasImage)
                <img class="worker-photo" src="{{ showFile($maid->image) }}" alt="">
            @else
                <div class="worker-photo">{{ mb_strtoupper(mb_substr(trim($maid->name ?: '؟'), 0, 1)) }}</div>
            @endif
            <div class="worker-info">
                <div class="worker-name">{{ $maid->name ?: '—' }}</div>
                <div class="worker-chips">
                    @if ($maid->cv_code)
                        <span class="chip" dir="ltr">{{ $maid->cv_code }}</span>
                    @endif
                    <span class="chip">{{ $admin->name }}</span>
                    <span class="chip time" id="expiry" data-expires="{{ $expiresAt->timestamp }}">صالح حتى <b dir="ltr">{{ $expiresAt->format('H:i') }}</b></span>
                </div>
            </div>
        </div>

        <div class="notice ok" id="ok" role="status" @unless (session('success')) hidden @endunless>
            <span>✓</span><span id="ok-text">{{ session('success') }} يمكنك رفع ملف آخر أو إغلاق الصفحة.</span>
        </div>
        <div class="notice err" id="err" role="alert" @unless ($errors->any()) hidden @endunless>
            <span>!</span><span id="err-text">{{ implode(' ', $errors->all()) }}</span>
        </div>

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="upload-form">
            @csrf
            <div class="tiles">
                @foreach ($tiles as $field => $tile)
                    <label class="tile" data-max-mb="{{ $tile['max'] }}" data-hint="{{ $tile['hint'] }}">
                        <input type="file" name="{{ $field }}" accept="{{ $tile['accept'] }}">
                        <span class="tile-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $tile['icon'] !!}</svg></span>
                        <span class="tile-body">
                            <span class="tile-title">{{ $tile['title'] }}
                                @if ($tile['current'])<span class="tile-badge">يوجد حالياً — الجديد يستبدله</span>@endif
                            </span>
                            <span class="tile-hint">{{ $tile['hint'] }}</span>
                        </span>
                        <button type="button" class="tile-clear" aria-label="إلغاء الاختيار">×</button>
                    </label>
                @endforeach
            </div>

            <div class="progress" id="progress" hidden>
                <div class="progress-top"><span>جارٍ الرفع…</span><span id="progress-pct" dir="ltr">0%</span></div>
                <div class="progress-bar"><span id="progress-bar"></span></div>
                <small>لا تغلق الصفحة حتى يكتمل الرفع.</small>
            </div>

            <button class="login-submit" type="submit" id="submit">رفع وحفظ</button>
        </form>

        <p class="foot-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <span>هذا الرابط لك وحدك ولهذه العاملة فقط، فلا تشاركه مع أحد. كل رفع يُسجَّل باسمك.</span>
        </p>
    </section>
</main>

<script>
(function () {
    var form = document.getElementById('upload-form');
    var submit = document.getElementById('submit');
    var tiles = Array.prototype.slice.call(form.querySelectorAll('.tile'));
    var ok = document.getElementById('ok'), err = document.getElementById('err');

    function formatSize(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' ميجابايت' : Math.max(1, Math.round(bytes / 1024)) + ' كيلوبايت';
    }

    var expired = false;

    function describe(hint, file, suffix) {
        var name = document.createElement('bdi');
        name.textContent = file.name;
        hint.textContent = '';
        hint.appendChild(name);
        hint.appendChild(document.createTextNode(' — ' + formatSize(file.size) + (suffix || '')));
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
        tile.querySelector('.tile-clear').addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation(); reset(tile); refresh();
        });
    });

    // الرفع مع شريط تقدم؛ بدون JavaScript يعمل النموذج بالإرسال العادي.
    form.addEventListener('submit', function (e) {
        if (!window.FormData || !window.XMLHttpRequest) { return; }
        e.preventDefault();
        if (submit.disabled) { return; }
        var data = new FormData(form);
        tiles.forEach(function (t) { if (!t.classList.contains('chosen')) { data.delete(t.querySelector('input').name); } });

        var progress = document.getElementById('progress'), bar = document.getElementById('progress-bar'), pct = document.getElementById('progress-pct');
        ok.hidden = true; err.hidden = true; progress.hidden = false; submit.disabled = true; submit.textContent = 'جارٍ الرفع…';
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
            progress.hidden = true; bar.style.width = '0'; submit.textContent = 'رفع وحفظ';
            tiles.forEach(function (t) { t.querySelector('input').disabled = false; });
            if (xhr.status >= 200 && xhr.status < 300) {
                document.getElementById('ok-text').textContent = (body.message || 'تم الحفظ.') + ' يمكنك رفع ملف آخر أو إغلاق الصفحة.';
                ok.hidden = false;
                tiles.forEach(function (t) {
                    if (t.classList.contains('chosen')) {
                        var title = t.querySelector('.tile-title');
                        if (!title.querySelector('.tile-badge')) {
                            var badge = document.createElement('span'); badge.className = 'tile-badge'; badge.textContent = 'تم الرفع';
                            title.appendChild(badge);
                        }
                        reset(t);
                    }
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
        if (Date.now() >= expiresAt) {
            expired = true;
            expiry.textContent = 'انتهت صلاحية الرابط — اطلب رابطاً جديداً';
            refresh();
        }
    }, 15000);

    refresh();
})();
</script>
</body>
</html>
