<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'استعادة نظام ClickERP'])
</head>
<body class="sa-recovery">
<main class="shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1>طبقة استعادة ClickERP</h1>
                <p class="muted">صفحة مستقلة عن جداول النظام، مخصصة لفحص الاتصال وإصلاح البنية قبل دخول لوحة الإدارة.</p>
            </div>
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

    <div class="page-heading">
        <div>
            <h2>الاستعادة والفحص</h2>
            <p>حالة الاتصال، الجداول الأساسية، والرقع المعلقة — كلها في شاشة واحدة.</p>
        </div>
        <div class="page-heading-meta">
            <span class="heading-chip {{ $database['connected'] ? 'is-good' : 'is-bad' }}">
                قاعدة البيانات: <b>{{ $database['connected'] ? 'متصلة' : 'غير متصلة' }}</b>
            </span>
            <span class="heading-chip {{ $missingTableCount === 0 ? 'is-good' : 'is-bad' }}">
                جداول مفقودة: <b>{{ $missingTableCount }}</b>
            </span>
            <span class="heading-chip {{ $migrationStatus['pending']->isEmpty() ? 'is-good' : 'is-bad' }}">
                رقع معلقة: <b>{{ $migrationStatus['pending']->count() }}</b>
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>حالة قاعدة البيانات</h2>
                <p class="muted">هذه البيانات تُقرأ مباشرة من الاتصال المحدد في إعدادات البيئة.</p>
            </div>
        </div>
        <div class="panel-body">
            <div class="status-grid">
                <div class="status-card"><span>الاتصال</span><strong class="badge {{ $database['connected'] ? 'ok' : 'bad' }}">{{ $database['connected'] ? 'متصل' : 'غير متصل' }}</strong></div>
                <div class="status-card"><span>Driver</span><strong>{{ $database['driver'] ?: '—' }}</strong></div>
                <div class="status-card"><span>قاعدة البيانات المستهدفة</span><strong><code>{{ $database['name'] ?: 'غير محددة' }}</code></strong></div>
            </div>
            @if ($database['error'])
                <div class="alert alert-danger" style="margin-top:16px">{{ $database['error'] }}</div>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>فحص الجداول الأساسية</h2>
                <p class="muted">الجداول المفقودة: {{ $missingTableCount }}</p>
            </div>
            <span class="badge {{ $missingTableCount === 0 ? 'ok' : 'bad' }}">
                {{ $missingTableCount === 0 ? 'البنية سليمة' : $missingTableCount . ' جدول مفقود' }}
            </span>
        </div>
        <div class="panel-body">
            <div class="table-wrap" style="margin-top:0">
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
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>رقع قاعدة البيانات</h2>
                <p class="muted">يمكنك الرجوع لهذه الصفحة وتشغيل أي رقع جديدة معلقة. الرقع المطبقة لن تُشغّل مرة ثانية حمايةً للبيانات.</p>
            </div>
        </div>
        <div class="panel-body">
            <div class="status-grid">
                <div class="status-card"><span>إجمالي ملفات الرقع</span><strong>{{ $migrationStatus['total'] }}</strong></div>
                <div class="status-card"><span>الرقع المسجلة كمطبقة</span><strong>{{ $migrationStatus['applied'] }}</strong></div>
                <div class="status-card"><span>الرقع المعلقة</span><strong class="badge {{ $migrationStatus['pending']->isEmpty() ? 'ok' : 'bad' }}">{{ $migrationStatus['pending']->count() }}</strong></div>
            </div>

            @if ($migrationStatus['pending']->isNotEmpty())
                <div class="table-wrap">
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
                <div class="alert" style="margin-top:16px">قاعدة البيانات لا تحتوي على رقع migrations معلقة.</div>
            @endif
        </div>
    </section>

    <section class="panel danger-zone">
        <div class="panel-head">
            <div>
                <h2>تشغيل الرقع المعلقة</h2>
                <p class="muted">يشغّل فقط migrations غير المطبقة على قاعدة البيانات المعروضة أعلاه. لا ينفذ rollback أو fresh ولا يمسح الجداول.</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($structuralDrift->isNotEmpty())
                <div class="alert alert-danger">
                    <strong>تعذر تشغيل الرقع بسبب عدم تطابق بنيوي.</strong>
                    سجل <code>migrations</code> يعتبر رقع إنشاء الجداول التالية مطبقة، لكن الجداول نفسها مفقودة:
                    <code>{{ $structuralDrift->pluck('table')->implode(', ') }}</code>.
                    استعد هذه الجداول وبياناتها من آخر نسخة احتياطية سليمة، ثم أعد تحميل الصفحة. تشغيل migrations أو إنشاء جداول فارغة لن يستعيد بيانات العملاء والإعدادات المحذوفة.
                </div>
            @elseif ($database['connected'])
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
