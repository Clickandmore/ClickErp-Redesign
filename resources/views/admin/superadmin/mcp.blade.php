<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'خادم MCP — ClickERP'])
    <style>
        .sa-recovery select,
        .sa-recovery input[type="number"] {
            width: 100%; min-height: 44px; border: 1px solid var(--border-strong); border-radius: var(--r-sm);
            padding: 9px 13px; font: inherit; font-size: .89rem; background: #fff; box-shadow: var(--shadow-xs);
        }
        .sa-recovery .check-row { display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--text); }
        .sa-recovery .check-row input { width: 18px; height: 18px; }
        .sa-recovery .form-actions { margin-top: 16px; display: flex; gap: 10px; }
        .mcp-token { display: flex; gap: 10px; align-items: center; margin-top: 10px; }
        .mcp-token code { flex: 1; direction: ltr; text-align: left; padding: 12px 14px; border-radius: var(--r-sm); background: #0d1a2b; color: #a7f3d0; font: .85rem var(--mono); overflow-x: auto; white-space: nowrap; }
        .mcp-snippet { direction: ltr; text-align: left; margin: 8px 0 16px; }
        @media (max-width: 760px) { .sa-recovery .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="sa-recovery">
@php
    $statusLabels = ['ok' => 'نجح', 'denied' => 'بلا صلاحية', 'error' => 'خطأ', 'unknown' => 'أداة غير موجودة'];
    $activeTokens = $tokens->filter(fn ($token) => $token->isActive())->count();
@endphp
<main class="shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1>خادم MCP</h1>
                <p class="muted">ربط مساعدات الذكاء الاصطناعي (Codex، Claude) ببيانات النظام للقراءة فقط، بصلاحيات موظف محدد.</p>
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
            <h2>إعدادات MCP</h2>
            <p>المساعد يرى ما يراه الموظف صاحب التوكن فقط، داخل فرعه، وكل استدعاء يُسجَّل.</p>
        </div>
        <div class="page-heading-meta">
            <span class="heading-chip {{ $settings['enabled'] ? 'is-good' : 'is-bad' }}">
                الخادم: <b>{{ $settings['enabled'] ? 'مفعّل' : 'مغلق' }}</b>
            </span>
            <span class="heading-chip">توكنات فعّالة: <b>{{ $activeTokens }}</b></span>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if (!$tablesReady)
        <div class="alert alert-danger">
            جداول MCP غير موجودة. شغّل الرقعة المعلقة من
            <a href="{{ route('dev-superadmin.recovery') }}">الاستعادة والفحص</a> ثم أعد تحميل الصفحة.
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
                    <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcpPlainToken').textContent).then(() => this.textContent = 'تم النسخ')">نسخ</button>
                </div>
            </div>
        </section>
    @endif

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>الإعدادات</h2>
                <p class="muted">القيم المحفوظة هنا تتقدم على ملف البيئة (.env).</p>
            </div>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('dev-superadmin.mcp.update') }}">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <label class="check-row">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" {{ old('enabled', $settings['enabled']) ? 'checked' : '' }}>
                        تفعيل خادم MCP
                    </label>
                    <label class="check-row">
                        <input type="hidden" name="mask_sensitive" value="0">
                        <input type="checkbox" name="mask_sensitive" value="1" {{ old('mask_sensitive', $settings['mask_sensitive']) ? 'checked' : '' }}>
                        إخفاء أرقام الجواز والجوال (آخر 4 أرقام فقط)
                    </label>
                    <label>
                        حد الطلبات في الدقيقة لكل توكن
                        <input type="number" name="rate_limit_per_minute" min="1" max="1000" required value="{{ old('rate_limit_per_minute', $settings['rate_limit_per_minute']) }}">
                    </label>
                    <label>
                        مدة صلاحية التوكن الافتراضية (يوم، 0 = بلا انتهاء)
                        <input type="number" name="token_days" min="0" max="3650" required value="{{ old('token_days', $settings['token_days']) }}">
                    </label>
                </div>
                <p class="help">عند الإغلاق يرجع المسار <code dir="ltr">{{ $endpoint }}</code> خطأ 404 لكل الطلبات، والتوكنات تبقى محفوظة.</p>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </section>

    @if ($tablesReady)
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>إصدار توكن</h2>
                <p class="muted">اختر موظفاً بالصلاحيات التي يحتاجها المساعد فعلاً، لا حساب المدير العام.</p>
            </div>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('dev-superadmin.mcp.tokens.store') }}">
                @csrf
                <div class="form-grid">
                    <label>
                        الموظف
                        <select name="admin_id" required>
                            <option value="">— اختر —</option>
                            @foreach ($admins as $admin)
                                <option value="{{ $admin->id }}" {{ (string) old('admin_id') === (string) $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }} — {{ $admin->email }}{{ $admin->branch_id ? '' : ' (كل الفروع)' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        اسم التوكن
                        <input type="text" name="name" maxlength="120" required placeholder="Codex - لابتوب أحمد" value="{{ old('name') }}">
                    </label>
                    <label>
                        الصلاحية بالأيام (فارغ = {{ $settings['token_days'] ?: 'بلا انتهاء' }})
                        <input type="number" name="days" min="0" max="3650" value="{{ old('days') }}">
                    </label>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">إصدار التوكن</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>التوكنات</h2>
                <p class="muted">إيقاف الموظف يوقف توكناته تلقائياً.</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($tokens->isEmpty())
                <p class="help">لا توجد توكنات بعد.</p>
            @else
                <div class="table-wrap" style="margin-top:0">
                    <table>
                        <thead><tr><th>#</th><th>الموظف</th><th>الاسم</th><th>آخر استخدام</th><th>ينتهي</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($tokens as $token)
                            @php
                                $suspended = ($token->admin->status ?? 'active') === 'suspended';
                                $state = $token->revoked_at ? 'ملغي' : (!$token->isActive() ? 'منتهي' : ($suspended ? 'الموظف موقوف' : 'فعّال'));
                            @endphp
                            <tr>
                                <td>{{ $token->id }}</td>
                                <td>{{ $token->admin->name ?? ('#' . $token->admin_id) }}</td>
                                <td>{{ $token->name }}</td>
                                <td dir="ltr">{{ $token->last_used_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td dir="ltr">{{ $token->expires_at?->format('Y-m-d') ?? 'بلا انتهاء' }}</td>
                                <td><span class="badge {{ $state === 'فعّال' ? 'ok' : 'bad' }}">{{ $state }}</span></td>
                                <td>
                                    @if (!$token->revoked_at)
                                        <form method="POST" action="{{ route('dev-superadmin.mcp.tokens.revoke', $token) }}" onsubmit="return confirm('إلغاء التوكن #{{ $token->id }}؟ أي مساعد يستخدمه سيتوقف فوراً.')">
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
        <div class="panel-head">
            <div>
                <h2>طريقة الربط</h2>
                <p class="muted">استبدل <code dir="ltr">cerp_mcp_...</code> بالتوكن الذي أصدرته.</p>
            </div>
        </div>
        <div class="panel-body">
            <p class="help">Claude Code:</p>
            <pre class="command-output mcp-snippet">claude mcp add --transport http clickerp {{ $endpoint }} \
  --header "Authorization: Bearer cerp_mcp_..."</pre>
            <p class="help">Codex — في <code dir="ltr">~/.codex/config.toml</code> ثم <code dir="ltr">export CLICKERP_TOKEN=cerp_mcp_...</code>:</p>
            <pre class="command-output mcp-snippet">[mcp_servers.clickerp]
url = "{{ $endpoint }}"
bearer_token_env_var = "CLICKERP_TOKEN"</pre>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>آخر الاستدعاءات</h2>
                <p class="muted">آخر 50 استدعاء أداة من سجل التدقيق.</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($logs->isEmpty())
                <p class="help">لم يُستدعَ الخادم بعد.</p>
            @else
                <div class="table-wrap" style="margin-top:0">
                    <table>
                        <thead><tr><th>الوقت</th><th>الموظف</th><th>الأداة</th><th>المدخلات</th><th>النتيجة</th><th>المدة</th></tr></thead>
                        <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td dir="ltr">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $logAdmins[$log->admin_id] ?? ('#' . $log->admin_id) }}</td>
                                <td><code dir="ltr">{{ $log->tool }}</code></td>
                                <td dir="ltr"><code>{{ \Illuminate\Support\Str::limit(json_encode($log->arguments ?: new \stdClass(), JSON_UNESCAPED_UNICODE), 80) }}</code></td>
                                <td>
                                    <span class="badge {{ $log->status === 'ok' ? 'ok' : 'bad' }}" @if ($log->error) title="{{ $log->error }}" @endif>
                                        {{ $statusLabels[$log->status] ?? $log->status }}
                                    </span>
                                </td>
                                <td dir="ltr">{{ $log->duration_ms }} ms</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
    @endif
</main>
</body>
</html>
