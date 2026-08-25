<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>استعادة نظام ClickERP</title>
    <style>
        :root { --bg:#eef3f5; --surface:#fff; --border:#d7e1e7; --text:#172033; --muted:#65758b; --accent:#0f766e; --danger:#991b1b; --danger-bg:#fef2f2; --success:#166534; --success-bg:#f0fdf4; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Tahoma,Arial,sans-serif; }
        .shell { width:min(1040px,calc(100% - 32px)); margin:32px auto; display:grid; gap:16px; }
        .topbar,.panel { background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:0 12px 30px rgba(23,32,51,.06); }
        .topbar { padding:18px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
        h1,h2,p { margin-top:0; }
        h1 { margin-bottom:6px; font-size:1.35rem; }
        h2 { margin-bottom:6px; font-size:1.05rem; }
        .muted { margin-bottom:0; color:var(--muted); line-height:1.8; }
        .actions { display:flex; flex-wrap:wrap; gap:8px; }
        .btn { min-height:40px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:9px; padding:0 14px; background:#fff; color:var(--text); font:inherit; font-weight:800; text-decoration:none; cursor:pointer; }
        .btn-primary { border-color:var(--accent); background:var(--accent); color:#fff; }
        .btn-danger { border-color:#fecaca; color:var(--danger); }
        .panel-head { padding:16px 18px; border-bottom:1px solid var(--border); }
        .panel-body { padding:18px; }
        .status-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .status-card { padding:14px; border:1px solid var(--border); border-radius:11px; background:#f8fafc; }
        .status-card span { display:block; margin-bottom:7px; color:var(--muted); font-size:.8rem; }
        .status-card strong { overflow-wrap:anywhere; }
        .badge { display:inline-flex; min-height:27px; align-items:center; padding:0 10px; border-radius:999px; font-size:.78rem; font-weight:900; }
        .ok { color:var(--success); background:var(--success-bg); }
        .bad { color:var(--danger); background:var(--danger-bg); }
        .table-wrap { overflow:auto; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:11px 12px; border-bottom:1px solid var(--border); text-align:right; white-space:nowrap; }
        th { color:var(--muted); font-size:.82rem; }
        code { direction:ltr; unicode-bidi:embed; }
        .alert { padding:13px 15px; border:1px solid #bbf7d0; border-radius:10px; color:var(--success); background:var(--success-bg); white-space:pre-wrap; line-height:1.7; }
        .alert-danger { border-color:#fecaca; color:var(--danger); background:var(--danger-bg); }
        .danger-zone { border-color:#fecaca; }
        .danger-zone .panel-head { background:var(--danger-bg); border-radius:14px 14px 0 0; }
        .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        label { display:grid; gap:7px; color:#334155; font-size:.86rem; font-weight:800; }
        input { width:100%; min-height:44px; border:1px solid #cbd5e1; border-radius:9px; padding:0 12px; font:inherit; background:#fff; }
        .help { color:var(--muted); font-size:.82rem; line-height:1.7; }
        .command-output { margin-top:12px; direction:ltr; text-align:left; max-height:300px; overflow:auto; padding:14px; border-radius:10px; background:#0f172a; color:#d1fae5; white-space:pre-wrap; font:12px/1.7 monospace; }
        @media (max-width:760px) { .topbar { align-items:flex-start; flex-direction:column; } .status-grid,.form-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div>
            <h1>طبقة استعادة ClickERP</h1>
            <p class="muted">صفحة مستقلة عن جداول النظام، مخصصة لفحص الاتصال وإصلاح البنية قبل دخول لوحة الإدارة.</p>
        </div>
        <div class="actions">
            @if ($database['connected'] && $missingTableCount === 0)
                <a class="btn" href="{{ route('dev-superadmin.modules') }}">إدارة الموديولات</a>
            @endif
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <div class="panel-head">
            <h2>حالة قاعدة البيانات</h2>
            <p class="muted">هذه البيانات تُقرأ مباشرة من الاتصال المحدد في إعدادات البيئة.</p>
        </div>
        <div class="panel-body">
            <div class="status-grid">
                <div class="status-card"><span>الاتصال</span><strong class="badge {{ $database['connected'] ? 'ok' : 'bad' }}">{{ $database['connected'] ? 'متصل' : 'غير متصل' }}</strong></div>
                <div class="status-card"><span>Driver</span><strong>{{ $database['driver'] ?: '—' }}</strong></div>
                <div class="status-card"><span>قاعدة البيانات المستهدفة</span><strong><code>{{ $database['name'] ?: 'غير محددة' }}</code></strong></div>
            </div>
            @if ($database['error'])
                <div class="alert alert-danger" style="margin-top:12px">{{ $database['error'] }}</div>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>فحص الجداول الأساسية</h2>
            <p class="muted">الجداول المفقودة: {{ $missingTableCount }}</p>
        </div>
        <div class="panel-body table-wrap">
            <table>
                <thead><tr><th>المكوّن</th><th>الجدول</th><th>الحالة</th></tr></thead>
                <tbody>
                @foreach ($tables as $table)
                    <tr>
                        <td>{{ $table['label'] }}</td>
                        <td><code>{{ $table['table'] }}</code></td>
                        <td>
                            @if ($table['exists'] === null)
                                <span class="badge bad">تعذر الفحص</span>
                            @elseif ($table['exists'])
                                <span class="badge ok">موجود</span>
                            @else
                                <span class="badge bad">مفقود</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>رقع قاعدة البيانات</h2>
            <p class="muted">يمكنك الرجوع لهذه الصفحة وتشغيل أي رقع جديدة معلقة. الرقع المطبقة لن تُشغّل مرة ثانية حمايةً للبيانات.</p>
        </div>
        <div class="panel-body">
            <div class="status-grid">
                <div class="status-card"><span>إجمالي ملفات الرقع</span><strong>{{ $migrationStatus['total'] }}</strong></div>
                <div class="status-card"><span>الرقع المسجلة كمطبقة</span><strong>{{ $migrationStatus['applied'] }}</strong></div>
                <div class="status-card"><span>الرقع المعلقة</span><strong class="badge {{ $migrationStatus['pending']->isEmpty() ? 'ok' : 'bad' }}">{{ $migrationStatus['pending']->count() }}</strong></div>
            </div>

            @if ($migrationStatus['pending']->isNotEmpty())
                <div class="table-wrap" style="margin-top:12px">
                    <table>
                        <thead><tr><th>الرقع المعلقة</th></tr></thead>
                        <tbody>
                        @foreach ($migrationStatus['pending']->take(50) as $migration)
                            <tr><td><code>{{ $migration }}</code></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($migrationStatus['pending']->count() > 50)
                    <p class="help">يعرض أول 50 رقعة من أصل {{ $migrationStatus['pending']->count() }}.</p>
                @endif
            @else
                <div class="alert" style="margin-top:12px">قاعدة البيانات لا تحتوي على رقع migrations معلقة.</div>
            @endif
        </div>
    </section>

    <section class="panel danger-zone">
        <div class="panel-head">
            <h2>تشغيل الرقع المعلقة</h2>
            <p class="muted">يشغّل فقط migrations غير المطبقة على قاعدة البيانات المعروضة أعلاه. لا ينفذ rollback أو fresh ولا يمسح الجداول.</p>
        </div>
        <div class="panel-body">
            @if ($database['connected'])
                <form method="POST" action="{{ route('dev-superadmin.recovery.migrate') }}">
                    @csrf
                    <div class="form-grid">
                        <label>
                            اكتب عبارة التأكيد: <code>{{ $migrationConfirmation }}</code>
                            <input type="text" name="confirmation" value="{{ old('confirmation') }}" autocomplete="off" required>
                        </label>
                        <label>
                            كلمة مرور Super Admin
                            <input type="password" name="current_password" autocomplete="current-password" required>
                        </label>
                    </div>
                    <p class="help">لن يعمل الزر إذا تغير اسم قاعدة البيانات أو كانت عبارة التأكيد أو كلمة المرور غير صحيحة.</p>
                    <button class="btn btn-primary" type="submit" {{ $migrationStatus['pending']->isEmpty() ? 'disabled' : '' }}>تشغيل {{ $migrationStatus['pending']->count() }} رقعة معلقة</button>
                </form>
            @else
                <div class="alert alert-danger">صحّح بيانات الاتصال في ملف البيئة أولًا، ثم أعد تحميل الصفحة.</div>
            @endif

            @if (session('command_output'))
                <pre class="command-output">{{ session('command_output') }}</pre>
            @endif
        </div>
    </section>
</main>
</body>
</html>
