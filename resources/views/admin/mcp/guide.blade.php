@extends('admin.layout.indexs.index')

@section('page-title', 'المساعد الذكي')

@push('style')
<style>
    .ai-guide { --ai-accent: var(--primary-color, #0f6b6e); --ai-line: var(--border-color, #dde5ea); --ai-muted: #68778a; --ai-ink: #101827; color: var(--ai-ink); padding-bottom: 24px; }
    .ai-guide * { box-sizing: border-box; }
    .ai-hero { display: flex; align-items: center; gap: 18px; padding: 24px 28px; margin-bottom: 20px; border: 1px solid var(--ai-line); border-radius: 14px; background: #fff; }
    .ai-hero-icon { flex: 0 0 56px; width: 56px; height: 56px; display: grid; place-items: center; border-radius: 14px; background: var(--ai-accent); color: #fff; font-size: 24px; }
    .ai-hero h1 { margin: 0 0 4px; font-size: 1.35rem; font-weight: 700; color: var(--ai-ink); }
    .ai-hero p { margin: 0; color: var(--ai-muted); font-size: .92rem; line-height: 1.7; }
    .ai-status { margin-inline-start: auto; flex: 0 0 auto; display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; font-size: .82rem; font-weight: 700; white-space: nowrap; }
    .ai-status::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .ai-status.is-on { background: #e8f5ef; color: #17754f; }
    .ai-status.is-off { background: #fdf3e3; color: #a2670c; }

    .ai-notice { display: flex; gap: 12px; align-items: flex-start; padding: 14px 18px; margin-bottom: 20px; border: 1px solid #f1dcb4; border-radius: 12px; background: #fdf8ee; color: #7a4f08; font-size: .9rem; line-height: 1.7; }
    .ai-notice i { margin-top: 4px; }

    .ai-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 20px; align-items: start; }
    .ai-stack { display: grid; gap: 20px; min-width: 0; }
    .ai-card { padding: 22px 24px; border: 1px solid var(--ai-line); border-radius: 14px; background: #fff; min-width: 0; }
    .ai-card h2 { margin: 0 0 4px; font-size: 1.02rem; font-weight: 700; color: var(--ai-ink); }
    .ai-card-sub { margin: 0 0 16px; color: var(--ai-muted); font-size: .85rem; line-height: 1.7; }

    .ai-url { display: flex; gap: 10px; align-items: stretch; }
    .ai-url code { flex: 1; min-width: 0; padding: 11px 14px; border: 1px solid var(--ai-line); border-radius: 10px; background: #f7f9fb; color: var(--ai-ink); font-size: .9rem; text-align: left; overflow-x: auto; white-space: nowrap; }
    .ai-btn { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 42px; padding: 0 16px; border: 1px solid var(--ai-accent); border-radius: 10px; background: var(--ai-accent); color: #fff; font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; transition: opacity .15s; }
    .ai-btn:hover { opacity: .88; }
    .ai-btn:focus-visible, .ai-tab:focus-visible, .ai-copy:focus-visible { outline: 2px solid var(--ai-accent); outline-offset: 2px; }

    .ai-tabs { display: flex; gap: 6px; padding: 4px; margin-bottom: 18px; border-radius: 10px; background: #f1f4f7; }
    .ai-tab { flex: 1; min-height: 38px; padding: 0 10px; border: 0; border-radius: 8px; background: transparent; color: var(--ai-muted); font: inherit; font-size: .86rem; font-weight: 700; cursor: pointer; }
    .ai-tab[aria-selected="true"] { background: #fff; color: var(--ai-ink); box-shadow: 0 1px 3px rgba(16, 24, 39, .12); }
    .ai-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
    .ai-steps[hidden] { display: none; }
    .ai-steps li { display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 12px; align-items: start; font-size: .9rem; line-height: 1.8; }
    .ai-step-no { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; background: #e6f1f1; color: var(--ai-accent); font-size: .82rem; font-weight: 700; }
    .ai-steps b { font-weight: 700; }
    .ai-path { display: inline-block; direction: ltr; unicode-bidi: isolate; font-weight: 700; }
    .ai-steps code { display: block; direction: ltr; text-align: left; margin-top: 6px; padding: 9px 12px; border-radius: 8px; background: #f7f9fb; border: 1px solid var(--ai-line); color: var(--ai-ink); font-size: .82rem; overflow-x: auto; white-space: nowrap; }

    .ai-examples { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .ai-example { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 14px; border: 1px solid var(--ai-line); border-radius: 10px; font-size: .88rem; line-height: 1.6; }
    .ai-copy { flex: 0 0 34px; width: 34px; height: 34px; display: grid; place-items: center; border: 0; border-radius: 8px; background: #f1f4f7; color: var(--ai-muted); cursor: pointer; }
    .ai-copy:hover { background: #e6f1f1; color: var(--ai-accent); }
    .ai-empty { padding: 18px; border: 1px dashed var(--ai-line); border-radius: 10px; color: var(--ai-muted); font-size: .87rem; text-align: center; }

    .ai-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 16px; }
    .ai-list li { display: grid; grid-template-columns: 34px minmax(0, 1fr); gap: 12px; align-items: start; }
    .ai-list-icon { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: #e6f1f1; color: var(--ai-accent); font-size: .9rem; }
    .ai-list b { display: block; font-size: .88rem; font-weight: 700; }
    .ai-list span.ai-list-text { display: block; color: var(--ai-muted); font-size: .82rem; line-height: 1.7; }

    @media (max-width: 1100px) { .ai-grid { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 680px) {
        .ai-hero { flex-wrap: wrap; padding: 20px; }
        .ai-status { margin-inline-start: 0; }
        .ai-card { padding: 18px; }
        .ai-examples { grid-template-columns: minmax(0, 1fr); }
        .ai-url { flex-direction: column; }
    }
</style>
@endpush

@section('content')
@php
    $serviceOpen = $serverEnabled && $subscription['allows_read'];
    $ready = $serviceOpen && $activated;
@endphp
<div class="container-fluid ai-guide">
    <header class="ai-hero">
        <div class="ai-hero-icon"><i class="fa fa-robot" aria-hidden="true"></i></div>
        <div>
            <h1>المساعد الذكي</h1>
            <p>اسأل Claude أو ChatGPT عن بيانات النظام بالعربية، واحصل على الإجابة داخل المحادثة.</p>
        </div>
        <span class="ai-status {{ $ready ? 'is-on' : 'is-off' }}">{{ $ready ? ($subscription['state'] === 'grace' ? 'قراءة فقط' : 'مفعّل لحسابك') : 'غير مفعّل' }}</span>
    </header>

    @if (!$serverEnabled)
        <div class="ai-notice" role="status"><i class="fa fa-circle-info" aria-hidden="true"></i><div>الخدمة غير مفعّلة حالياً. للاستفادة منها تواصل مع فريق ClickERP.</div></div>
    @elseif (!$subscription['allows_read'])
        <div class="ai-notice" role="status"><i class="fa fa-circle-info" aria-hidden="true"></i><div>{{ $subscription['message'] }}</div></div>
    @elseif (!$activated)
        <div class="ai-notice" role="status"><i class="fa fa-circle-info" aria-hidden="true"></i><div>لم يُفعَّل المساعد لحسابك بعد، فلن يكتمل الربط. لتفعيله تواصل مع فريق ClickERP.</div></div>
    @elseif ($subscription['message'])
        <div class="ai-notice" role="status"><i class="fa fa-circle-info" aria-hidden="true"></i><div>{{ $subscription['message'] }}</div></div>
    @endif

    <div class="ai-grid">
        <div class="ai-stack">
            @if ($serviceOpen)
                <section class="ai-card">
                    <h2>رابط الخادم</h2>
                    <p class="ai-card-sub">هذا هو الرابط الذي تضيفه في البرنامج الذي تستخدمه.</p>
                    <div class="ai-url">
                        <code id="aiServerUrl" dir="ltr">{{ $serverUrl }}</code>
                        <button type="button" class="ai-btn" data-copy-target="#aiServerUrl"><i class="fa fa-copy" aria-hidden="true"></i><span>نسخ الرابط</span></button>
                    </div>
                </section>

                <section class="ai-card">
                    <h2>طريقة الربط</h2>
                    @if ($oauthEnabled)
                        <p class="ai-card-sub">مرة واحدة فقط لكل برنامج، ولا تحتاج إلى كلمة مرور جديدة.</p>
                        <div class="ai-tabs" role="tablist" aria-label="البرنامج">
                            <button type="button" class="ai-tab" role="tab" aria-selected="true" aria-controls="aiStepsClaude" id="aiTabClaude">Claude</button>
                            <button type="button" class="ai-tab" role="tab" aria-selected="false" aria-controls="aiStepsChatgpt" id="aiTabChatgpt">ChatGPT</button>
                            <button type="button" class="ai-tab" role="tab" aria-selected="false" aria-controls="aiStepsCode" id="aiTabCode">Claude Code</button>
                        </div>

                        <ol class="ai-steps" id="aiStepsClaude" role="tabpanel" aria-labelledby="aiTabClaude">
                            <li><span class="ai-step-no">1</span><div>افتح <span class="ai-path">Settings → Connectors</span></div></li>
                            <li><span class="ai-step-no">2</span><div>اختر <span class="ai-path">Add custom connector</span> والصق رابط الخادم.</div></li>
                            <li><span class="ai-step-no">3</span><div>ستفتح صفحة دخول ClickERP: سجّل دخولك واضغط <b>موافقة وربط</b>.</div></li>
                        </ol>
                        <ol class="ai-steps" id="aiStepsChatgpt" role="tabpanel" aria-labelledby="aiTabChatgpt" hidden>
                            <li><span class="ai-step-no">1</span><div>افتح <span class="ai-path">Settings → Apps &amp; Connectors → Advanced</span> وفعّل <span class="ai-path">Developer mode</span>.</div></li>
                            <li><span class="ai-step-no">2</span><div>اختر <span class="ai-path">Create</span>، والصق رابط الخادم، واختر المصادقة <span class="ai-path">OAuth</span>.</div></li>
                            <li><span class="ai-step-no">3</span><div>ستفتح صفحة دخول ClickERP: سجّل دخولك واضغط <b>موافقة وربط</b>.</div></li>
                        </ol>
                        <ol class="ai-steps" id="aiStepsCode" role="tabpanel" aria-labelledby="aiTabCode" hidden>
                            <li><span class="ai-step-no">1</span><div>نفّذ هذا الأمر في الطرفية:<code dir="ltr">claude mcp add --transport http clickerp {{ $serverUrl }}</code></div></li>
                            <li><span class="ai-step-no">2</span><div>داخل Claude Code اكتب <span class="ai-path">/mcp</span> ثم اختر <span class="ai-path">Authenticate</span>.</div></li>
                            <li><span class="ai-step-no">3</span><div>ستفتح صفحة دخول ClickERP: سجّل دخولك واضغط <b>موافقة وربط</b>.</div></li>
                        </ol>
                    @else
                        <p class="ai-card-sub" style="margin-bottom:0">لإتمام الربط تواصل مع فريق ClickERP.</p>
                    @endif
                </section>
            @endif

            <section class="ai-card">
                <h2>جرّب أن تسأل</h2>
                <p class="ai-card-sub">أمثلة تناسب صلاحياتك. انسخ أي سؤال والصقه في المحادثة، أو اسأل بأسلوبك.</p>
                @if ($examples)
                    <div class="ai-examples">
                        @foreach ($examples as $example)
                            <div class="ai-example">
                                <span>{{ $example }}</span>
                                <button type="button" class="ai-copy" data-copy-text="{{ $example }}" aria-label="نسخ السؤال" title="نسخ"><i class="fa fa-copy" aria-hidden="true"></i></button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ai-empty">لا توجد أمثلة متاحة لصلاحيات حسابك حالياً.</div>
                @endif
            </section>
        </div>

        <aside class="ai-stack">
            <section class="ai-card">
                <h2>بياناتك في أمان</h2>
                <p class="ai-card-sub">المساعد يعمل بحسابك أنت، بلا صلاحيات إضافية.</p>
                <ul class="ai-list">
                    <li><span class="ai-list-icon"><i class="fa fa-user-shield" aria-hidden="true"></i></span><div><b>صلاحياتك فقط</b><span class="ai-list-text">يرى ما تراه في لوحة التحكم، داخل فرعك.</span></div></li>
                    <li><span class="ai-list-icon"><i class="fa fa-eye-slash" aria-hidden="true"></i></span><div><b>الأرقام الحساسة مخفية</b><span class="ai-list-text">يظهر آخر 4 أرقام من الجوال والجواز والهوية.</span></div></li>
                    <li><span class="ai-list-icon"><i class="fa fa-circle-check" aria-hidden="true"></i></span><div><b>لا تنفيذ بلا موافقتك</b><span class="ai-list-text">الإضافة والإرسال يحتاجان إلى موافقتك أولاً.</span></div></li>
                    <li><span class="ai-list-icon"><i class="fa fa-clipboard-list" aria-hidden="true"></i></span><div><b>كل استدعاء مسجّل</b><span class="ai-list-text">لإيقاف ربط أو تغيير ما هو متاح لك، تواصل مع فريق ClickERP.</span></div></li>
                </ul>
            </section>

            @if ($prompts)
                <section class="ai-card">
                    <h2>قوالب جاهزة</h2>
                    <p class="ai-card-sub">تظهر في البرامج التي تدعم القوالب.</p>
                    <ul class="ai-list">
                        @foreach ($prompts as $prompt)
                            <li><span class="ai-list-icon"><i class="fa fa-wand-magic-sparkles" aria-hidden="true"></i></span><div><b>{{ $prompt['title'] }}</b><span class="ai-list-text">{{ $prompt['description'] }}</span></div></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('js')
<script>
    (function () {
        var root = document.querySelector('.ai-guide');
        if (!root) { return; }

        var tabs = Array.prototype.slice.call(root.querySelectorAll('.ai-tab'));
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (other) {
                    var selected = other === tab;
                    other.setAttribute('aria-selected', selected ? 'true' : 'false');
                    document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
                });
            });
        });

        function copy(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }
            var area = document.createElement('textarea');
            area.value = text;
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();
            var done = document.execCommand('copy');
            document.body.removeChild(area);
            return done ? Promise.resolve() : Promise.reject();
        }

        root.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy-target], [data-copy-text]');
            if (!button) { return; }

            var target = button.getAttribute('data-copy-target');
            var text = target ? document.querySelector(target).textContent.trim() : button.getAttribute('data-copy-text');
            var icon = button.querySelector('i');

            copy(text).then(function () {
                icon.className = 'fa fa-check';
                setTimeout(function () { icon.className = 'fa fa-copy'; }, 1500);
            }).catch(function () {
                icon.className = 'fa fa-xmark';
                setTimeout(function () { icon.className = 'fa fa-copy'; }, 1500);
            });
        });
    })();
</script>
@endpush
