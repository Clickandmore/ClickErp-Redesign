<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'المساعد الذكي (MCP) — ClickERP'])
    <style>
        .mcp-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; align-items: start; }
        .mcp-grid-2 > .panel + .panel { margin-top: 0; }
        .mcp-fieldset .switch-row, .mcp-edit-body .switch-row { min-height: 48px; padding: 0 14px; border: 1px solid var(--border-strong); border-radius: var(--r-sm); background: #fff; }
        .mcp-stats { margin-bottom: 0; }
        .mcp-stat-hint { margin-top: 4px; color: var(--muted); font-size: .78rem; }
        .mcp-stat.is-warn::before { background: linear-gradient(90deg, var(--warning), #f59e0b); }
        .mcp-stat.is-bad::before { background: linear-gradient(90deg, var(--danger), #ef4444); }

        .mcp-checks { display: grid; gap: 10px; }
        .mcp-check { display: grid; grid-template-columns: 26px 1fr; gap: 10px; align-items: start; padding: 11px 13px; border: 1px solid var(--border); border-radius: var(--r-sm); background: var(--surface-soft); }
        .mcp-check-icon { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: .8rem; }
        .mcp-check.is-ok .mcp-check-icon { background: var(--success-soft); color: var(--success); border: 1px solid var(--success-line); }
        .mcp-check.is-bad .mcp-check-icon { background: var(--warning-soft); color: var(--warning); border: 1px solid var(--warning-line); }
        .mcp-check b { display: block; font-size: .88rem; }
        .mcp-check span { color: var(--muted); font-size: .8rem; }

        .mcp-chart { display: flex; align-items: flex-end; gap: 6px; height: 170px; padding: 8px 4px 0; border-bottom: 1px solid var(--border-strong); }
        .mcp-bar { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 4px; min-width: 0; }
        .mcp-bar i { display: block; width: 100%; max-width: 30px; border-radius: 6px 6px 2px 2px; background: linear-gradient(180deg, #3ba99a, var(--accent)); min-height: 2px; }
        .mcp-bar small { font-size: .68rem; color: var(--muted); font-weight: 600; }
        .mcp-bar-labels { display: flex; gap: 6px; padding: 6px 4px 0; }
        .mcp-bar-labels span { flex: 1; text-align: center; font-size: .66rem; color: var(--muted); direction: ltr; }

        .mcp-rank { display: grid; gap: 10px; }
        .mcp-rank-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 12px; font-size: .86rem; }
        .mcp-rank-row code { font: .8rem var(--mono); color: var(--text-soft); direction: ltr; }
        .mcp-rank-track { grid-column: 1 / -1; height: 7px; border-radius: 999px; background: var(--surface-alt); overflow: hidden; }
        .mcp-rank-track i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--accent), #3ba99a); }
        .mcp-empty { padding: 26px; text-align: center; color: var(--muted); font-size: .88rem; border: 1px dashed var(--border-strong); border-radius: var(--r-md); background: var(--surface-soft); }

        .mcp-mode { display: flex; gap: 14px; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 14px 18px; border-radius: var(--r-md); border: 1px solid var(--info-line); background: var(--info-soft); color: var(--info); font-size: .87rem; }
        .mcp-mode b { color: var(--text); }

        .mcp-form-row { display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 12px; align-items: end; }
        .mcp-person { display: flex; align-items: center; gap: 11px; min-width: 0; }
        .mcp-avatar { width: 38px; height: 38px; flex: 0 0 auto; border-radius: 12px; display: grid; place-items: center; font-weight: 700; color: var(--navy); background: var(--navy-soft); border: 1px solid var(--border); }
        .mcp-person b { display: block; font-size: .9rem; }
        .mcp-person small { display: block; color: var(--muted); font-size: .78rem; direction: ltr; text-align: right; }
        .mcp-badges { display: flex; gap: 6px; flex-wrap: wrap; }
        .badge.neutral { background: var(--surface-alt); color: var(--text-soft); border-color: var(--border); }
        .badge.info { background: var(--info-soft); color: var(--info); border-color: var(--info-line); }
        .badge.warn { background: var(--warning-soft); color: var(--warning); border-color: var(--warning-line); }
        .mcp-row-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .mcp-row-actions form { margin: 0; }

        .mcp-edit summary { list-style: none; cursor: pointer; }
        .mcp-edit summary::-webkit-details-marker { display: none; }
        .mcp-edit-body { margin-top: 14px; padding: 16px; border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); display: grid; gap: 14px; }
        .mcp-edit-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .mcp-choice { display: flex; gap: 8px; flex-wrap: wrap; }
        .mcp-choice label { display: inline-flex; align-items: center; gap: 7px; padding: 8px 12px; border: 1px solid var(--border-strong); border-radius: var(--r-sm); background: #fff; font-size: .84rem; font-weight: 600; cursor: pointer; }
        .mcp-choice input { accent-color: var(--accent); }
        .mcp-tool-picks { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px 14px; }
        .mcp-tool-picks label { display: flex; gap: 7px; align-items: center; font-size: .82rem; }
        .mcp-tool-picks code { font: .78rem var(--mono); direction: ltr; }
        .mcp-tool-picks h4 { grid-column: 1 / -1; margin-top: 6px; font-size: .78rem; color: var(--muted); }

        .mcp-write { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 18px 20px; border-radius: var(--r-md); border: 1px solid var(--warning-line); background: var(--warning-soft); }
        .mcp-write h3 { font-size: 1rem; color: var(--warning); }
        .mcp-write p { color: var(--text-soft); font-size: .85rem; margin-top: 4px; }
        .mcp-category { margin-top: 22px; }
        .mcp-category-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .mcp-category-head h3 { font-size: .98rem; }
        .mcp-tool-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .mcp-tool { display: grid; gap: 10px; align-content: start; padding: 15px; border: 1px solid var(--border); border-radius: var(--r-md); background: #fff; box-shadow: var(--shadow-xs); transition: border-color .15s, opacity .15s; }
        .mcp-tool.is-off { opacity: .62; background: var(--surface-soft); }
        .mcp-tool-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .mcp-tool-head code { font: 600 .82rem var(--mono); direction: ltr; color: var(--navy); }
        .mcp-tool p { color: var(--text-soft); font-size: .82rem; line-height: 1.65; }
        .mcp-tool-meta { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; color: var(--muted); font-size: .75rem; }
        .mcp-tool-meta code { font: .72rem var(--mono); direction: ltr; }
        .mcp-sticky-save { position: sticky; bottom: 14px; z-index: 20; margin-top: 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; border-radius: var(--r-md); background: rgba(255,255,255,.92); border: 1px solid var(--border); box-shadow: var(--shadow-lg); backdrop-filter: blur(10px); }

        .mcp-filters { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) auto; gap: 10px; align-items: end; }
        .mcp-filters .field input, .mcp-filters .field select { min-height: 40px; }
        .mcp-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 14px; color: var(--muted); font-size: .84rem; }
        .mcp-args { display: inline-block; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: bottom; font: .76rem var(--mono); direction: ltr; }

        .mcp-settings { display: grid; gap: 18px; }
        .mcp-fieldset { padding: 18px; border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); }
        .mcp-fieldset > h3 { font-size: .95rem; margin-bottom: 4px; }
        .mcp-fieldset > p { color: var(--muted); font-size: .82rem; margin-bottom: 14px; }
        .mcp-fieldset .switch-row { background: #fff; }
        .mcp-switches { display: grid; gap: 10px; }
        .mcp-inputs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .mcp-radio-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .mcp-radio-card { position: relative; display: block; padding: 15px 16px 15px 44px; border: 1.5px solid var(--border-strong); border-radius: var(--r-md); background: #fff; cursor: pointer; }
        .mcp-radio-card input { position: absolute; left: 16px; top: 18px; accent-color: var(--accent); width: 17px; height: 17px; }
        .mcp-radio-card b { display: block; font-size: .9rem; }
        .mcp-radio-card span { display: block; margin-top: 3px; color: var(--muted); font-size: .8rem; line-height: 1.6; }
        .mcp-radio-card:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); box-shadow: 0 0 0 4px var(--ring); }

        .mcp-token { display: flex; gap: 10px; align-items: center; }
        .mcp-token code { flex: 1; direction: ltr; text-align: left; padding: 12px 14px; border-radius: var(--r-sm); background: #0d1a2b; color: #a7f3d0; font: .85rem var(--mono); overflow-x: auto; white-space: nowrap; }
        .mcp-snippet { direction: ltr; text-align: left; margin: 8px 0 16px; }
        .mcp-connect-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .mcp-connect-card { padding: 18px; border: 1px solid var(--border); border-radius: var(--r-md); background: #fff; }
        .mcp-connect-card h3 { display: flex; align-items: center; gap: 8px; font-size: .98rem; margin-bottom: 8px; }
        .mcp-connect-card ol { margin: 0; padding-right: 18px; color: var(--text-soft); font-size: .85rem; line-height: 1.9; }

        @media (max-width: 1100px) {
            .mcp-tool-grid, .mcp-tool-picks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .mcp-filters { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 820px) {
            .mcp-grid-2, .mcp-connect-grid, .mcp-radio-cards, .mcp-form-row, .mcp-edit-grid, .mcp-inputs { grid-template-columns: 1fr; }
            .mcp-tool-grid, .mcp-tool-picks, .mcp-filters { grid-template-columns: 1fr; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body class="sa-recovery">
@php
    $navIcons = [
        'overview' => '<path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/>',
        'subscription' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'employees' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'tools' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
        'connections' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'logs' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 7 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 3.6 14H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 2.82 1.17l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'connect' => '<path d="M12 2v6M12 16v6M4.93 4.93l4.24 4.24M14.83 14.83l4.24 4.24M2 12h6M16 12h6"/>',
    ];
    $selectedMode = $settings['access_mode'] === \App\Support\McpSettings::ACCESS_SELECTED;
    $toolsByCategory = $tools->groupBy(fn ($tool) => $tool->category());
    $shortDescription = fn ($tool) => \Illuminate\Support\Str::of($tool->description())->before('. ')->before('Returns')->trim()->rtrim('.')->__toString();
    $initial = fn ($name) => mb_substr(trim((string) $name) ?: '؟', 0, 1);
@endphp
<main class="shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">AI</div>
            <div>
                <h1>المساعد الذكي (MCP)</h1>
                <p class="muted">ربط Claude وChatGPT وCodex ببيانات النظام، بصلاحيات كل موظف وتحت تحكمك الكامل.</p>
            </div>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('dev-superadmin.modules') }}">لوحة Super Admin</a>
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    <div class="page-heading">
        <div>
            <h2>{{ $tabs[$tab] }}</h2>
            <p>المساعد يرى ما يراه الموظف في لوحة التحكم فقط، داخل فرعه، وكل استدعاء يُسجَّل.</p>
        </div>
        <div class="page-heading-meta">
            <span class="heading-chip {{ $subscription['allows_write'] ? 'is-good' : 'is-bad' }}">الاشتراك: <b>{{ $subscription['label'] }}</b></span>
            <span class="heading-chip {{ $settings['enabled'] ? 'is-good' : 'is-bad' }}">الخادم: <b>{{ $settings['enabled'] ? 'يعمل' : 'مغلق' }}</b></span>
            <span class="heading-chip {{ $settings['oauth_enabled'] ? 'is-good' : '' }}">OAuth: <b>{{ $settings['oauth_enabled'] ? 'مفعّل' : 'مغلق' }}</b></span>
            <span class="heading-chip">الوصول: <b>{{ $selectedMode ? 'المحددون فقط' : 'الجميع' }}</b></span>
            <span class="heading-chip {{ $settings['write_enabled'] ? 'is-bad' : '' }}">الكتابة: <b>{{ $settings['write_enabled'] ? 'مفعّلة' : 'مغلقة' }}</b></span>
        </div>
    </div>

    <nav class="section-nav" aria-label="أقسام المساعد الذكي">
        @foreach ($tabs as $key => $label)
            <a class="{{ $tab === $key ? 'active' : '' }}" href="{{ route('dev-superadmin.mcp.index', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $navIcons[$key] !!}</svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (!$tablesReady)
        <div class="alert alert-danger">
            جداول MCP غير مكتملة. شغّل الرقعة المعلقة من
            <a href="{{ route('dev-superadmin.recovery') }}">الاستعادة والفحص</a> ثم أعد تحميل الصفحة. تبويب الإعدادات يعمل بدونها.
        </div>
    @endif

    @if ($plainToken)
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2>التوكن الجديد #{{ $plainToken['id'] }} — {{ $plainToken['admin'] }}</h2>
                    <p class="muted">انسخه الآن. لن يظهر مرة أخرى؛ النظام يحفظ نسخة مشفرة منه فقط.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="mcp-token">
                    <code id="mcpPlainToken">{{ $plainToken['value'] }}</code>
                    <button class="btn btn-primary" type="button" data-copy="#mcpPlainToken">نسخ</button>
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================================ نظرة عامة --}}
    @if ($tab === 'overview' && $tablesReady)
        @php
            $failedToday = ($today['denied'] ?? 0) + ($today['disabled'] ?? 0) + ($today['blocked'] ?? 0) + ($today['expired'] ?? 0);
            $errorsToday = $today['error'] ?? 0;
            $maxDay = max(1, $days->max('total'));
            $maxTool = max(1, $topTools->max() ?? 1);
            $maxAdmin = max(1, $topAdmins->max('total') ?? 1);
        @endphp
        <section class="stats mcp-stats">
            <div class="stat">
                <div class="stat-label">اتصالات فعّالة</div>
                <div class="stat-value">{{ number_format($summary['connections']) }}</div>
                <div class="mcp-stat-hint">توكنات يدوية وروابط OAuth</div>
            </div>
            <div class="stat">
                <div class="stat-label">موظفون مفعّلون</div>
                <div class="stat-value">{{ $summary['employees'] === null ? 'الجميع' : number_format($summary['employees']) }}</div>
                <div class="mcp-stat-hint">{{ $selectedMode ? 'المحددون في تبويب الموظفين' : 'كل من لديه توكن صالح' }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">استدعاءات اليوم</div>
                <div class="stat-value">{{ number_format($summary['calls_today']) }}</div>
                <div class="mcp-stat-hint">أدوات نُفذت منذ منتصف الليل</div>
            </div>
            <div class="stat mcp-stat {{ $errorsToday ? 'is-bad' : ($failedToday ? 'is-warn' : '') }}">
                <div class="stat-label">مرفوضة / أخطاء اليوم</div>
                <div class="stat-value">{{ number_format($failedToday) }} <small style="font-size:.9rem;color:var(--muted)">/ {{ number_format($errorsToday) }}</small></div>
                <div class="mcp-stat-hint">الرفض طبيعي؛ الأخطاء تستحق المراجعة</div>
            </div>
        </section>

        <div class="mcp-grid-2">
            <section class="panel">
                <div class="panel-head"><div><h2>جاهزية التشغيل</h2><p class="muted">ما يلزم ليعمل الربط مع كل البرامج.</p></div></div>
                <div class="panel-body">
                    <div class="mcp-checks">
                        @foreach ($checks as $check)
                            <div class="mcp-check {{ $check['ok'] ? 'is-ok' : 'is-bad' }}">
                                <div class="mcp-check-icon">{{ $check['ok'] ? '✓' : '!' }}</div>
                                <div><b>{{ $check['label'] }}</b><span>{{ $check['hint'] }}</span></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><div><h2>الاستدعاءات آخر 14 يوماً</h2><p class="muted">عدد الأدوات المنفذة يومياً.</p></div></div>
                <div class="panel-body">
                    <div class="mcp-chart" role="img" aria-label="رسم الاستدعاءات اليومية">
                        @foreach ($days as $day)
                            <div class="mcp-bar" title="{{ $day['date']->format('Y-m-d') }}: {{ $day['total'] }}">
                                <small>{{ $day['total'] ?: '' }}</small>
                                <i style="height: {{ round($day['total'] / $maxDay * 100) }}%"></i>
                            </div>
                        @endforeach
                    </div>
                    <div class="mcp-bar-labels">
                        @foreach ($days as $day)
                            <span>{{ $day['date']->format('d/m') }}</span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><div><h2>أكثر الأدوات استخداماً</h2><p class="muted">آخر 30 يوماً.</p></div></div>
                <div class="panel-body">
                    @if ($topTools->isEmpty())
                        <div class="mcp-empty">لا توجد استدعاءات بعد.</div>
                    @else
                        <div class="mcp-rank">
                            @foreach ($topTools as $name => $total)
                                <div class="mcp-rank-row">
                                    <code>{{ $name }}</code><b>{{ number_format($total) }}</b>
                                    <div class="mcp-rank-track"><i style="width: {{ round($total / $maxTool * 100) }}%"></i></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><div><h2>أكثر الموظفين استخداماً</h2><p class="muted">آخر 30 يوماً.</p></div></div>
                <div class="panel-body">
                    @if ($topAdmins->isEmpty())
                        <div class="mcp-empty">لا توجد استدعاءات بعد.</div>
                    @else
                        <div class="mcp-rank">
                            @foreach ($topAdmins as $row)
                                <div class="mcp-rank-row">
                                    <span>{{ $row['name'] }}</span><b>{{ number_format($row['total']) }}</b>
                                    <div class="mcp-rank-track"><i style="width: {{ round($row['total'] / $maxAdmin * 100) }}%"></i></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="mcp-grid-2">
            <section class="panel">
                <div class="panel-head"><div><h2>بحث بلا نتائج</h2><p class="muted">آخر 30 يوماً: ما يبحث عنه الموظفون ولا يجدونه.</p></div></div>
                <div class="panel-body">
                    @if ($emptySearches->isEmpty())
                        <div class="mcp-empty">لا يوجد بعد.</div>
                    @else
                        <div class="mcp-rank">
                            @foreach ($emptySearches as $log)
                                <div class="mcp-rank-row"><code>{{ $log->tool }}</code><span class="mcp-args">{{ json_encode($log->arguments, JSON_UNESCAPED_UNICODE) }}</span></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
            <section class="panel">
                <div class="panel-head"><div><h2>فرص المتابعة</h2><p class="muted">أدوات لم تُستخدم آخر 30 يوماً، وموظفون ربطوا ولم يستخدموا المساعد منذ 14 يوماً.</p></div></div>
                <div class="panel-body">
                    <div class="mcp-badges">
                        @forelse ($unusedTools as $name)
                            <span class="badge neutral">{{ $name }}</span>
                        @empty
                            <span class="muted">كل الأدوات مستخدمة.</span>
                        @endforelse
                    </div>
                    <div class="mcp-badges" style="margin-top:14px">
                        @forelse ($idleAdmins as $name)
                            <span class="badge warn">{{ $name }}</span>
                        @empty
                            <span class="muted">لا يوجد موظف متوقف.</span>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <section class="panel">
            <div class="panel-head">
                <div><h2>آخر النشاط</h2><p class="muted">آخر 8 طلبات وصلت للخادم.</p></div>
                <a class="btn btn-small" href="{{ route('dev-superadmin.mcp.index', ['tab' => 'logs']) }}">السجل الكامل</a>
            </div>
            <div class="panel-body">
                @include('admin.superadmin.partials.mcp-log-table', ['rows' => $recentLogs, 'names' => $logAdmins])
            </div>
        </section>
    @endif

    {{-- ================================================================ الموظفون --}}
    @if ($tab === 'employees' && $tablesReady)
        <div class="mcp-mode">
            @if ($selectedMode)
                <span>وضع الوصول: <b>الموظفون المحددون فقط</b> — لا يستخدم المساعد إلا من في هذه القائمة ومفعّل.</span>
            @else
                <span>وضع الوصول: <b>الجميع</b> — أي موظف لديه توكن صالح يستخدم المساعد للقراءة. هذه القائمة لإيقاف أشخاص أو منحهم الكتابة أو تحديد أدوات.</span>
            @endif
            <a class="btn btn-small" href="{{ route('dev-superadmin.mcp.index', ['tab' => 'settings']) }}">تغيير الوضع</a>
        </div>

        <section class="panel">
            <div class="panel-head"><div><h2>إضافة موظف</h2><p class="muted">صلاحيات الشاشات تبقى الحد الأعلى: ما لا يراه الموظف في لوحة التحكم لا يصل إليه عبر المساعد.</p></div></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.mcp.admins.store') }}" class="mcp-form-row">
                    @csrf
                    <div class="field">
                        <label>الموظف <span class="required">*</span></label>
                        <select name="admin_id" required>
                            <option value="">— اختر —</option>
                            @foreach ($candidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ ((string) old('admin_id') === (string) $candidate->id) ? 'selected' : '' }}>{{ $candidate->name }} — {{ $candidate->email }}{{ $candidate->branch_id ? '' : ' (كل الفروع)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>المستوى</label>
                        <select name="access_level">
                            <option value="read">قراءة فقط</option>
                            <option value="write">قراءة وإضافة</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>ملاحظة</label>
                        <input type="text" name="note" maxlength="190" placeholder="مثال: مدير العمليات — تقارير يومية" value="{{ old('note') }}">
                    </div>
                    <button class="btn btn-primary" type="submit">إضافة</button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>الموظفون ({{ $accesses->count() }})</h2><p class="muted">اضغط «تعديل» لتغيير المستوى أو تحديد الأدوات. الإيقاف يقطع الاستخدام فوراً دون حذف الاتصالات.</p></div></div>
            <div class="panel-body">
                @if ($accesses->isEmpty())
                    <div class="mcp-empty">لم تُضف أي موظف بعد.{{ $selectedMode ? ' في وضع «المحددون فقط» لن يعمل المساعد لأحد حتى تضيف موظفاً.' : '' }}</div>
                @else
                    <div class="table-wrap" style="margin-top:0">
                        <table>
                            <thead><tr><th>الموظف</th><th>الحالة</th><th>اتصالات</th><th>آخر استخدام</th><th>استدعاءات 30 يوماً</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($accesses as $access)
                                @php
                                    $person = $access->admin;
                                    $suspended = $person && ($person->status ?? 'active') === 'suspended';
                                    $conn = $connections[$access->admin_id] ?? null;
                                    $lastUsed = $conn?->last_used ? \Illuminate\Support\Carbon::parse($conn->last_used) : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="mcp-person">
                                            <div class="mcp-avatar">{{ $initial($person->name ?? '') }}</div>
                                            <div>
                                                <b>{{ $person->name ?? ('#' . $access->admin_id) }}</b>
                                                <small>{{ $person->email ?? '' }}</small>
                                                @if ($access->note)<small style="direction:rtl">{{ $access->note }}</small>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mcp-badges">
                                            @if (!$person || $person->deleted_at)
                                                <span class="badge bad">محذوف</span>
                                            @elseif ($suspended)
                                                <span class="badge bad">موقوف في النظام</span>
                                            @endif
                                            <span class="badge {{ $access->enabled ? 'ok' : 'bad' }}">{{ $access->enabled ? 'مفعّل' : 'موقوف' }}</span>
                                            <span class="badge {{ $access->canWrite() ? 'warn' : 'neutral' }}">{{ $access->canWrite() ? 'قراءة وإضافة' : 'قراءة' }}</span>
                                            <span class="badge info">{{ $access->allowed_tools === null ? 'كل الأدوات' : count($access->allowed_tools) . ' أدوات' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $conn->total ?? 0 }}</td>
                                    <td dir="ltr">{{ $lastUsed?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>{{ number_format($calls[$access->admin_id] ?? 0) }}</td>
                                    <td>
                                        <div class="mcp-row-actions">
                                            @if ($conn?->total)
                                                <form method="POST" action="{{ route('dev-superadmin.mcp.admins.revoke-connections', $access->admin_id) }}" onsubmit="return confirm('قطع كل اتصالات {{ $person->name ?? 'الموظف' }}؟ سيحتاج لإعادة الربط.')">
                                                    @csrf
                                                    <button class="btn btn-small" type="submit">قطع الاتصالات</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('dev-superadmin.mcp.admins.destroy', $access) }}" onsubmit="return confirm('إزالة {{ $person->name ?? 'الموظف' }} من القائمة؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-small" type="submit">إزالة</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" style="padding-top:0">
                                        <details class="mcp-edit" @if ((string) old('access_id') === (string) $access->id) open @endif>
                                            <summary class="btn btn-small">تعديل الصلاحية</summary>
                                            <form method="POST" action="{{ route('dev-superadmin.mcp.admins.update', $access) }}" class="mcp-edit-body">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="access_id" value="{{ $access->id }}">
                                                <div class="mcp-edit-grid">
                                                    <div class="switch-row">
                                                        <span>مفعّل</span>
                                                        <label class="switch">
                                                            <input type="hidden" name="enabled" value="0">
                                                            <input type="checkbox" name="enabled" value="1" {{ ($access->enabled) ? 'checked' : '' }}>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </div>
                                                    <div class="field">
                                                        <label>المستوى</label>
                                                        <select name="access_level">
                                                            <option value="read" {{ (!$access->canWrite()) ? 'selected' : '' }}>قراءة فقط</option>
                                                            <option value="write" {{ ($access->canWrite()) ? 'selected' : '' }}>قراءة وإضافة{{ $settings['write_enabled'] ? '' : ' (الكتابة مغلقة عامةً)' }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="field">
                                                        <label>ملاحظة</label>
                                                        <input type="text" name="note" maxlength="190" value="{{ $access->note }}">
                                                    </div>
                                                </div>
                                                <div class="field">
                                                    <label>الأدوات</label>
                                                    <div class="mcp-choice">
                                                        <label><input type="radio" name="tools_mode" value="all" {{ ($access->allowed_tools === null) ? 'checked' : '' }} data-tools-mode="{{ $access->id }}"> كل الأدوات المفعّلة</label>
                                                        <label><input type="radio" name="tools_mode" value="selected" {{ ($access->allowed_tools !== null) ? 'checked' : '' }} data-tools-mode="{{ $access->id }}"> أدوات محددة</label>
                                                    </div>
                                                </div>
                                                <div class="mcp-tool-picks" data-tools-picks="{{ $access->id }}" @if ($access->allowed_tools === null) hidden @endif>
                                                    @foreach ($toolsByCategory as $category => $categoryTools)
                                                        <h4>{{ $categories[$category] ?? $category }}</h4>
                                                        @foreach ($categoryTools as $tool)
                                                            <label>
                                                                <input type="checkbox" name="allowed_tools[]" value="{{ $tool->name() }}" {{ ($access->allowsTool($tool->name())) ? 'checked' : '' }}>
                                                                <code>{{ $tool->name() }}</code>
                                                            </label>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                                <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ</button></div>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================ الأدوات --}}
    @if ($tab === 'tools')
        @php $writeTools = $tools->filter(fn ($tool) => $tool->isWriteTool()); @endphp
        <form method="POST" action="{{ route('dev-superadmin.mcp.tools.update') }}">
            @csrf
            @method('PUT')
            <section class="panel">
                <div class="panel-body">
                    <div class="mcp-write">
                        <div>
                            <h3>أدوات الإضافة والتعديل</h3>
                            <p>مفتاح عام فوق كل الموظفين: عند الإغلاق لا تظهر أي أداة كتابة لأحد، حتى من مستواه «قراءة وإضافة».
                                @if ($writeTools->isEmpty()) لا توجد أدوات كتابة في هذا الإصدار بعد؛ كل الأدوات الحالية للقراءة فقط. @else عدد أدوات الكتابة: {{ $writeTools->count() }}. @endif
                            </p>
                        </div>
                        <label class="switch" title="تفعيل أدوات الكتابة">
                            <input type="hidden" name="write_enabled" value="0">
                            <input type="checkbox" name="write_enabled" value="1" {{ ($settings['write_enabled']) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    @foreach ($categories as $category => $label)
                        @continue(!isset($toolsByCategory[$category]))
                        <div class="mcp-category">
                            <div class="mcp-category-head">
                                <h3>{{ $label }} <small class="text-muted">({{ $toolsByCategory[$category]->count() }})</small></h3>
                            </div>
                            <div class="mcp-tool-grid">
                                @foreach ($toolsByCategory[$category] as $tool)
                                    @php $on = !in_array($tool->name(), $settings['disabled_tools'], true); @endphp
                                    <article class="mcp-tool {{ $on ? '' : 'is-off' }}" data-tool-card>
                                        <div class="mcp-tool-head">
                                            <code>{{ $tool->name() }}</code>
                                            <label class="switch" title="تفعيل {{ $tool->name() }}">
                                                <input type="checkbox" name="enabled_tools[]" value="{{ $tool->name() }}" {{ ($on) ? 'checked' : '' }} data-tool-switch>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <p>{{ $shortDescription($tool) }}</p>
                                        <div class="mcp-tool-meta">
                                            <span class="badge {{ $tool->isWriteTool() ? 'warn' : 'neutral' }}">{{ $tool->isWriteTool() ? 'كتابة' : 'قراءة' }}</span>
                                            <span>الصلاحية من: <code>{{ $tool->gateRouteName() ?? 'أي توكن صالح' }}</code></span>
                                            @isset($toolCalls)<span>· {{ number_format($toolCalls[$tool->name()] ?? 0) }} استدعاء / 30 يوماً</span>@endisset
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="mcp-sticky-save">
                <div class="mcp-row-actions" style="justify-content:flex-start">
                    <button class="btn btn-small" type="button" data-tools-all="1">تفعيل الكل</button>
                    <button class="btn btn-small" type="button" data-tools-all="0">إيقاف الكل</button>
                </div>
                <button class="btn btn-primary" type="submit">حفظ الأدوات</button>
            </div>
        </form>
    @endif

    {{-- ================================================================ الاتصالات --}}
    @if ($tab === 'connections' && $tablesReady)
        <section class="panel">
            <div class="panel-head"><div><h2>إصدار توكن يدوي</h2><p class="muted">لـ Codex وClaude Code وأي برنامج لا يدعم تسجيل الدخول. claude.ai وChatGPT لا يحتاجان توكناً.</p></div></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.mcp.tokens.store') }}" class="mcp-form-row">
                    @csrf
                    <div class="field">
                        <label>الموظف <span class="required">*</span></label>
                        <select name="admin_id" required>
                            <option value="">— اختر —</option>
                            @foreach ($admins as $admin)
                                <option value="{{ $admin->id }}" {{ ((string) old('admin_id') === (string) $admin->id) ? 'selected' : '' }}>{{ $admin->name }} — {{ $admin->email }}{{ $admin->branch_id ? '' : ' (كل الفروع)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>المدة بالأيام</label>
                        <input type="number" name="days" min="0" max="3650" placeholder="{{ $settings['token_days'] ?: 'بلا انتهاء' }}" value="{{ old('days') }}">
                    </div>
                    <div class="field">
                        <label>اسم التوكن <span class="required">*</span></label>
                        <input type="text" name="name" maxlength="120" required placeholder="Codex - لابتوب أحمد" value="{{ old('name') }}">
                    </div>
                    <button class="btn btn-primary" type="submit">إصدار</button>
                </form>
                @if ($selectedMode)
                    <p class="help" style="margin-top:10px">الموظف يُضاف تلقائياً إلى تبويب الموظفين بمستوى «قراءة» إن لم يكن مضافاً.</p>
                @endif
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div><h2>الاتصالات</h2><p class="muted">ربط OAuth يتجدد كل ساعة، فيظهر فعّالاً ما دام قابلاً للتجديد.</p></div>
                <div class="mcp-row-actions">
                    <a class="btn btn-small {{ request('show') !== 'all' ? 'btn-primary' : '' }}" href="{{ route('dev-superadmin.mcp.index', ['tab' => 'connections']) }}">الفعّالة</a>
                    <a class="btn btn-small {{ request('show') === 'all' ? 'btn-primary' : '' }}" href="{{ route('dev-superadmin.mcp.index', ['tab' => 'connections', 'show' => 'all']) }}">الكل</a>
                </div>
            </div>
            <div class="panel-body">
                @if ($tokens->isEmpty())
                    <div class="mcp-empty">لا توجد اتصالات {{ request('show') === 'all' ? '' : 'فعّالة' }}.</div>
                @else
                    <div class="table-wrap" style="margin-top:0">
                        <table>
                            <thead><tr><th>#</th><th>الموظف</th><th>البرنامج / الاسم</th><th>النوع</th><th>آخر استخدام</th><th>ينتهي</th><th>الحالة</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($tokens as $token)
                                @php
                                    $suspended = ($token->admin->status ?? 'active') === 'suspended';
                                    $renewable = $token->oauth_client_id && $token->refresh_expires_at?->isFuture();
                                    $state = $token->revoked_at ? 'ملغي' : ((!$token->isActive() && !$renewable) ? 'منتهي' : ($suspended ? 'الموظف موقوف' : 'فعّال'));
                                @endphp
                                <tr>
                                    <td>{{ $token->id }}</td>
                                    <td>{{ $token->admin->name ?? ('#' . $token->admin_id) }}</td>
                                    <td>{{ $token->oauthClient->client_name ?? $token->name }}</td>
                                    <td><span class="badge {{ $token->oauth_client_id ? 'info' : 'neutral' }}">{{ $token->oauth_client_id ? 'تسجيل دخول' : 'توكن يدوي' }}</span></td>
                                    <td dir="ltr">{{ $token->last_used_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td dir="ltr">{{ ($token->oauth_client_id ? $token->refresh_expires_at : $token->expires_at)?->format('Y-m-d') ?? 'بلا انتهاء' }}</td>
                                    <td><span class="badge {{ $state === 'فعّال' ? 'ok' : 'bad' }}">{{ $state }}</span></td>
                                    <td>
                                        @if (!$token->revoked_at)
                                            <form method="POST" action="{{ route('dev-superadmin.mcp.tokens.revoke', $token) }}" onsubmit="return confirm('إلغاء الاتصال #{{ $token->id }}؟ البرنامج الذي يستخدمه سيتوقف فوراً.')">
                                                @csrf
                                                <button class="btn btn-danger btn-small" type="submit">إلغاء</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>التطبيقات المسجلة (OAuth)</h2><p class="muted">claude.ai وChatGPT وClaude Code تسجل نفسها تلقائياً. إلغاء تطبيق يقطع كل اتصالاته ويمنع استخدامه مجدداً.</p></div></div>
            <div class="panel-body">
                @if ($clients->isEmpty())
                    <div class="mcp-empty">لم يسجّل أي تطبيق بعد.</div>
                @else
                    <div class="table-wrap" style="margin-top:0">
                        <table>
                            <thead><tr><th>التطبيق</th><th>رابط الرجوع</th><th>سُجّل</th><th>اتصالات فعّالة</th><th>آخر استخدام</th><th>الحالة</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($clients as $client)
                                @php
                                    $stat = $clientStats[$client->id] ?? null;
                                    $hosts = collect($client->redirect_uris)->map(fn ($uri) => parse_url($uri, PHP_URL_HOST))->unique()->implode('، ');
                                @endphp
                                <tr>
                                    <td><b>{{ $client->client_name ?: 'بدون اسم' }}</b><br><small class="text-muted" dir="ltr">{{ \Illuminate\Support\Str::limit($client->client_id, 26) }}</small></td>
                                    <td dir="ltr">{{ $hosts }}</td>
                                    <td dir="ltr">{{ $client->created_at?->format('Y-m-d') }}</td>
                                    <td>{{ (int) ($stat->active ?? 0) }}</td>
                                    <td dir="ltr">{{ $stat?->last_used ? \Illuminate\Support\Carbon::parse($stat->last_used)->format('Y-m-d H:i') : '—' }}</td>
                                    <td><span class="badge {{ $client->revoked_at ? 'bad' : 'ok' }}">{{ $client->revoked_at ? 'ملغي' : 'نشط' }}</span></td>
                                    <td>
                                        @if (!$client->revoked_at)
                                            <form method="POST" action="{{ route('dev-superadmin.mcp.clients.revoke', $client) }}" onsubmit="return confirm('إلغاء {{ $client->client_name ?: 'التطبيق' }} وقطع كل اتصالاته؟')">
                                                @csrf
                                                <button class="btn btn-danger btn-small" type="submit">إلغاء التطبيق</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================ السجل --}}
    @if ($tab === 'logs' && $tablesReady)
        <section class="panel">
            <div class="panel-head">
                <div><h2>سجل الاستدعاءات</h2><p class="muted">الأرقام الحساسة في المدخلات مخفية. يُحذف ما هو أقدم من {{ $settings['audit_retention_days'] ?: '∞' }} يوماً تلقائياً.</p></div>
                <a class="btn btn-small" href="{{ route('dev-superadmin.mcp.logs.export', request()->only(['admin_id', 'tool', 'status', 'from', 'to'])) }}">تصدير CSV</a>
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('dev-superadmin.mcp.index') }}" class="mcp-filters">
                    <input type="hidden" name="tab" value="logs">
                    <div class="field">
                        <label>الموظف</label>
                        <select name="admin_id">
                            <option value="">الكل</option>
                            @foreach ($filterAdmins as $id => $name)
                                <option value="{{ $id }}" {{ ((string) request('admin_id') === (string) $id) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>الأداة</label>
                        <select name="tool">
                            <option value="">الكل</option>
                            @foreach ($filterTools as $name)
                                <option value="{{ $name }}" {{ (request('tool') === $name) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>النتيجة</label>
                        <select name="status">
                            <option value="">الكل</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" {{ (request('status') === $value) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>من</label><input type="date" name="from" value="{{ request('from') }}"></div>
                    <div class="field"><label>إلى</label><input type="date" name="to" value="{{ request('to') }}"></div>
                    <div class="mcp-row-actions">
                        <button class="btn btn-primary" type="submit">تصفية</button>
                        <a class="btn" href="{{ route('dev-superadmin.mcp.index', ['tab' => 'logs']) }}">مسح</a>
                    </div>
                </form>

                <div style="margin-top:16px">
                    @include('admin.superadmin.partials.mcp-log-table', ['rows' => $logs, 'names' => $logAdmins])
                </div>

                @if ($logs->hasPages())
                    <div class="mcp-pager">
                        <span>صفحة {{ $logs->currentPage() }} من {{ $logs->lastPage() }} — {{ number_format($logs->total()) }} سجل</span>
                        <div class="mcp-row-actions">
                            @if ($logs->previousPageUrl())<a class="btn btn-small" href="{{ $logs->previousPageUrl() }}">السابق</a>@endif
                            @if ($logs->nextPageUrl())<a class="btn btn-small" href="{{ $logs->nextPageUrl() }}">التالي</a>@endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================ الإعدادات --}}
    @if ($tab === 'settings')
        <section class="panel">
            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.mcp.update') }}" class="mcp-settings">
                    @csrf
                    @method('PUT')

                    <div class="mcp-fieldset">
                        <h3>التشغيل</h3>
                        <p>عند إغلاق الخادم يرجع <code dir="ltr">{{ $endpoint }}</code> خطأ 404، والاتصالات تبقى محفوظة وتعود عند التشغيل.</p>
                        <div class="mcp-switches">
                            <div class="switch-row">
                                <span><b>تشغيل خادم MCP</b></span>
                                <label class="switch"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" {{ (old('enabled', $settings['enabled'])) ? 'checked' : '' }}><span class="slider"></span></label>
                            </div>
                            <div class="switch-row">
                                <span><b>الربط بتسجيل الدخول (OAuth)</b> — مطلوب لـ claude.ai وChatGPT</span>
                                <label class="switch"><input type="hidden" name="oauth_enabled" value="0"><input type="checkbox" name="oauth_enabled" value="1" {{ (old('oauth_enabled', $settings['oauth_enabled'])) ? 'checked' : '' }}><span class="slider"></span></label>
                            </div>
                        </div>
                    </div>

                    <div class="mcp-fieldset">
                        <h3>من يستخدم المساعد</h3>
                        <p>في الوضعين تبقى صلاحيات الشاشات هي الحد الأعلى، ومستوى الكتابة لا يُمنح إلا من تبويب الموظفين.</p>
                        <div class="mcp-radio-cards">
                            <label class="mcp-radio-card">
                                <input type="radio" name="access_mode" value="selected" {{ (old('access_mode', $settings['access_mode']) === 'selected') ? 'checked' : '' }}>
                                <b>الموظفون المحددون فقط (موصى به)</b>
                                <span>لا يستخدم المساعد إلا من تضيفه في تبويب الموظفين. الربط من claude.ai أو ChatGPT يُرفض لغيرهم برسالة واضحة.</span>
                            </label>
                            <label class="mcp-radio-card">
                                <input type="radio" name="access_mode" value="all" {{ (old('access_mode', $settings['access_mode']) === 'all') ? 'checked' : '' }}>
                                <b>كل موظف لديه توكن صالح</b>
                                <span>أي موظف يستطيع الربط للقراءة. تبويب الموظفين يُستخدم لإيقاف أشخاص بعينهم أو منح الكتابة.</span>
                            </label>
                        </div>
                    </div>

                    <div class="mcp-fieldset">
                        <h3>الحماية والمدد</h3>
                        <p>هذه الشاشة هي المصدر الوحيد لإعدادات المساعد؛ لا يُقرأ شيء من ملف البيئة.</p>
                        <div class="switch-row" style="margin-bottom:14px">
                            <span><b>إخفاء أرقام الجواز والهوية والجوال</b> — يظهر آخر 4 أرقام فقط</span>
                            <label class="switch"><input type="hidden" name="mask_sensitive" value="0"><input type="checkbox" name="mask_sensitive" value="1" {{ (old('mask_sensitive', $settings['mask_sensitive'])) ? 'checked' : '' }}><span class="slider"></span></label>
                        </div>
                        <div class="mcp-inputs">
                            <div class="field">
                                <label>حد الطلبات في الدقيقة لكل اتصال</label>
                                <input type="number" name="rate_limit_per_minute" min="1" max="1000" required value="{{ old('rate_limit_per_minute', $settings['rate_limit_per_minute']) }}">
                            </div>
                            <div class="field">
                                <label>مدة التوكن الافتراضية (يوم، 0 = بلا انتهاء)</label>
                                <input type="number" name="token_days" min="0" max="3650" required value="{{ old('token_days', $settings['token_days']) }}">
                            </div>
                            <div class="field">
                                <label>الاحتفاظ بالسجل (يوم، 0 = دائماً)</label>
                                <input type="number" name="audit_retention_days" min="0" max="3650" value="{{ old('audit_retention_days', $settings['audit_retention_days']) }}">
                            </div>
                            <div class="field">
                                <label>حد الإضافة عبر المساعد (عملية/ساعة لكل موظف)</label>
                                <input type="number" name="write_limit_per_hour" min="1" max="1000" value="{{ old('write_limit_per_hour', $settings['write_limit_per_hour']) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mcp-fieldset">
                        <h3>صفحة «المساعد الذكي»</h3>
                        <p>صفحة إرشادية تظهر في القائمة الجانبية داخل «إعدادات النظام» لكل الموظفين، ولو لم يُفعَّل لهم المساعد؛ وتخبر غير المفعّل بذلك. عرضها لا يفتح المساعد لأحد. الإخفاء يزيل الرابط ويغلق الصفحة، ولا يؤثر على عمل المساعد نفسه.</p>
                        <div class="mcp-switches">
                            <label class="switch-row">
                                <span>إظهار الصفحة للموظفين</span>
                                <input type="hidden" name="guide_page_enabled" value="0">
                                <input type="checkbox" name="guide_page_enabled" value="1" @checked($settings['guide_page_enabled'])>
                            </label>
                        </div>
                    </div>

                    <div class="mcp-fieldset">
                        <h3>رسائل العملاء عبر المساعد</h3>
                        <p>تعمل بها أداة <code>send_customer_message</code>. موافقة الموظف داخل ClickERP إلزامية لكل رسالة حتى لو أُوقف الخيار التالي.</p>
                        <div class="mcp-inputs">
                            <div class="field">
                                <label>أقصى عدد عملاء في الرسالة الواحدة</label>
                                <input type="number" name="message_max_recipients" min="1" max="500" value="{{ old('message_max_recipients', $settings['message_max_recipients']) }}">
                            </div>
                            <div class="field">
                                <label>أقصى عدد رسائل في الساعة لكل موظف</label>
                                <input type="number" name="messages_per_hour" min="1" max="5000" value="{{ old('messages_per_hour', $settings['messages_per_hour']) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mcp-fieldset">
                        <h3>موافقة الموظف على الإضافة</h3>
                        <p>عند التفعيل لا يُنفَّذ أي طلب إضافة إلا بعد أن يفتح الموظف رابطاً ويوافق من حسابه في ClickERP. يحمي من نص خبيث داخل شكوى أو ملاحظة يدفع النموذج إلى التأكيد. الإيقاف يعيد التأكيد داخل المحادثة.</p>
                        <div class="mcp-switches">
                            <label class="switch-row">
                                <span>موافقة الموظف داخل ClickERP</span>
                                <input type="hidden" name="human_confirmation" value="0">
                                <input type="checkbox" name="human_confirmation" value="1" @checked($settings['human_confirmation'])>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ الإعدادات</button></div>
                </form>
            </div>
        </section>
    @endif

    {{-- ================================================================ الاشتراك --}}
    @if ($tab === 'subscription')
        @php
            $isPaid = old('subscription_mode', $settings['subscription_mode']) === \App\Support\McpSubscription::MODE_PAID;
            $selectedAlerts = array_map('intval', (array) old('subscription_alert_admins', $settings['subscription_alert_admins']));
        @endphp
        <section class="stats mcp-stats">
            <div class="stat mcp-stat {{ $subscription['allows_write'] ? '' : ($subscription['allows_read'] ? 'is-warn' : 'is-bad') }}">
                <div class="stat-label">الحالة</div>
                <div class="stat-value">{{ $subscription['label'] }}</div>
                <div class="mcp-stat-hint">{{ $subscription['allows_write'] ? 'كل الأدوات تعمل' : ($subscription['allows_read'] ? 'القراءة فقط' : 'الخدمة متوقفة') }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">الأيام المتبقية</div>
                <div class="stat-value">{{ $subscription['days_left'] === null ? 'بلا حد' : max(0, $subscription['days_left']) }}</div>
                <div class="mcp-stat-hint">حتى تاريخ النهاية</div>
            </div>
            <div class="stat">
                <div class="stat-label">تاريخ النهاية</div>
                <div class="stat-value" dir="ltr" style="text-align:right">{{ $subscription['ends_at'] ?? '—' }}</div>
                <div class="mcp-stat-hint">يوم عمل كامل</div>
            </div>
            <div class="stat">
                <div class="stat-label">نهاية فترة السماح</div>
                <div class="stat-value" dir="ltr" style="text-align:right">{{ $subscription['grace_ends_at'] ?? '—' }}</div>
                <div class="mcp-stat-hint">بعدها تتوقف الخدمة</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>نوع الخدمة ومدتها</h2><p class="muted">مستقل عن اشتراك النظام وعن زر تشغيل الخادم. الخدمة تعمل عندما يكون الخادم مفعّلاً والاشتراك ساريًا.</p></div></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.mcp.subscription.update') }}" class="mcp-settings" id="mcpSubscriptionForm">
                    @csrf
                    @method('PUT')
                    <div class="mcp-radio-cards">
                        <label class="mcp-radio-card">
                            <input type="radio" name="subscription_mode" value="free" @checked(!$isPaid)>
                            <b>مجاني</b>
                            <span>يعمل دون تواريخ أو قيود حتى تغيّره.</span>
                        </label>
                        <label class="mcp-radio-card">
                            <input type="radio" name="subscription_mode" value="paid" @checked($isPaid)>
                            <b>مدفوع</b>
                            <span>يعمل بين تاريخي البداية والنهاية، ثم فترة سماح للقراءة فقط، ثم يتوقف.</span>
                        </label>
                    </div>

                    <div class="mcp-fieldset" data-paid-only @if (!$isPaid) hidden @endif>
                        <h3>مدة الاشتراك</h3>
                        <p>عند التجديد غيّر تاريخ النهاية فقط؛ تعود الخدمة فورًا دون إعادة ربط.</p>
                        <div class="mcp-inputs">
                            <div class="field">
                                <label>تاريخ البداية</label>
                                <input type="date" name="subscription_starts_at" value="{{ old('subscription_starts_at', $settings['subscription_starts_at']) }}">
                            </div>
                            <div class="field">
                                <label>تاريخ النهاية</label>
                                <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $settings['subscription_ends_at']) }}">
                            </div>
                            <div class="field">
                                <label>فترة السماح (يوم، 0 = بلا سماح)</label>
                                <input type="number" name="subscription_grace_days" min="0" max="90" required value="{{ old('subscription_grace_days', $settings['subscription_grace_days']) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mcp-fieldset" data-paid-only @if (!$isPaid) hidden @endif>
                        <h3>من يصله تنبيه الانتهاء</h3>
                        <p>إشعار داخل النظام قبل النهاية بـ 14 و3 أيام، وأول يوم في فترة السماح، وعند التوقف. يمكن اختيار أكثر من موظف.</p>
                        <div class="field">
                            <select name="subscription_alert_admins[]" multiple size="6">
                                @foreach ($alertAdmins as $alertAdmin)
                                    <option value="{{ $alertAdmin->id }}" @selected(in_array($alertAdmin->id, $selectedAlerts, true))>{{ $alertAdmin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label>ملاحظات (رقم الفاتورة، الباقة...)</label>
                        <input type="text" name="subscription_notes" maxlength="1000" value="{{ old('subscription_notes', $settings['subscription_notes']) }}">
                    </div>

                    <div class="form-actions"><button class="btn btn-primary" type="submit">حفظ الاشتراك</button></div>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head"><div><h2>سجل التغييرات</h2><p class="muted">كل تغيير في نوع الخدمة أو تواريخها، للرجوع إليه عند التجديد.</p></div></div>
            <div class="panel-body">
                @if ($subscriptionEvents->isEmpty())
                    <div class="mcp-empty">لا توجد تغييرات بعد.</div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>وقت التغيير</th><th>النوع</th><th>من</th><th>إلى</th><th>السماح</th><th>ملاحظات</th><th>IP</th></tr></thead>
                            <tbody>
                                @foreach ($subscriptionEvents as $event)
                                    <tr>
                                        <td dir="ltr">{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $event->mode === 'paid' ? 'مدفوع' : 'مجاني' }}</td>
                                        <td dir="ltr">{{ $event->starts_at ?? '—' }}</td>
                                        <td dir="ltr">{{ $event->ends_at ?? '—' }}</td>
                                        <td>{{ $event->grace_days }}</td>
                                        <td>{{ $event->notes ?? '—' }}</td>
                                        <td dir="ltr">{{ $event->ip }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
        <script>
            (function () {
                var form = document.getElementById('mcpSubscriptionForm');
                form.addEventListener('change', function (event) {
                    if (event.target.name !== 'subscription_mode') { return; }
                    form.querySelectorAll('[data-paid-only]').forEach(function (section) {
                        section.hidden = event.target.value !== 'paid';
                    });
                });
            })();
        </script>
    @endif

    {{-- ================================================================ طريقة الربط --}}
    @if ($tab === 'connect')
        <section class="panel">
            <div class="panel-head"><div><h2>رابط الخادم</h2><p class="muted">نفس الرابط لكل البرامج.</p></div></div>
            <div class="panel-body">
                <div class="mcp-token">
                    <code id="mcpEndpoint">{{ $endpoint }}</code>
                    <button class="btn btn-primary" type="button" data-copy="#mcpEndpoint">نسخ</button>
                </div>
                @unless ($settings['oauth_enabled'])
                    <div class="alert alert-warning" style="margin-top:14px">claude.ai وChatGPT لن يعملا حتى تفعّل «الربط بتسجيل الدخول» من تبويب الإعدادات.</div>
                @endunless
                @if ($selectedMode)
                    <div class="alert alert-info" style="margin-top:14px">وضع «المحددون فقط»: أضف الموظف في تبويب الموظفين قبل أن يربط، وإلا يُرفض الربط.</div>
                @endif
            </div>
        </section>

        <div class="mcp-connect-grid">
            <div class="mcp-connect-card">
                <h3><span class="badge info">OAuth</span> claude.ai وتطبيق Claude</h3>
                <ol>
                    <li>Settings ← Connectors ← <b>Add custom connector</b> (في Team/Enterprise يضيفه مالك المنظمة).</li>
                    <li>الاسم <code>ClickERP</code> والرابط أعلاه، ثم <b>Add</b>.</li>
                    <li><b>Connect</b> ← صفحة دخول ClickERP ← «موافقة وربط».</li>
                    <li>في المحادثة فعّل ClickERP من قائمة الأدوات واسأل.</li>
                </ol>
            </div>
            <div class="mcp-connect-card">
                <h3><span class="badge info">OAuth</span> ChatGPT</h3>
                <ol>
                    <li>Settings ← Apps &amp; Connectors ← Advanced ← فعّل <b>Developer mode</b> (حسب باقة الحساب).</li>
                    <li><b>Create</b>: الاسم <code>ClickERP</code> والرابط أعلاه.</li>
                    <li>المصادقة <b>OAuth</b> ← Create ← دخول ClickERP وموافقة.</li>
                    <li>في المحادثة اختر ClickERP من زر (+).</li>
                </ol>
            </div>
            <div class="mcp-connect-card">
                <h3><span class="badge info">OAuth</span> <span class="badge neutral">توكن</span> Claude Code</h3>
                <p class="help">الأسهل — الإضافة (Plugin)، وعند السؤال عن الرابط الصق الرابط أعلاه:</p>
                <pre class="command-output mcp-snippet">/plugin marketplace add Clickandmore/clickerp-claude-plugin
/plugin install clickerp@clickerp
/mcp</pre>
                <p class="help">أو أمر واحد ثم <code dir="ltr">/mcp</code> ← Authenticate:</p>
                <pre class="command-output mcp-snippet">claude mcp add --transport http clickerp {{ $endpoint }}</pre>
                <p class="help">أو بتوكن يدوي:</p>
                <pre class="command-output mcp-snippet">claude mcp add --transport http clickerp {{ $endpoint }} \
  --header "Authorization: Bearer cerp_mcp_..."</pre>
            </div>
            <div class="mcp-connect-card">
                <h3><span class="badge neutral">توكن</span> Codex</h3>
                <p class="help">1. أصدر توكناً من تبويب الاتصالات، ثم أضف إلى <code dir="ltr">~/.codex/config.toml</code>:</p>
                <pre class="command-output mcp-snippet">[mcp_servers.clickerp]
url = "{{ $endpoint }}"
bearer_token_env_var = "CLICKERP_TOKEN"</pre>
                <p class="help">2. في الطرفية: <code dir="ltr">export CLICKERP_TOKEN=cerp_mcp_...</code> ثم شغّل Codex.</p>
            </div>
        </div>
    @endif
</main>

<script>
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-copy'));
            navigator.clipboard.writeText(target.textContent.trim()).then(function () { button.textContent = 'تم النسخ'; });
        });
    });

    document.querySelectorAll('[data-tool-switch]').forEach(function (input) {
        input.addEventListener('change', function () {
            input.closest('[data-tool-card]').classList.toggle('is-off', !input.checked);
        });
    });

    document.querySelectorAll('[data-tools-all]').forEach(function (button) {
        button.addEventListener('click', function () {
            var on = button.getAttribute('data-tools-all') === '1';
            document.querySelectorAll('[data-tool-switch]').forEach(function (input) {
                input.checked = on;
                input.dispatchEvent(new Event('change'));
            });
        });
    });

    document.querySelectorAll('[data-tools-mode]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var picks = document.querySelector('[data-tools-picks="' + radio.getAttribute('data-tools-mode') + '"]');
            picks.hidden = radio.value !== 'selected';
        });
    });
</script>
</body>
</html>
