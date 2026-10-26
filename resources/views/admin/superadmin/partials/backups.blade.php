@php
    $dbConnected = (bool) ($currentDatabaseDetails['connected'] ?? false);
    $riyadh = fn ($timestamp) => \Carbon\Carbon::createFromTimestamp($timestamp, 'Asia/Riyadh');

    // نوع النسخة يُستنتج من بادئة اسم الملف
    $backupType = function (string $filename, string $fallback) {
        return match (true) {
            str_starts_with($filename, 'clickerp-pre-import-') => ['أمان قبل الاستيراد', 'is-warning'],
            str_starts_with($filename, 'clickerp-pre-restore-') => ['أمان قبل الاستعادة', 'is-warning'],
            str_starts_with($filename, 'clickerp-scheduled-') => ['مجدولة تلقائيًا', 'is-info'],
            str_starts_with($filename, 'clickerp-uploaded-') => ['مرفوعة يدويًا', 'is-navy'],
            default => [$fallback, ''],
        };
    };

    $latestBackupAt = collect($databaseBackups)->merge($localDatabaseBackups)->max('created_at');
    $backupIcons = [
        'database' => '<ellipse cx="12" cy="6" rx="7.5" ry="3"/><path d="M4.5 6v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3V6"/><path d="M4.5 12v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3v-6"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.2V12l3.2 2"/>',
        'cloud' => '<path d="M7 18.5h10.2a4.3 4.3 0 00.6-8.6 6 6 0 00-11.5 1.6A3.5 3.5 0 007 18.5z"/>',
        'server' => '<rect x="3.5" y="4" width="17" height="7" rx="2"/><rect x="3.5" y="13" width="17" height="7" rx="2"/><path d="M7.5 7.5h.01M7.5 16.5h.01"/>',
        'download' => '<path d="M12 4v11M7.5 10.5L12 15l4.5-4.5"/><path d="M4.5 19.5h15"/>',
        'shield' => '<path d="M12 3l7.5 3.4v5.1c0 4.6-3.1 7.9-7.5 9.3-4.4-1.4-7.5-4.7-7.5-9.3V6.4L12 3z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.6 1.6 0 00-2.7 1.1V21a2 2 0 11-4 0v-.1a1.6 1.6 0 00-2.7-1.1l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.6 1.6 0 003 14.2H3a2 2 0 110-4h.1a1.6 1.6 0 001.1-2.7l-.1-.1a2 2 0 112.8-2.8l.1.1A1.6 1.6 0 009.8 3.6V3a2 2 0 114 0v.1a1.6 1.6 0 002.7 1.1l.1-.1a2 2 0 112.8 2.8l-.1.1a1.6 1.6 0 001.1 2.7H21a2 2 0 110 4h-.1a1.6 1.6 0 00-1.5 1.3z"/>',
        'plug' => '<path d="M9 3v5M15 3v5M6.5 8h11v3a5.5 5.5 0 01-11 0V8zM12 16.5V21"/>',
        'alert' => '<path d="M12 4l9 16H3l9-16z"/><path d="M12 10v4.5M12 17.5h.01"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V7.5a3.5 3.5 0 017 0v3"/>',
    ];
    $icon = fn (string $name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $backupIcons[$name] . '</svg>';
@endphp

<div class="bk" id="backups">
    {{-- ======================= شريط الإجراءات ======================= --}}
    <section class="bk-toolbar">
        <div>
            <h2>إجراءات سريعة</h2>
            <p>«تنزيل نسخة جديدة» يصدّر القاعدة الحالية مباشرة إلى جهازك.</p>
        </div>
        <div class="bk-toolbar-actions">
            @if (!$r2Ready)
                <a class="btn" href="{{ route('storage.settings') }}">{!! $icon('cloud') !!} إعدادات R2</a>
            @endif
            <form method="POST" action="{{ route('dev-superadmin.database-backups.create') }}">
                @csrf
                <button class="btn" type="submit">{!! $icon($r2Ready ? 'cloud' : 'server') !!} {{ $r2Ready ? 'إنشاء نسخة في R2' : 'إنشاء نسخة محلية' }}</button>
            </form>
            <form method="POST" action="{{ route('dev-superadmin.database-backups.download-current') }}">
                @csrf
                <button class="btn btn-primary" type="submit">{!! $icon('download') !!} تنزيل نسخة جديدة</button>
            </form>
        </div>
    </section>

    {{-- ======================= بطاقات الملخص ======================= --}}
    <section class="bk-kpis" aria-label="ملخص النسخ الاحتياطية">
        <article class="bk-kpi {{ $dbConnected ? 'is-good' : 'is-bad' }}">
            <span class="bk-kpi-icon">{!! $icon('database') !!}</span>
            <div class="bk-kpi-body">
                <span class="bk-kpi-label">قاعدة البيانات الحالية</span>
                <strong class="bk-kpi-value" dir="ltr">{{ $currentDatabaseDetails['connected_database'] ?? ($currentDatabaseDetails['database'] ?? '—') }}</strong>
                <span class="bk-kpi-meta">
                    <i class="bk-dot"></i>{{ $dbConnected ? 'متصلة' : 'غير متصلة' }}
                    · <bdi dir="ltr">{{ $currentDatabaseDetails['host'] ?? '—' }}:{{ $currentDatabaseDetails['port'] ?? '—' }}</bdi>
                    · MySQL <bdi dir="ltr">{{ $currentDatabaseDetails['server_version'] ?? '—' }}</bdi>
                </span>
            </div>
        </article>

        <article class="bk-kpi {{ $backupSettings['enabled'] ? 'is-good' : 'is-bad' }}">
            <span class="bk-kpi-icon">{!! $icon('clock') !!}</span>
            <div class="bk-kpi-body">
                <span class="bk-kpi-label">النسخ التلقائي</span>
                <strong class="bk-kpi-value">{{ $backupSettings['enabled'] ? 'يوميًا ' . $backupSettings['daily_time'] : 'متوقف' }}</strong>
                <span class="bk-kpi-meta">آخر نسخة: <bdi dir="ltr">{{ $latestBackupAt ? $riyadh($latestBackupAt)->format('Y-m-d H:i') : '—' }}</bdi></span>
            </div>
        </article>

        <article class="bk-kpi {{ $r2Ready ? 'is-good' : 'is-bad' }}">
            <span class="bk-kpi-icon">{!! $icon('cloud') !!}</span>
            <div class="bk-kpi-body">
                <span class="bk-kpi-label">التخزين السحابي R2</span>
                <strong class="bk-kpi-value">{{ $r2Ready ? count($databaseBackups) . ' نسخة' : 'غير جاهز' }}</strong>
                <span class="bk-kpi-meta">الاحتفاظ {{ $backupSettings['retention_days'] }} يوم · <bdi dir="ltr">{{ $backupSettings['path'] }}</bdi></span>
            </div>
        </article>

        <article class="bk-kpi {{ $backupSettings['local_enabled'] ? 'is-good' : '' }}">
            <span class="bk-kpi-icon">{!! $icon('server') !!}</span>
            <div class="bk-kpi-body">
                <span class="bk-kpi-label">نسخ السيرفر المحلية</span>
                <strong class="bk-kpi-value">{{ count($localDatabaseBackups) }} نسخة</strong>
                <span class="bk-kpi-meta">{{ $backupSettings['local_enabled'] ? 'الاحتفاظ ' . $backupSettings['local_retention_days'] . ' يوم' : 'الحفظ المحلي متوقف' }}</span>
            </div>
        </article>
    </section>

    @if (!empty($currentDatabaseDetails['error']))
        <div class="alert alert-danger bk-alert">{{ $currentDatabaseDetails['error'] }}</div>
    @endif
    @if ($backupListError)
        <div class="alert alert-danger bk-alert">تعذر قراءة قائمة النسخ: {{ $backupListError }}</div>
    @endif

    <div class="bk-layout">
        {{-- ======================= العمود الرئيسي: القوائم ======================= --}}
        <div class="bk-main">
            <section class="panel">
                <div class="panel-head">
                    <div class="bk-head-title">
                        <span class="bk-head-icon">{!! $icon('cloud') !!}</span>
                        <div>
                            <h2 class="panel-title">نسخ R2 السحابية <span class="bk-count">{{ count($databaseBackups) }}</span></h2>
                            <p class="panel-subtitle">الأحدث أولًا — الوقت بتوقيت الرياض (24 ساعة). الاستعادة والحذف يتطلبان رمز تحقق.</p>
                        </div>
                    </div>
                </div>

                @if ($r2Ready || count($databaseBackups) > 0)
                    <div class="bk-table-wrap">
                        <table class="bk-table">
                            <thead><tr><th>النسخة</th><th>التاريخ</th><th>الحجم</th><th class="bk-col-actions">الإجراءات</th></tr></thead>
                            <tbody>
                            @forelse ($databaseBackups as $backup)
                                @php
                                    [$typeLabel, $typeTone] = $backupType($backup['filename'], 'يدوية');
                                    $hasChallenge = $backupVerificationChallenge && $backupVerificationChallenge['key'] === $backup['key'];
                                @endphp
                                <tr class="{{ $hasChallenge ? 'is-selected' : '' }}">
                                    <td>
                                        <div class="bk-file">{{ $backup['filename'] }}</div>
                                        <span class="bk-tag {{ $typeTone }}">{{ $typeLabel }}</span>
                                    </td>
                                    <td class="cell-ltr">
                                        <div>{{ $riyadh($backup['created_at'])->format('Y-m-d') }}</div>
                                        <small class="bk-sub">{{ $riyadh($backup['created_at'])->format('H:i:s') }}</small>
                                    </td>
                                    <td class="cell-ltr">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                    <td class="bk-col-actions">
                                        <div class="bk-row-actions">
                                            <a class="btn btn-primary btn-small" href="{{ route('dev-superadmin.database-backups.download', ['key' => $backup['key']]) }}" aria-label="تحميل {{ $backup['filename'] }}">{!! $icon('download') !!} تحميل</a>
                                            @if (count($backupVerificationChannels) > 0)
                                                <details class="bk-menu" {{ $hasChallenge ? 'open' : '' }}>
                                                    <summary class="btn btn-small">استعادة / حذف</summary>
                                                    <form class="bk-menu-body" method="POST" action="{{ route('dev-superadmin.database-backups.verification-code') }}">
                                                        @csrf
                                                        <input type="hidden" name="key" value="{{ $backup['key'] }}">
                                                        <label>إرسال رمز التحقق إلى</label>
                                                        <select name="verification_channel" required aria-label="وسيلة إرسال رمز التحقق">
                                                            @foreach ($backupVerificationChannels as $channel => $details)
                                                                <option value="{{ $channel }}">{{ $details['label'] }} — {{ $details['destination'] }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="bk-menu-actions">
                                                            <button class="btn btn-small" type="submit" name="action" value="restore">طلب رمز الاستعادة</button>
                                                            <button class="btn btn-danger btn-small" type="submit" name="action" value="delete">طلب رمز الحذف</button>
                                                        </div>
                                                    </form>
                                                </details>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                @if ($hasChallenge)
                                    @php $isRestore = $backupVerificationChallenge['action'] === 'restore'; @endphp
                                    <tr class="bk-challenge-row">
                                        <td colspan="4">
                                            <form class="bk-challenge {{ $isRestore ? '' : 'is-danger' }}" method="POST"
                                                  action="{{ $isRestore ? route('dev-superadmin.database-backups.restore') : route('dev-superadmin.database-backups.destroy') }}"
                                                  onsubmit="return confirm('{{ $isRestore ? 'سيتم أولًا أخذ نسخة أمان من البيانات الحالية، ثم استبدال قاعدة البيانات. هل تريد المتابعة؟' : 'سيتم حذف ملف النسخة نهائيًا من R2. هل تريد المتابعة؟' }}')">
                                                @csrf
                                                @unless ($isRestore)
                                                    @method('DELETE')
                                                @endunless
                                                <input type="hidden" name="key" value="{{ $backup['key'] }}">
                                                <div class="bk-challenge-text">
                                                    <strong>{{ $isRestore ? 'تأكيد استعادة هذه النسخة' : 'تأكيد حذف هذه النسخة نهائيًا' }}</strong>
                                                    <span>الرمز مرسل إلى {{ $backupVerificationChallenge['destination'] }} وصالح حتى {{ \Carbon\Carbon::createFromTimestamp($backupVerificationChallenge['expires_at'])->format('H:i') }}.</span>
                                                </div>
                                                <input type="text" name="confirmation" required placeholder="{{ $isRestore ? 'RESTORE' : 'DELETE' }}" autocomplete="off" aria-label="اكتب {{ $isRestore ? 'RESTORE' : 'DELETE' }}">
                                                <input type="text" name="verification_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="رمز التحقق" autocomplete="one-time-code" aria-label="رمز التحقق">
                                                <button class="btn {{ $isRestore ? 'btn-primary' : 'btn-danger' }} btn-small" type="submit">{{ $isRestore ? 'تأكيد الاستعادة' : 'تأكيد الحذف' }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="4"><div class="bk-empty">لا توجد نسخ احتياطية محفوظة في R2 حتى الآن.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="panel-body">
                        <div class="bk-empty">
                            {!! $icon('cloud') !!}
                            <strong>التخزين السحابي غير مفعّل</strong>
                            <span>فعّل R2 واختبر الاتصال لحفظ النسخ خارج السيرفر واستعادتها لاحقًا.</span>
                            <a class="btn btn-small" href="{{ route('storage.settings') }}">فتح إعدادات التخزين</a>
                        </div>
                    </div>
                @endif
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="bk-head-title">
                        <span class="bk-head-icon">{!! $icon('server') !!}</span>
                        <div>
                            <h2 class="panel-title">النسخ المحلية على السيرفر <span class="bk-count">{{ count($localDatabaseBackups) }}</span></h2>
                            <p class="panel-subtitle">محفوظة بشكل خاص داخل <bdi dir="ltr">storage/app/database-backups</bdi>. نسخ الأمان محمية من الحذف.</p>
                        </div>
                    </div>
                </div>

                @if (count($localDatabaseBackups) > 0)
                    <div class="bk-table-wrap">
                        <table class="bk-table">
                            <thead><tr><th>النسخة</th><th>التاريخ</th><th>الحجم</th><th class="bk-col-actions">الإجراءات</th></tr></thead>
                            <tbody>
                            @foreach ($localDatabaseBackups as $backup)
                                @php
                                    [$typeLabel, $typeTone] = $backupType($backup['filename'], 'نسخة محلية');
                                    $isUploaded = str_starts_with($backup['filename'], 'clickerp-uploaded-');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="bk-file">{{ $backup['filename'] }}</div>
                                        <span class="bk-tag {{ $typeTone }}">{{ $typeLabel }}</span>
                                    </td>
                                    <td class="cell-ltr">
                                        <div>{{ $riyadh($backup['created_at'])->format('Y-m-d') }}</div>
                                        <small class="bk-sub">{{ $riyadh($backup['created_at'])->format('H:i:s') }}</small>
                                    </td>
                                    <td class="cell-ltr">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                                    <td class="bk-col-actions">
                                        <div class="bk-row-actions">
                                            <a class="btn btn-small" href="{{ route('dev-superadmin.database-backups.local.download', ['path' => $backup['path']]) }}" aria-label="تحميل {{ $backup['filename'] }}">{!! $icon('download') !!} تحميل</a>
                                            @if ($isUploaded)
                                                <button class="btn btn-small copy-terminal-command" type="button" data-command="{{ $backup['terminal_command'] }}">نسخ أمر Terminal</button>
                                                <details class="bk-menu">
                                                    <summary class="btn btn-danger btn-small">حذف</summary>
                                                    <form class="bk-menu-body" method="POST" action="{{ route('dev-superadmin.database-backups.local.destroy') }}" onsubmit="return confirm('سيُحذف ملف النسخة المحلية المرفوعة نهائيًا. هل تريد المتابعة؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                                        <label>اكتب DELETE وكلمة المرور للتأكيد</label>
                                                        <input type="text" name="confirmation" required placeholder="DELETE" autocomplete="off" aria-label="اكتب DELETE" dir="ltr">
                                                        <input type="password" name="current_password" required placeholder="كلمة مرور Super Admin" autocomplete="current-password" aria-label="كلمة مرور Super Admin الحالية">
                                                        <button class="btn btn-danger btn-small" type="submit">حذف النسخة نهائيًا</button>
                                                    </form>
                                                </details>
                                            @else
                                                <span class="bk-protected">{!! $icon('lock') !!} محمية</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="panel-body">
                        <div class="bk-empty">
                            {!! $icon('server') !!}
                            <strong>لا توجد نسخ محلية بعد</strong>
                            <span>ستظهر هنا النسخ المجدولة ونسخ الأمان والملفات المرفوعة يدويًا.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        {{-- ======================= العمود الجانبي: الإعدادات ======================= --}}
        <aside class="bk-side">
            <section class="panel">
                <div class="panel-head">
                    <div class="bk-head-title">
                        <span class="bk-head-icon">{!! $icon('settings') !!}</span>
                        <div>
                            <h2 class="panel-title">الجدولة والتخزين</h2>
                            <p class="panel-subtitle">توقيت النسخ التلقائي ومدد الاحتفاظ.</p>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('dev-superadmin.database-backups.settings') }}" class="bk-form">
                        @csrf
                        @method('PUT')
                        <label class="bk-toggle">
                            <span>
                                <strong>النسخ التلقائي اليومي</strong>
                                <small>إنشاء نسخة كل يوم في الوقت المحدد</small>
                            </span>
                            <span class="switch">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $backupSettings['enabled']) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </span>
                        </label>
                        <label class="bk-toggle">
                            <span>
                                <strong>نسخة خاصة على السيرفر</strong>
                                <small>الاحتفاظ بنسخة محلية إضافة إلى R2</small>
                            </span>
                            <span class="switch">
                                <input type="hidden" name="local_enabled" value="0">
                                <input type="checkbox" name="local_enabled" value="1" {{ old('local_enabled', $backupSettings['local_enabled']) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </span>
                        </label>
                        <div class="bk-form-grid">
                            <div class="field">
                                <label for="backupDailyTime">وقت التنفيذ</label>
                                <input id="backupDailyTime" type="time" name="daily_time" required value="{{ old('daily_time', $backupSettings['daily_time']) }}">
                            </div>
                            <div class="field">
                                <label for="backupPath">المجلد داخل R2</label>
                                <input id="backupPath" type="text" name="path" dir="ltr" required value="{{ old('path', $backupSettings['path']) }}">
                            </div>
                            <div class="field">
                                <label for="backupRetention">الاحتفاظ في R2 (يوم)</label>
                                <input id="backupRetention" type="number" name="retention_days" min="1" max="365" required value="{{ old('retention_days', $backupSettings['retention_days']) }}">
                            </div>
                            <div class="field">
                                <label for="localBackupRetention">الاحتفاظ محليًا (يوم)</label>
                                <input id="localBackupRetention" type="number" name="local_retention_days" min="1" max="30" required value="{{ old('local_retention_days', $backupSettings['local_retention_days']) }}">
                            </div>
                        </div>
                        <p class="backup-status {{ $r2Ready ? 'is-ready' : 'is-off' }}">
                            {{ $r2Ready ? 'اتصال R2 جاهز للحفظ والاستعادة.' : 'R2 غير جاهز. فعّله واختبر الاتصال من إعدادات التخزين أولًا.' }}
                            <a href="{{ route('storage.settings') }}">إعدادات التخزين</a>
                        </p>
                        <button class="btn btn-primary" type="submit">حفظ الإعداد</button>
                    </form>
                </div>
            </section>

            <details class="panel bk-collapse">
                <summary class="panel-head">
                    <div class="bk-head-title">
                        <span class="bk-head-icon">{!! $icon('shield') !!}</span>
                        <div>
                            <h2 class="panel-title">أمان Super Admin</h2>
                            <p class="panel-subtitle">جهات استلام رموز التحقق وكلمة المرور.</p>
                        </div>
                    </div>
                </summary>
                <div class="panel-body">
                    <p class="bk-note">تُحفظ القيم في ملف خاص مشفّر ومستقل عن قاعدة البيانات.</p>
                    <form method="POST" action="{{ route('dev-superadmin.database-backups.security') }}" class="bk-form" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <div class="field">
                            <label for="superAdminVerificationEmail">بريد التحقق</label>
                            <input id="superAdminVerificationEmail" type="email" name="verification_email" maxlength="190" value="{{ old('verification_email', $devSuperAdminSecurity['email']) }}" placeholder="security@example.com" autocomplete="email" dir="ltr">
                        </div>
                        <div class="field">
                            <label for="superAdminVerificationPhone">جوال التحقق</label>
                            <input id="superAdminVerificationPhone" type="tel" name="verification_phone" value="{{ old('verification_phone', $devSuperAdminSecurity['phone']) }}" placeholder="05xxxxxxxx" dir="ltr" autocomplete="tel">
                        </div>
                        <div class="field">
                            <label for="superAdminCurrentPassword">كلمة المرور الحالية <span class="required">*</span></label>
                            <input id="superAdminCurrentPassword" type="password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="bk-form-grid">
                            <div class="field">
                                <label for="superAdminNewPassword">كلمة مرور جديدة</label>
                                <input id="superAdminNewPassword" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="اختياري">
                            </div>
                            <div class="field">
                                <label for="superAdminNewPasswordConfirmation">تأكيدها</label>
                                <input id="superAdminNewPasswordConfirmation" type="password" name="password_confirmation" minlength="12" autocomplete="new-password">
                            </div>
                        </div>
                        <p class="bk-note">يجب إبقاء بريد أو جوال واحد على الأقل. كلمة المرور الجديدة لا تقل عن 12 حرفًا وتحتوي حرفًا كبيرًا وصغيرًا ورقمًا ورمزًا خاصًا. عند تغييرها سيتم تسجيل خروجك تلقائيًا.</p>
                        <button class="btn btn-primary" type="submit" onclick="return confirm('سيتم تحديث إعدادات أمان Super Admin. هل تريد المتابعة؟')">حفظ إعدادات الأمان</button>
                    </form>
                </div>
            </details>

            <details class="panel bk-collapse">
                <summary class="panel-head">
                    <div class="bk-head-title">
                        <span class="bk-head-icon">{!! $icon('plug') !!}</span>
                        <div>
                            <h2 class="panel-title">تفاصيل اتصال .env</h2>
                            <p class="panel-subtitle">بيانات الاتصال الفعلية بقاعدة MySQL.</p>
                        </div>
                    </div>
                </summary>
                <dl class="bk-dl">
                    <div><dt>DB_CONNECTION</dt><dd>{{ $currentDatabaseDetails['driver'] ?? '—' }}</dd></div>
                    <div><dt>DB_HOST</dt><dd>{{ $currentDatabaseDetails['host'] ?? '—' }}</dd></div>
                    <div><dt>DB_PORT</dt><dd>{{ $currentDatabaseDetails['port'] ?? '—' }}</dd></div>
                    <div><dt>DB_DATABASE</dt><dd>{{ $currentDatabaseDetails['database'] ?? '—' }}</dd></div>
                    <div><dt>DB_USERNAME</dt><dd>{{ $currentDatabaseDetails['username'] ?? '—' }}</dd></div>
                    <div><dt>DB_PASSWORD</dt><dd>{{ ($currentDatabaseDetails['password_configured'] ?? false) ? '•••••••• (مخفية)' : 'غير مضبوطة' }}</dd></div>
                    <div><dt>القاعدة المؤكدة من MySQL</dt><dd>{{ $currentDatabaseDetails['connected_database'] ?? '—' }}</dd></div>
                    <div><dt>إصدار الخادم</dt><dd>{{ $currentDatabaseDetails['server_version'] ?? '—' }}</dd></div>
                    <div><dt>ترميز الاتصال</dt><dd>{{ $currentDatabaseDetails['charset'] ?? '—' }}</dd></div>
                </dl>
            </details>
        </aside>
    </div>

    {{-- ======================= منطقة الخطر: الاستيراد ======================= --}}
    <section class="panel bk-danger-zone">
        <div class="panel-head">
            <div class="bk-head-title">
                <span class="bk-head-icon">{!! $icon('alert') !!}</span>
                <div>
                    <h2 class="panel-title">استيراد قاعدة بيانات</h2>
                    <p class="panel-subtitle">يستبدل البيانات الحالية بالكامل. تُحفظ نسخة أمان تلقائيًا قبل الاستبدال.</p>
                </div>
            </div>
            <span class="bk-tag is-danger">عملية حساسة</span>
        </div>

        <div class="panel-body">
            <div class="bk-steps">
                <div class="bk-step">
                    <span class="bk-step-num">1</span>
                    <div class="bk-step-body">
                        <h3>اختر الملف واتصال السيرفر</h3>
                        <div class="field">
                            <label for="databaseFileInput">ملف قاعدة البيانات <bdi dir="ltr">SQL / SQL.GZ</bdi></label>
                            <input id="databaseFileInput" type="file" accept=".sql,.gz,application/sql,application/gzip" aria-label="اختر ملف قاعدة البيانات لإنشاء أمر الاستيراد">
                        </div>
                        <div class="field">
                            <label for="databaseSshConnection">اتصال السيرفر</label>
                            <input id="databaseSshConnection" type="text" dir="ltr" value="ssh {{ config('database.backup_ssh.target') }}" placeholder="ssh clickadmin@130.110.98.217">
                        </div>
                        <span id="terminalSelectedFile" class="terminal-selected-file">لم يتم اختيار ملف بعد.</span>
                        <p class="bk-note">اختيار الملف لا يرفعه تلقائيًا؛ هو فقط يولّد الأوامر.</p>
                    </div>
                </div>

                <div class="bk-step">
                    <span class="bk-step-num">2</span>
                    <div class="bk-step-body">
                        <h3>نفّذ أمر الرفع على جهازك</h3>
                        <code id="databaseUploadCommand" class="terminal-command">اختر الملف لإنشاء أمر الرفع.</code>
                        <div class="terminal-command-actions">
                            <button id="copyDatabaseUploadCommand" class="btn btn-small" type="button" disabled>نسخ أمر الرفع</button>
                        </div>
                        <p class="bk-note">يبحث الأمر عن الملف في المجلد الحالي وDownloads وDesktop وDocuments، وإن لم يجده يطلب مساره، ثم ينقله إلى مجلد النسخ المحلية بصلاحية مستخدم التطبيق.</p>
                    </div>
                </div>

                <div class="bk-step">
                    <span class="bk-step-num">3</span>
                    <div class="bk-step-body">
                        <h3>نفّذ أمر الاستيراد داخل جلسة SSH</h3>
                        <code id="databaseTerminalCommandPrefix" class="terminal-command">اختر الملف لإنشاء أمر الاستيراد بعد الرفع.</code>
                        <div class="terminal-command-actions">
                            <button id="copyDatabaseTerminalPrefix" class="btn btn-small" type="button" disabled>نسخ أمر الاستيراد</button>
                        </div>
                        <p class="bk-note">اكتب <bdi dir="ltr">IMPORT</bdi> عند طلب التأكيد في Terminal.</p>
                    </div>
                </div>
            </div>

            <details class="settings-disclosure">
                <summary>طريقة بديلة: الرفع والاستيراد من المتصفح</summary>
                <div class="settings-disclosure-content">
                    <form id="databaseImportForm" class="verification-confirm" method="POST" enctype="multipart/form-data" action="{{ route('dev-superadmin.database-backups.import') }}">
                        @csrf
                        <input id="databaseUploadFileInput" type="file" name="database_file" required accept=".sql,.gz,application/sql,application/gzip" aria-label="ملف قاعدة البيانات">
                        <input type="text" name="confirmation" required placeholder="IMPORT" autocomplete="off" aria-label="اكتب IMPORT">
                        <input type="password" name="current_password" required placeholder="كلمة مرور Super Admin" autocomplete="current-password" aria-label="كلمة مرور Super Admin الحالية">
                        <button id="databaseImportButton" class="btn btn-danger" type="submit">رفع واستيراد القاعدة</button>
                    </form>
                    <p class="bk-note">الحد داخل التطبيق {{ number_format(config('database.backup_import_max_kb', 512000) / 1024, 0) }} MB، وقد يفرض PHP حدًا أقل.</p>
                    <div id="databaseImportProgress" class="import-progress" hidden>
                        <div class="import-progress-track" role="progressbar" aria-label="تقدم رفع قاعدة البيانات" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div class="import-progress-bar"></div>
                        </div>
                        <p class="import-progress-text">جاري رفع الملف: 0%</p>
                    </div>
                    <div id="databaseImportMessage" class="import-progress-message" hidden></div>
                </div>
            </details>

            <details class="settings-disclosure bk-disclosure-danger">
                <summary>خيار متقدم وخطر: تفريغ القاعدة قبل الاستيراد</summary>
                <div class="settings-disclosure-content">
                    <p class="bk-danger-text">ينشئ نسخة أمان ثم يحذف كل الجداول والـViews من <bdi dir="ltr">{{ $currentDatabaseDetails['database'] ?? '—' }}</bdi>.</p>
                    <code class="terminal-command">{{ $databaseEmptyCommand }}</code>
                    <div class="terminal-command-actions">
                        <button class="btn btn-danger btn-small copy-terminal-command" type="button" data-command="{{ $databaseEmptyCommand }}">نسخ أمر التفريغ</button>
                        <a class="btn btn-small" href="http://127.0.0.1:8001/dev-super-admin/recovery" target="_blank" rel="noopener">فتح شاشة الإنقاذ 8001</a>
                    </div>
                    <p class="bk-note">بعد التفريغ لن يعمل النظام على 8000 حتى يكتمل الاستيراد. شاشة 8001 مستقلة ولا تستعيد هذه القاعدة تلقائيًا.</p>
                </div>
            </details>
        </div>
    </section>
</div>
