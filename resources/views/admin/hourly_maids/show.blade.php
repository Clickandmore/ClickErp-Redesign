@extends('admin.layout.indexs.index')
@section('page-title','تفاصيل العاملة — '.$maid->name)
@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ——— ملف عاملة الساعات بهوية صفحة إدارة الطلب: الأزرق #2794eb مع الفيروزي #00ac9a ——— */
    .mp { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; }
    .mp-card { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); overflow: hidden; margin-bottom: 1.25rem; }

    /* الترويسة: صورة العاملة وهويتها على تدرّج الهوية */
    .mp-hero { position: relative; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.3rem 1.5rem; display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; }
    .mp-hero::after { content: ''; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, var(--teal), rgba(255, 255, 255, .35)); }
    .mp-photo { width: 84px; height: 84px; border-radius: 20px; object-fit: cover; background: rgba(255, 255, 255, .2); border: 3px solid rgba(255, 255, 255, .55); flex: 0 0 84px; }
    .mp-eyebrow { display: block; font-size: .76rem; opacity: .8; margin-bottom: .15rem; }
    .mp-hero h4 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; }
    .mp-hero-meta { display: flex; gap: .45rem 1rem; flex-wrap: wrap; margin-top: .4rem; font-size: .84rem; opacity: .92; }
    .mp-hero-meta span { display: inline-flex; align-items: center; gap: .35rem; }
    .mp-hero-actions { margin-inline-start: auto; display: flex; gap: .5rem; flex-wrap: wrap; }
    .mp-hero-btn { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border: 0; border-radius: 10px; padding: .5rem .95rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .mp-hero-btn:hover { background: #fff; color: var(--brand-ink); }
    .mp-pill { display: inline-flex; align-items: center; gap: .35rem; border-radius: 99px; padding: .2rem .65rem; font-size: .76rem; font-weight: 700; background: rgba(255, 255, 255, .18); color: #fff; }
    .mp-pill.is-warn { background: #fef5e7; color: #9a6206; }

    .mp-layout { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 1.25rem; align-items: start; }
    @media (max-width: 1199px) { .mp-layout { grid-template-columns: 1fr; } .mp-side { order: 2; } }

    /* الشريط الجانبي: بطاقتا الإحصاء ثم الأقسام غير المربوطة وتنبيهات الوثائق */
    .mp-side .mp-card { padding: 1.1rem; }
    .mp-side h6 { font-size: .8rem; font-weight: 700; color: var(--muted); margin: 0 0 .75rem; display: flex; align-items: center; gap: .5rem; }
    .mp-side h6::after { content: ''; flex: 1; height: 1px; background: var(--line); }
    .mp-stat { position: relative; border: 1px solid var(--line); border-radius: 14px; padding: .85rem 1rem; margin-bottom: .7rem; overflow: hidden; }
    .mp-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone); }
    .mp-stat strong { display: flex; align-items: center; gap: .45rem; color: var(--tone); font-size: .92rem; }
    .mp-stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; margin-top: .6rem; text-align: center; }
    .mp-stat-grid b { display: block; padding: .45rem .2rem; border-radius: 10px; background: var(--tint); color: var(--ink); font-size: .8rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .mp-t-green { --tone: #17996f; --tint: #e8f7f1; }
    .mp-t-amber { --tone: #e08a17; --tint: #fef5e7; }
    .mp-hint { font-size: .76rem; color: var(--muted); margin: 0 0 1rem; }
    .mp-list { list-style: none; margin: 0; padding: 0; }
    .mp-list li { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .6rem 0; border-bottom: 1px dashed var(--line); font-size: .86rem; color: var(--ink); }
    .mp-list li:last-child { border-bottom: 0; }
    .mp-list li i { color: var(--brand); width: 18px; }
    .mp-list small { color: var(--muted); font-size: .74rem; }
    .mp-expiry { display: flex; align-items: center; gap: .5rem; border-radius: 12px; padding: .6rem .8rem; font-size: .8rem; margin-top: .6rem; border: 1px solid; }
    .mp-expiry.is-danger { background: #fdeeee; border-color: #f7cdcf; color: #b42a2e; }
    .mp-expiry.is-warn { background: #fef8ec; border-color: #f7ddb2; color: #8a5806; }

    /* التبويبات: مؤشّر متدرّج، والتنقّل يتم دون إعادة تحميل الصفحة */
    .mp-main { padding: 0 1.35rem 1.35rem; }
    .mp-tabs { display: flex; gap: 1.2rem; overflow-x: auto; border-bottom: 1px solid var(--line); margin: 0 -1.35rem 1.35rem; padding: 0 1.35rem; scrollbar-width: thin; }
    .mp-tabs a { position: relative; padding: 1rem .1rem .85rem; white-space: nowrap; text-decoration: none; color: var(--muted); font-weight: 600; font-size: .9rem; display: inline-flex; align-items: center; gap: .4rem; }
    .mp-tabs a::after { content: ''; position: absolute; inset-inline: 0; bottom: -1px; height: 3px; border-radius: 99px; background: transparent; transition: background .15s; }
    .mp-tabs a:hover { color: var(--ink); }
    .mp-tabs a.active { color: var(--brand-ink); }
    .mp-tabs a.active::after { background: linear-gradient(90deg, var(--brand), var(--teal)); }
    .mp-tabs a i { font-size: .85rem; opacity: .8; }
    .mp-pane { animation: mp-fade .18s ease-out; }
    @keyframes mp-fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    .mp-loading { display: grid; place-items: center; gap: .6rem; padding: 3.5rem 1rem; color: var(--muted); font-size: .86rem; }
    .mp-spinner { width: 34px; height: 34px; border-radius: 50%; border: 3px solid var(--line); border-top-color: var(--brand); animation: mp-spin .8s linear infinite; }
    @keyframes mp-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .mp-pane, .mp-spinner { animation: none; } }

    .mp-pane-title { font-size: 1rem; font-weight: 700; color: var(--brand-ink); margin: 0 0 1rem; display: flex; align-items: center; gap: .5rem; }
    .mp-table { width: 100%; font-size: .87rem; border-collapse: separate; border-spacing: 0; }
    .mp-table th { font-size: .78rem; color: var(--muted); font-weight: 700; padding: .65rem .9rem; background: var(--soft); border-bottom: 1px solid var(--line); white-space: nowrap; }
    .mp-table th:first-child { border-start-start-radius: 12px; }
    .mp-table th:last-child { border-start-end-radius: 12px; }
    .mp-table td { padding: .8rem .9rem; border-bottom: 1px solid #eef2f7; vertical-align: middle; color: var(--ink); }
    .mp-table tbody tr:hover td { background: #fbfdff; }
    .mp-num { font-variant-numeric: tabular-nums; }
    .mp-link { color: var(--brand-ink); font-weight: 700; text-decoration: none; }
    .mp-link:hover { color: var(--brand-deep); text-decoration: underline; }
    .mp-badge { display: inline-flex; align-items: center; border-radius: 8px; padding: .18rem .55rem; font-size: .76rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .mp-b-green { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .mp-b-grey { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .mp-b-red { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .mp-b-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .mp-b-blue { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .mp-b-violet { background: #f0effc; color: #4f47b8; border-color: #d6d2f5; }
    /* جدول السجلات: القيمة القديمة مشطوبة بخفوت والجديدة مظلّلة بلون الهوية */
    .mp-logs td { vertical-align: top; }
    .mp-logs td:nth-child(2) { min-width: 170px; }
    .mp-val { display: inline-block; max-width: 260px; overflow-wrap: anywhere; border-radius: 7px; padding: .1rem .45rem; font-size: .82rem; }
    .mp-val-old { color: var(--muted); background: #f3f5f8; text-decoration: line-through; text-decoration-color: rgba(107, 122, 140, .45); }
    .mp-val-new { color: var(--brand-deep); background: #eaf5fe; font-weight: 600; }
    .mp-logs-empty { background: var(--soft); border-radius: 0 0 12px 12px; padding: 3rem 1rem; }
    .mp-pager { margin-top: 1rem; }
    .mp-pager .pagination { margin: 0; }
    .mp-empty { text-align: center; padding: 2.2rem 1rem; color: var(--muted); }
    .mp-empty i { font-size: 1.7rem; color: #c3d2e0; display: block; margin-bottom: .5rem; }
    .mp-empty-lg { padding: 3.5rem 1rem; }
    .mp-empty-lg h5 { color: var(--ink); font-weight: 700; }

    /* نموذج الملف: عناوين الأقسام بترقيم في دائرة، وحقول بحواف ناعمة */
    .mp-profile fieldset { min-width: 0; }
    .mp-profile fieldset:disabled { opacity: .85; }
    .mp-section { font-size: 1rem; font-weight: 700; color: var(--ink); margin: 1.75rem 0 1rem; padding-bottom: .7rem; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: .5rem; }
    .mp-section.mt-0 { margin-top: 0; }
    .mp-section::before { content: ''; width: 4px; height: 18px; border-radius: 99px; background: linear-gradient(180deg, var(--brand), var(--teal)); }
    .mp .form-label { font-size: .82rem; font-weight: 700; color: var(--ink); }
    .mp .form-control, .mp .form-select { border-radius: 11px; border-color: #dfe7ef; font-size: .88rem; }
    .mp .form-control:focus, .mp .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .mp-document { padding: .7rem .9rem; background: var(--soft); border: 1px solid var(--line); border-radius: 12px; height: 100%; font-weight: 600; color: var(--ink); }
    .mp-document small { display: block; color: var(--muted); margin-bottom: .25rem; font-weight: 500; font-size: .76rem; }
    .mp-profile .btn-primary { background: var(--brand-ink); border-color: var(--brand-ink); border-radius: 11px; font-weight: 700; }
    .mp-profile .btn-primary:hover { background: var(--brand-deep); border-color: var(--brand-deep); }
    .mp-profile .btn-outline-primary { border-radius: 11px; font-weight: 600; color: var(--brand-ink); border-color: var(--brand); }
    .mp-profile .btn-outline-primary:hover { background: #eff8ff; color: var(--brand-deep); }
    .mp-profile .table { font-size: .87rem; }
    .mp-profile .table th { font-size: .78rem; color: var(--muted); background: var(--soft); border-bottom: 1px solid var(--line); }
</style>
@endsection
@section('content')
@php
    $profile = $maid->hourly_settings['profile'] ?? [];
    $tabIcons = ['profile' => 'fa-id-card', 'logs' => 'fa-clock-rotate-left', 'calendar' => 'fa-calendar-days', 'orders' => 'fa-cart-shopping', 'visits' => 'fa-house-user', 'leaves' => 'fa-umbrella-beach', 'reviews' => 'fa-star', 'report' => 'fa-chart-line', 'payments' => 'fa-wallet', 'preferences' => 'fa-sliders'];
@endphp
<div class="container-fluid ht-page mp" dir="rtl">
    <div class="mp-card">
        <header class="mp-hero">
            <img class="mp-photo" src="{{ $maid->photo_url }}" alt="صورة {{ $maid->name }}">
            <div>
                <span class="mp-eyebrow">تفاصيل العاملة · ملف عاملة الساعات</span>
                <h4>{{ $maid->name }}</h4>
                <div class="mp-hero-meta">
                    <span><i class="fa fa-hashtag"></i> {{ $maid->id }}</span>
                    <span><i class="fa fa-at"></i> {{ $maid->hourly_username ?: '—' }}</span>
                    <span><i class="fa fa-people-group"></i> {{ $maid->hourlyTeamMemberships->firstWhere('role','primary')?->team?->name ?? 'بدون فريق أساسي' }}</span>
                    <span class="mp-pill {{ $maid->hourly_is_suspended ? 'is-warn' : '' }}"><i class="fa {{ $maid->hourly_is_suspended ? 'fa-pause' : 'fa-circle-check' }}"></i> {{ $maid->hourly_is_suspended ? 'إتاحة الساعات معلّقة' : 'مسموح بالإتاحة للساعات' }}</span>
                </div>
            </div>
            <div class="mp-hero-actions">
                <a class="mp-hero-btn" href="{{ route('admin.hourly-maids.index') }}"><i class="fa fa-arrow-right"></i> العودة للعاملات</a>
            </div>
        </header>
    </div>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>يرجى مراجعة البيانات:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="mp-layout">
        <aside class="mp-side">
            <div class="mp-card">
                <h6>ملخص العمل</h6>
                @foreach(['completed' => ['مكتمل', 'mp-t-green', 'fa-circle-check'], 'pending' => ['قيد الانتظار', 'mp-t-amber', 'fa-hourglass-half']] as $key => [$label, $tone, $icon])
                    <div class="mp-stat {{ $tone }}">
                        <strong><i class="fa {{ $icon }}"></i> {{ $label }}</strong>
                        <div class="mp-stat-grid"><b>{{ $stats[$key]['orders'] }} طلبات</b><b>{{ $stats[$key]['visits'] }} زيارات</b><b>{{ $stats[$key]['hours'] }} ساعات</b></div>
                    </div>
                @endforeach
                <p class="mp-hint">الساعات هي المدة المخططة للزيارات المسندة للعاملة.</p>
                <h6>أقسام قادمة</h6>
                <ul class="mp-list">
                    @foreach(['الإيرادات' => 'fa-sack-dollar', 'التحصيل' => 'fa-wallet', 'التقييمات' => 'fa-star'] as $label => $icon)
                        <li><span><i class="fa {{ $icon }}"></i> {{ $label }}</span><small>غير مربوط بعد</small></li>
                    @endforeach
                </ul>
                @foreach(['id_expiry_date' => 'الإقامة', 'medical_insurance_expiry_date' => 'التأمين الطبي'] as $field => $label)
                    @if($maid->$field && $maid->$field->lte(now()->addDays(30)))
                        <div class="mp-expiry {{ $maid->$field->isPast() ? 'is-danger' : 'is-warn' }}"><i class="fa fa-triangle-exclamation"></i> {{ $label }}: <span dir="ltr">{{ $maid->$field->format('Y-m-d') }}</span></div>
                    @endif
                @endforeach
                <button class="btn btn-outline-secondary w-100 mt-3" disabled title="إرسال الإشعارات غير مربوط بعد"><i class="fa fa-bell"></i> إرسال إشعار</button>
            </div>
        </aside>

        <main class="mp-card mp-main">
            <nav class="mp-tabs" role="tablist" aria-label="أقسام ملف العاملة">
                @foreach($tabs as $key => $label)
                    <a class="{{ $tab === $key ? 'active' : '' }}" role="tab" id="mp-tab-{{ $key }}" aria-controls="mp-pane-{{ $key }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" data-mp-tab="{{ $key }}" href="{{ route('admin.hourly-maids.show', [$maid, $key]) }}"><i class="fa {{ $tabIcons[$key] ?? 'fa-circle' }}"></i> {{ $label }}</a>
                @endforeach
            </nav>
            <div id="mp-panes">
                @foreach($tabs as $key => $label)
                    <section class="mp-pane" id="mp-pane-{{ $key }}" role="tabpanel" aria-labelledby="mp-tab-{{ $key }}" @if($tab === $key) data-loaded="1" data-url="{{ request()->fullUrl() }}" @else hidden @endif>
                        @if($tab === $key) @include('admin.hourly_maids._tab') @endif
                    </section>
                @endforeach
                <div class="mp-loading" id="mp-loading" hidden role="status"><span class="mp-spinner"></span> جارِ التحميل…</div>
            </div>
        </main>
    </div>
</div>
@endsection
@push('js')
<script>
/* التنقّل بين التبويبات: يُجلب محتوى التبويب مرة واحدة ويُحفظ، ويُحدَّث الرابط دون إعادة تحميل. */
(function () {
    const nav = document.querySelector('.mp-tabs');
    if (!nav) return;
    const loading = document.getElementById('mp-loading');
    const pane = key => document.getElementById('mp-pane-' + key);
    let pending = null;

    // السكربتات المدرجة عبر innerHTML لا تُنفَّذ، فنعيد إنشاءها.
    function runScripts(root) {
        root.querySelectorAll('script').forEach(old => {
            const script = document.createElement('script');
            [...old.attributes].forEach(a => script.setAttribute(a.name, a.value));
            script.textContent = old.textContent;
            old.replaceWith(script);
        });
    }

    function activate(key) {
        nav.querySelectorAll('[data-mp-tab]').forEach(link => {
            const on = link.dataset.mpTab === key;
            link.classList.toggle('active', on);
            link.setAttribute('aria-selected', on ? 'true' : 'false');
            on ? link.setAttribute('aria-current', 'page') : link.removeAttribute('aria-current');
        });
        document.querySelectorAll('.mp-pane').forEach(p => p.hidden = p.id !== 'mp-pane-' + key);
    }

    async function load(key, url, push) {
        const target = pane(key);
        if (!target) { location.href = url; return; }
        activate(key);
        if (push) history.pushState({ mpTab: key, url }, '', url);
        if (target.dataset.loaded && target.dataset.url === url) { loading.hidden = true; shown(key); return; }
        target.hidden = true; loading.hidden = false;
        const request = pending = new AbortController();
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' }, signal: request.signal });
            if (!response.ok) throw new Error(response.status);
            const html = await response.text();
            if (pending !== request) return;
            target.innerHTML = html;
            target.dataset.loaded = '1'; target.dataset.url = url;
            // يظهر التبويب قبل تشغيل سكربتاته حتى تقيس مكوّناته (كالتقويم) أبعادها الفعلية.
            loading.hidden = true; target.hidden = false;
            runScripts(target);
        } catch (error) {
            if (error.name === 'AbortError') return;
            target.innerHTML = '<div class="mp-empty"><i class="fa fa-triangle-exclamation"></i> تعذّر تحميل القسم. <a class="mp-link" href="' + url + '">إعادة المحاولة</a></div>';
            delete target.dataset.loaded;
        }
        if (pending !== request) return;
        loading.hidden = true; target.hidden = false;
    }

    const shown = key => document.dispatchEvent(new CustomEvent('mp:tab-shown', { detail: key }));

    nav.addEventListener('click', event => {
        const link = event.target.closest('[data-mp-tab]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
        event.preventDefault();
        if (pending) pending.abort();
        const target = pane(link.dataset.mpTab);
        load(link.dataset.mpTab, target && target.dataset.url ? target.dataset.url : link.href, true);
    });

    // صفحات الترقيم داخل التبويب تُجلب في مكانها أيضًا.
    document.getElementById('mp-panes').addEventListener('click', event => {
        const link = event.target.closest('.mp-pager a[href]');
        if (!link || event.ctrlKey || event.metaKey) return;
        event.preventDefault();
        const key = link.closest('.mp-pane').id.replace('mp-pane-', '');
        load(key, link.href, true);
    });

    const initial = nav.querySelector('[data-mp-tab].active');
    if (initial) history.replaceState({ mpTab: initial.dataset.mpTab, url: location.href }, '', location.href);
    window.addEventListener('popstate', event => {
        if (!event.state || !event.state.mpTab) return;
        if (pending) pending.abort();
        load(event.state.mpTab, event.state.url, false);
    });
})();
</script>
@endpush
