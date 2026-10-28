<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'لوحة Super Admin التطويرية'])
</head>
<body>

@php
    $modules = $modules ?? [];
    $enabledMap = $enabledMap ?? [];
    $configuredMap = $configuredMap ?? $enabledMap;
    $productProfiles = $productProfiles ?? [];
    $productProfileKey = $productProfileKey ?? 'recruitment';
    $dashboards = $dashboards ?? [];
    $defaultDashboardKey = $defaultDashboardKey ?? 'recruitment';
    $adminDesign = $adminDesign ?? 'classic';
    $branchLimit = $branchLimit ?? null;
    $admins = $admins ?? collect();
    $enabledCount = collect($enabledMap)->filter()->count();
    $disabledCount = max(count($modules) - $enabledCount, 0);
    $activeAdminsCount = $admins->filter(fn ($admin) => ($admin->status ?? 'active') !== 'suspended')->count();
    $backupSettings = $backupSettings ?? ['enabled' => true, 'daily_time' => '03:30', 'retention_days' => 30, 'local_enabled' => true, 'local_retention_days' => 7, 'path' => 'database-backups'];
    $databaseBackups = $databaseBackups ?? [];
    $localDatabaseBackups = $localDatabaseBackups ?? [];
    $currentDatabaseDetails = $currentDatabaseDetails ?? [];
    $databaseImportCommandPrefix = $databaseImportCommandPrefix ?? '';
    $databaseImportShell = $databaseImportShell ?? 'terminal';
    $databaseEmptyCommand = $databaseEmptyCommand ?? '';
    $backupListError = $backupListError ?? null;
    $r2Ready = $r2Ready ?? false;
    $schedulerStatus = $schedulerStatus ?? ['active' => false, 'last_heartbeat_at' => null, 'crontab_configured' => null, 'crontab_entries' => [], 'crontab_error' => null, 'recommended_entry' => '', 'jobs' => []];
    $cronJobs = collect($schedulerStatus['jobs']);
    $highRiskCronCount = $cronJobs->where('risk', 'high')->count();
    $invalidCronCount = $cronJobs->where('risk', 'invalid')->count();
    $subscription = $subscription ?? [];
    $activeSection = $activeSection ?? 'modules';
    $maintenanceSettings = $maintenanceSettings ?? \App\Support\DashboardMaintenanceSettings::all();
    $subscriptionStatuses = [
        'active' => 'اشتراك نشط',
        'trial' => 'فترة تجريبية',
        'paused' => 'الاشتراك موقوف',
        'expired' => 'الاشتراك منتهي',
    ];
    $subscriptionStatus = ($subscription['is_expired'] ?? false) ? 'expired' : ($subscription['status'] ?? 'active');
    $sectionMeta = [
        'subscription' => ['title' => 'العميل والاشتراك', 'description' => 'إدارة بيانات المنشأة والباقة وتواريخ الاشتراك.'],
        'modules' => ['title' => 'الموديلات', 'description' => 'تفعيل مكونات النظام واختيار لوحة التحكم الافتراضية.'],
        'employees' => ['title' => 'الموظفون', 'description' => 'البحث عن موظف والدخول إلى لوحة الإدارة بحسابه.'],
        'product-updates' => ['title' => 'تحديثات النظام', 'description' => 'إعلان الميزات والإصلاحات للموظفين بالصور والفيديو، ومتابعة من اطلع عليها، ومزامنة التحديثات بين النسخ.'],
        'backups' => ['title' => 'النسخ الاحتياطية', 'description' => 'إدارة النسخ التلقائية والتنزيل والاستعادة.'],
        'scheduler' => ['title' => 'المهام المجدولة', 'description' => 'متابعة حالة Scheduler والمهام الآلية القادمة.'],
    ][$activeSection] ?? ['title' => 'لوحة التطوير', 'description' => 'إدارة إعدادات النظام.'];

    // أيقونات التنقّل — SVG مضمّن حتى لا تعتمد اللوحة على أي مكتبة خارجية
    $navIcons = [
        'recovery' => '<path d="M12 3l7.5 3.4v5.1c0 4.6-3.1 7.9-7.5 9.3-4.4-1.4-7.5-4.7-7.5-9.3V6.4L12 3z"/><path d="M9.2 12.2l2 2 3.6-3.9"/>',
        'subscription' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="8.6" cy="10.8" r="2"/><path d="M5.1 16.6c.8-1.6 2-2.4 3.5-2.4s2.7.8 3.5 2.4"/><path d="M15 10h4M15 13.4h4"/>',
        'modules' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.8"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.8"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.8"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.8"/>',
        'employees' => '<circle cx="9.5" cy="8.5" r="3.2"/><path d="M3.5 19.4c.7-3 3.1-4.7 6-4.7s5.3 1.7 6 4.7"/><path d="M16.4 5.7a3 3 0 010 5.7M17.6 14.9c2 .6 3.4 2.1 3.9 4.5"/>',
        'product-updates' => '<path d="M12 3.5l2.3 4.8 5.2.7-3.8 3.7.9 5.2-4.6-2.5-4.6 2.5.9-5.2L4.5 9l5.2-.7z"/>',
        'backups' => '<ellipse cx="12" cy="6" rx="7.5" ry="3"/><path d="M4.5 6v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3V6"/><path d="M4.5 12v6c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3v-6"/>',
        'scheduler' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.2V12l3.2 2"/>',
    ];

    $navItems = [
        ['key' => 'recovery', 'label' => 'الاستعادة والفحص', 'url' => route('dev-superadmin.recovery')],
        ['key' => 'subscription', 'label' => 'العميل والاشتراك', 'url' => route('dev-superadmin.subscription')],
        ['key' => 'modules', 'label' => 'الموديلات', 'url' => route('dev-superadmin.modules')],
        ['key' => 'employees', 'label' => 'الموظفون', 'url' => route('dev-superadmin.employees')],
        ['key' => 'product-updates', 'label' => 'تحديثات النظام', 'url' => route('dev-superadmin.product-updates')],
        ['key' => 'backups', 'label' => 'النسخ الاحتياطية', 'url' => route('dev-superadmin.database-backups')],
        ['key' => 'scheduler', 'label' => 'المهام المجدولة', 'url' => route('dev-superadmin.scheduler')],
    ];

    // شرائح ملخّص تظهر في رأس كل قسم لتعطي الحالة قبل النزول للتفاصيل
    $headingChips = match ($activeSection) {
        'subscription' => [
            ['label' => 'الحالة', 'value' => $subscriptionStatuses[$subscriptionStatus] ?? 'غير محدد', 'tone' => in_array($subscriptionStatus, ['expired', 'paused'], true) ? 'is-bad' : 'is-good'],
            ['label' => 'المتبقي', 'value' => ($subscription['days_remaining'] ?? null) === null ? '—' : (($subscription['days_remaining'] < 0) ? 'منتهي' : number_format($subscription['days_remaining']) . ' يوم'), 'tone' => ''],
        ],
        'modules' => [
            ['label' => 'النظام', 'value' => $maintenanceSettings['enabled'] ? 'تحت التحديث' : 'متاح للموظفين', 'tone' => $maintenanceSettings['enabled'] ? 'is-bad' : 'is-good'],
            ['label' => 'مفعّل', 'value' => $enabledCount . ' من ' . count($modules), 'tone' => 'is-good'],
            ['label' => 'مغلق', 'value' => (string) $disabledCount, 'tone' => $disabledCount > 0 ? 'is-bad' : ''],
            ['label' => 'نوع البرنامج', 'value' => $productProfiles[$productProfileKey]['name'] ?? $productProfileKey, 'tone' => ''],
        ],
        'employees' => [
            ['label' => 'نشط', 'value' => (string) $activeAdminsCount, 'tone' => 'is-good'],
            ['label' => 'الإجمالي', 'value' => (string) $admins->count(), 'tone' => ''],
        ],
        'product-updates' => isset($systemUpdateCounts) ? [
            ['label' => 'منشور', 'value' => (string) $systemUpdateCounts['published'], 'tone' => 'is-good'],
            ['label' => 'مسودات', 'value' => (string) $systemUpdateCounts['drafts'], 'tone' => ''],
            ['label' => 'من المصدر', 'value' => (string) $systemUpdateCounts['remote'], 'tone' => ''],
        ] : [],
        'backups' => [
            ['label' => 'R2', 'value' => $r2Ready ? 'جاهز' : 'غير جاهز', 'tone' => $r2Ready ? 'is-good' : 'is-bad'],
            ['label' => 'نسخ محلية', 'value' => (string) count($localDatabaseBackups), 'tone' => ''],
            ['label' => 'قاعدة البيانات', 'value' => ($currentDatabaseDetails['connected'] ?? false) ? 'متصلة' : 'غير متصلة', 'tone' => ($currentDatabaseDetails['connected'] ?? false) ? 'is-good' : 'is-bad'],
        ],
        'scheduler' => [
            ['label' => 'Scheduler', 'value' => $schedulerStatus['active'] ? 'يعمل' : 'متوقف', 'tone' => $schedulerStatus['active'] ? 'is-good' : 'is-bad'],
            ['label' => 'مهام عالية التأثير', 'value' => (string) $highRiskCronCount, 'tone' => $highRiskCronCount > 0 ? 'is-bad' : ''],
            ['label' => 'أوامر غير موجودة', 'value' => (string) $invalidCronCount, 'tone' => $invalidCronCount > 0 ? 'is-bad' : ''],
        ],
        default => [],
    };
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">إدارة حساب العميل</h1>
                <p class="brand-text">الاشتراك، صلاحيات النظام، المستخدمون، والبنية التقنية في مكان واحد.</p>
            </div>
        </div>

        <div class="topbar-actions">
            <a class="btn" href="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}">حسابات اختبار التطبيقات</a>
            <a class="btn btn-primary" href="{{ route('dev-superadmin.hourly-rental-settings.index') }}">إعدادات الساعات</a>
            <a class="btn" href="{{ route('dev-superadmin.moyasar-settings') }}">بوابة ميسر</a>
            <a class="btn" href="{{ route('dev-superadmin.invoice-corrections.index') }}">تصحيح الفواتير</a>
            <a class="btn" href="{{ route('dev-superadmin.mcp.index') }}">MCP</a>
            <a class="btn" href="{{ \App\Support\SystemModuleManager::defaultDashboardUrl() }}">لوحة الإدارة</a>
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    @if (session('success') || (isset($errors) && $errors->any()))
        <div class="alerts">
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
        </div>
    @endif

    <nav class="section-nav" aria-label="أقسام لوحة التحكم">
        @foreach ($navItems as $navItem)
            @php $isActiveNav = $activeSection === $navItem['key']; @endphp
            <a
                class="{{ $isActiveNav ? 'active' : '' }}"
                href="{{ $navItem['url'] }}"
                @if ($isActiveNav) aria-current="page" @endif
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    {!! $navIcons[$navItem['key']] !!}
                </svg>
                {{ $navItem['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="page-heading">
        <div>
            <h2>{{ $sectionMeta['title'] }}</h2>
            <p>{{ $sectionMeta['description'] }}</p>
        </div>

        @if (count($headingChips) > 0)
            <div class="page-heading-meta">
                @foreach ($headingChips as $chip)
                    <span class="heading-chip {{ $chip['tone'] }}">{{ $chip['label'] }}: <b>{{ $chip['value'] }}</b></span>
                @endforeach
            </div>
        @endif
    </div>

    @if ($activeSection === 'subscription')
    <section class="subscription-panel" id="subscription">
        <div class="subscription-overview">
            <p class="eyebrow">ملف العميل</p>
            <h2 class="client-title">{{ $subscription['client_name'] ?: config('app.name') }}</h2>
            <p class="client-code">{{ $subscription['client_code'] ? 'ID: ' . $subscription['client_code'] : 'لم يحدد رقم العميل' }}</p>
            <span class="subscription-state is-{{ $subscriptionStatus }}">
                {{ $subscriptionStatuses[$subscriptionStatus] ?? 'غير محدد' }}
            </span>
            <div class="subscription-plan">
                <small>الباقة الحالية</small>
                <strong>{{ $subscription['plan_name'] ?? 'الخطة الأساسية' }}</strong>
            </div>
        </div>

        <div class="subscription-content">
            <div class="section-heading">
                <div>
                    <h2>بيانات الاشتراك وبدء التشغيل</h2>
                    <p>هذه البيانات مرجع سريع لفريق الدعم ولا تؤثر تلقائيًا على إتاحة النظام.</p>
                </div>
            </div>

            <form class="subscription-form" method="POST" action="{{ route('dev-superadmin.subscription.update') }}">
                @csrf
                @method('PUT')
                <div class="field span-2">
                    <label for="clientName">اسم العميل / المنشأة</label>
                    <input id="clientName" name="client_name" type="text" required value="{{ old('client_name', $subscription['client_name'] ?? '') }}" placeholder="اسم المنشأة">
                </div>
                <div class="field">
                    <label for="clientCode">رقم العميل</label>
                    <input id="clientCode" name="client_code" type="text" value="{{ old('client_code', $subscription['client_code'] ?? '') }}" placeholder="CL-001" dir="ltr">
                </div>
                <div class="field">
                    <label for="subscriptionStatus">حالة الاشتراك</label>
                    <select id="subscriptionStatus" name="status" required>
                        @foreach ($subscriptionStatuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ old('status', $subscription['status'] ?? 'active') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="planName">اسم الباقة</label>
                    <input id="planName" name="plan_name" type="text" required value="{{ old('plan_name', $subscription['plan_name'] ?? '') }}" placeholder="الخطة الأساسية">
                </div>
                <div class="field">
                    <label for="workStartedAt">بداية عمل العميل بالبرنامج</label>
                    <input id="workStartedAt" name="work_started_at" type="date" value="{{ old('work_started_at', $subscription['work_started_at'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="subscriptionStartedAt">بداية الاشتراك</label>
                    <input id="subscriptionStartedAt" name="subscription_started_at" type="date" value="{{ old('subscription_started_at', $subscription['subscription_started_at'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="subscriptionEndsAt">نهاية الاشتراك</label>
                    <input id="subscriptionEndsAt" name="subscription_ends_at" type="date" value="{{ old('subscription_ends_at', $subscription['subscription_ends_at'] ?? '') }}">
                </div>
                <div class="field span-4">
                    <label for="subscriptionNotes">ملاحظات العميل</label>
                    <textarea id="subscriptionNotes" name="notes" placeholder="أي تفاصيل مهمة للتجديد أو الدعم الفني...">{{ old('notes', $subscription['notes'] ?? '') }}</textarea>
                </div>
                <div class="form-actions span-4">
                    <button class="btn btn-primary" type="submit">حفظ بيانات الاشتراك</button>
                </div>
            </form>
        </div>
    </section>

    <section class="stats" aria-label="ملخص اللوحة">
        <div class="stat">
            <div class="stat-label">مدة استخدام البرنامج</div>
            <div class="stat-value">{{ isset($subscription['days_using_system']) && $subscription['days_using_system'] !== null ? number_format($subscription['days_using_system']) . ' يوم' : '—' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">المتبقي في الاشتراك</div>
            <div class="stat-value">
                @if (($subscription['days_remaining'] ?? null) === null) —
                @elseif ($subscription['days_remaining'] < 0) منتهي
                @else {{ number_format($subscription['days_remaining']) }} يوم
                @endif
            </div>
        </div>
        <div class="stat">
            <div class="stat-label">الموديلات المفعلة</div>
            <div class="stat-value">{{ $enabledCount }} <small style="font-size:.8rem;color:var(--muted)">من {{ count($modules) }}</small></div>
        </div>
        <div class="stat">
            <div class="stat-label">الموظفون النشطون</div>
            <div class="stat-value">{{ $activeAdminsCount }}</div>
        </div>
    </section>
    @endif

    @if ($activeSection === 'product-updates')
        @include('admin.superadmin.system-updates.' . ($systemUpdatesView ?? 'index'))
    @endif

    @if (in_array($activeSection, ['modules', 'employees'], true))
    <div class="layout is-single">
        @if ($activeSection === 'modules')
        <section class="maintenance-control {{ $maintenanceSettings['enabled'] ? 'is-active' : '' }}" id="maintenance-mode">
            <div class="maintenance-control-status">
                <div class="maintenance-control-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 7v5l3 2"/><path d="M17 3h4v4M21 3l-5 5"/>
                    </svg>
                </div>
                <div>
                    <span class="maintenance-control-label">حالة دخول الموظفين</span>
                    <h2>{{ $maintenanceSettings['enabled'] ? 'لوحة التحكم مغلقة للتحديث' : 'لوحة التحكم متاحة' }}</h2>
                    <p>لوحة التطوير هذه تظل متاحة دائمًا لتستطيع إلغاء وضع التحديث.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('dev-superadmin.maintenance-mode.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="enabled" value="{{ $maintenanceSettings['enabled'] ? '0' : '1' }}">
                <div class="field maintenance-message-field">
                    <label for="maintenanceMessage">الرسالة التي ستظهر للموظفين</label>
                    <textarea id="maintenanceMessage" name="message" maxlength="500" required>{{ old('message', $maintenanceSettings['message']) }}</textarea>
                </div>
                <button class="btn {{ $maintenanceSettings['enabled'] ? 'btn-primary' : 'btn-danger' }}" type="submit">
                    {{ $maintenanceSettings['enabled'] ? 'إنهاء التحديث وفتح اللوحة' : 'تفعيل وضع التحديث وإغلاق اللوحة' }}
                </button>
            </form>
        </section>

        <section class="panel" id="modules">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">إدارة الموديلات</h2>
                    <p class="panel-subtitle">إغلاق أي موديل يخفيه من القائمة ويمنع الوصول لمساراته داخل لوحة الإدارة.</p>
                </div>
            </div>

            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.modules.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="current_product_profile" value="{{ $productProfileKey }}">

                    <div class="section-heading">
                        <div>
                            <h3>نوع البرنامج</h3>
                            <p>نظام الاستقدام هو الوضع الافتراضي الحالي. تغيير النوع لا يحذف أي بيانات أو إعدادات.</p>
                        </div>
                    </div>

                    <div class="dashboard-options">
                        @foreach ($productProfiles as $profileKey => $profile)
                            <label class="dashboard-option">
                                <input type="radio" name="product_profile" value="{{ $profileKey }}" {{ old('product_profile', $productProfileKey) === $profileKey ? 'checked' : '' }} required>
                                <span class="dashboard-option-title">{{ $profile['name'] ?? $profileKey }}</span>
                                <span class="dashboard-option-desc">{{ $profile['description'] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="profile-confirmation" id="productProfileConfirmation" data-current-profile="{{ $productProfileKey }}">
                        <strong>تغيير حساس في بنية البرنامج</strong>
                        <p>
                            سيتغير ظهور الموديولات والقوائم والوصول للمسارات. لن يتم حذف البيانات.
                            للتأكيد اكتب <bdi id="productProfileConfirmationPhrase"></bdi> وأدخل كلمة مرور Super Admin.
                        </p>
                        <div class="profile-confirmation-fields">
                            <label>
                                عبارة التأكيد
                                <input type="text" name="product_profile_confirmation" value="{{ old('product_profile_confirmation') }}" autocomplete="off">
                            </label>
                            <label>
                                كلمة مرور Super Admin
                                <input type="password" name="current_password" autocomplete="current-password">
                            </label>
                            <label class="wide">
                                سبب تغيير نوع البرنامج
                                <textarea name="product_profile_change_reason" rows="2" maxlength="1000" placeholder="اشرح سبب التحويل والجهة الطالبة له">{{ old('product_profile_change_reason') }}</textarea>
                            </label>
                        </div>
                    </div>

                    <div class="section-heading">
                        <div>
                            <h3>لوحة التحكم الافتراضية</h3>
                            <p>الصفحة التي يفتحها المستخدم مباشرة بعد تسجيل الدخول للوحة الإدارة.</p>
                        </div>
                    </div>

                    <div class="dashboard-options">
                        @foreach ($dashboards as $dashboardKey => $dashboard)
                            <label class="dashboard-option">
                                <input type="radio" name="default_dashboard" value="{{ $dashboardKey }}" {{ $defaultDashboardKey === $dashboardKey ? 'checked' : '' }}>
                                <span class="dashboard-option-title">{{ $dashboard['name'] ?? $dashboardKey }}</span>
                                <span class="dashboard-option-desc">{{ $dashboard['description'] ?? '' }}</span>
                                <span class="dashboard-option-url">{{ $dashboard['url'] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="section-heading">
                        <div>
                            <h3>نسخة واجهة لوحة الإدارة</h3>
                            <p>يمكن الرجوع للتصميم الحالي في أي وقت. التفعيل يغيّر العرض فقط ولا يغيّر البيانات أو منطق النظام.</p>
                        </div>
                    </div>

                    <div class="dashboard-options">
                        <label class="dashboard-option">
                            <input type="radio" name="admin_design" value="classic" {{ old('admin_design', $adminDesign) === 'classic' ? 'checked' : '' }}>
                            <span class="dashboard-option-title">التصميم الحالي</span>
                            <span class="dashboard-option-desc">الواجهة المستقرة المستخدمة حاليًا.</span>
                            <span class="dashboard-option-url">classic</span>
                        </label>
                        <label class="dashboard-option">
                            <input type="radio" name="admin_design" value="redesign" {{ old('admin_design', $adminDesign) === 'redesign' ? 'checked' : '' }}>
                            <span class="dashboard-option-title">التصميم الجديد — قيد التطوير</span>
                            <span class="dashboard-option-desc">يُطبّق الأجزاء المنجزة تدريجيًا من مرجع Click ERP Redesign.</span>
                            <span class="dashboard-option-url">redesign</span>
                        </label>
                    </div>

                    <div class="section-heading">
                        <div>
                            <h3>مكوّنات النظام</h3>
                            <p>{{ $enabledCount }} مفعّل من {{ count($modules) }} — إغلاق أي موديل يخفيه من القائمة ويمنع الوصول لمساراته.</p>
                        </div>
                    </div>

                    <div class="module-grid">
                        <article class="module-card is-on">
                            <div>
                                <div class="module-top">
                                    <div>
                                        <h3 class="module-name">إدارة المستخدمين</h3>
                                        <span class="module-key">users</span>
                                    </div>
                                    <span class="status-badge status-on">حد النسخة</span>
                                </div>
                                <p class="module-description">التحكم في إجمالي حسابات مستخدمي لوحة الإدارة المسموح بها في هذه النسخة.</p>
                            </div>

                            <div class="limit-row">
                                <label for="usersLimit">عدد المستخدمين المسموح به</label>
                                <input
                                    id="usersLimit"
                                    class="limit-input"
                                    type="number"
                                    name="users_limit"
                                    min="0"
                                    step="1"
                                    value="{{ old('users_limit', $userLimit) }}"
                                    placeholder="غير محدود"
                                >
                                <p class="limit-help">اترك الحقل فارغًا للسماح بعدد غير محدود من المستخدمين.</p>
                            </div>
                        </article>

                        @foreach ($modules as $key => $module)
                            @php
                                $isEnabled = $enabledMap[$key] ?? true;
                                $isConfigured = $configuredMap[$key] ?? true;
                                $isAvailable = \App\Support\SystemModuleManager::isAvailableForProfile($key, $productProfileKey);
                                $isRequired = \App\Support\SystemModuleManager::isRequiredForProfile($key, $productProfileKey);
                                $isSwitchChecked = $isRequired || $isConfigured;
                            @endphp
                            <article class="module-card {{ $isEnabled ? 'is-on' : 'is-off' }}">
                                <div>
                                    <div class="module-top">
                                        <div>
                                            <h3 class="module-name">{{ $module['name'] ?? $key }}</h3>
                                            <span class="module-key">{{ $key }}</span>
                                        </div>
                                        <span class="status-badge {{ $isEnabled ? 'status-on' : 'status-off' }}">
                                            {{ !$isAvailable ? 'غير متاح لهذا النوع' : ($isRequired ? 'أساسي لهذا النوع' : ($isEnabled ? 'مفعل' : 'مغلق')) }}
                                        </span>
                                    </div>
                                    <p class="module-description">{{ $module['description'] ?? '' }}</p>
                                </div>

                                <div class="switch-row">
                                    <span>حالة الموديل</span>
                                    <label class="switch" title="تفعيل أو إغلاق {{ $module['name'] ?? $key }}">
                                        @if ($isRequired || (!$isAvailable && $isConfigured))
                                            <input type="hidden" name="modules[{{ $key }}]" value="1">
                                        @endif
                                        <input type="checkbox" name="modules[{{ $key }}]" value="1" {{ $isSwitchChecked ? 'checked' : '' }} {{ (!$isAvailable || $isRequired) ? 'disabled' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                @if ($key === 'hourly_rental')
                                    {{-- نفس خيار «العرض في الموقع الخارجي» في إعدادات التأجير بالساعة؛ يُخفي الحجز عن الموقع وحده. --}}
                                    <div class="switch-row">
                                        <span>العرض في الموقع الخارجي</span>
                                        <label class="switch" title="إظهار أو إخفاء حجز الساعات في الموقع الخارجي">
                                            <input type="hidden" name="hourly_website_field" value="1">
                                            <input type="checkbox" name="hourly_show_on_website" value="1" {{ old('hourly_show_on_website', \App\Support\HourlyRentalSettings::get('show_on_website', true)) ? 'checked' : '' }}>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                @endif

                                @if ($key === 'branches')
                                    <div class="limit-row">
                                        <label for="branchesLimit">عدد الفروع المسموح به</label>
                                        <input
                                            id="branchesLimit"
                                            class="limit-input"
                                            type="number"
                                            name="branches_limit"
                                            min="0"
                                            step="1"
                                            value="{{ old('branches_limit', $branchLimit) }}"
                                            placeholder="غير محدود"
                                        >
                                        <p class="limit-help">اترك الحقل فارغًا للسماح بعدد غير محدود من الفروع.</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="save-row">
                        <button class="btn btn-primary" type="submit">حفظ تغييرات الموديلات</button>
                    </div>
                </form>
            </div>
        </section>
        @endif

        @if ($activeSection === 'employees')
        <aside class="panel" id="employees">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">الدخول كموظف</h2>
                    <p class="panel-subtitle">ابحث بالاسم أو البريد أو الهاتف ثم افتح جلسة الإدارة بهذا الموظف.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="employee-tools">
                    <div class="search-field">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>
                        </svg>
                        <input class="search-input" type="search" id="employeeSearch" placeholder="بحث بالاسم أو البريد أو رقم الجوال..." autocomplete="off">
                    </div>
                </div>

                <div class="employee-list" id="employeeList">
                    @foreach ($admins as $admin)
                        <div class="employee-card {{ $admin->dev_is_suspended ? 'is-suspended' : '' }}" data-search="{{ e(mb_strtolower($admin->dev_search_text)) }}">
                            <div>
                                <h3 class="employee-name">{{ $admin->name ?: 'بدون اسم' }}</h3>
                                <p class="employee-meta">#{{ $admin->id }} - {{ $admin->dev_contact }}</p>
                                <span class="employee-role">{{ $admin->dev_role_name }}{{ $admin->dev_is_suspended ? ' - موقوف' : '' }}</span>
                            </div>
                            <form method="POST" action="{{ route('dev-superadmin.impersonate', $admin->id) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit" {{ $admin->dev_is_suspended ? 'disabled' : '' }}>دخول</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="empty-state" id="employeeEmpty">لا توجد نتائج مطابقة للبحث.</div>
            </div>
        </aside>
        @endif
    </div>
    @endif

    @if ($activeSection === 'backups')
    @include('admin.superadmin.partials.backups')
    @endif

    @if ($activeSection === 'scheduler')
    <section class="panel backup-panel" id="scheduler">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">مراقبة Cron Jobs وLaravel Scheduler</h2>
                <p class="panel-subtitle">الحالة تعتمد على نبض يُكتب بواسطة <span dir="ltr">schedule:run</span> كل دقيقة، وليست مجرد وجود المهمة داخل الكود.</p>
            </div>
            <div class="backup-actions">
                @if (!$schedulerStatus['active'] || $schedulerStatus['crontab_configured'] !== true)
                    <form method="POST" action="{{ route('dev-superadmin.scheduler.enable') }}" onsubmit="return confirm('سيتم تثبيت سطر Cron وتشغيل جميع المهام المستحقة الآن. هل تريد المتابعة؟')">
                        @csrf
                        <button class="btn btn-primary" type="submit">تفعيل وتشغيل Scheduler</button>
                    </form>
                @endif
                <a class="btn" href="{{ route('dev-superadmin.modules') }}">تحديث الحالة</a>
            </div>
        </div>

        <div class="panel-body">
            <div class="scheduler-summary">
                <div class="scheduler-stat {{ $schedulerStatus['active'] ? 'is-active' : 'is-inactive' }}">
                    حالة Laravel Scheduler
                    <strong>{{ $schedulerStatus['active'] ? 'يعمل الآن' : 'متوقف أو لم يعمل خلال 3 دقائق' }}</strong>
                </div>
                <div class="scheduler-stat {{ $schedulerStatus['crontab_configured'] === true ? 'is-active' : ($schedulerStatus['crontab_configured'] === false ? 'is-inactive' : '') }}">
                    إعداد Crontab للمستخدم الحالي
                    <strong>
                        @if ($schedulerStatus['crontab_configured'] === true) موجود
                        @elseif ($schedulerStatus['crontab_configured'] === false) غير موجود
                        @else تعذر التحقق
                        @endif
                    </strong>
                </div>
                <div class="scheduler-stat">
                    آخر نبض
                    <strong>{{ $schedulerStatus['last_heartbeat_at'] ? $schedulerStatus['last_heartbeat_at']->format('Y-m-d H:i:s') : 'لا يوجد بعد' }}</strong>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>ملخص تدقيق الأمان:</strong>
                لا توجد مهمة مجدولة تحذف صفوف عقود أو طلبات أو عملاء مباشرة.
                توجد {{ $highRiskCronCount }} مهمة عالية التأثير تغيّر حالة الطلب تلقائيًا،
                و{{ $invalidCronCount }} مهمة بأمر غير موجود لن تعمل حتى يتم إصلاحها.
                حذف النسخ الاحتياطية محصور بالملفات الأقدم من مدة الاحتفاظ فقط.
            </div>

            @if ($schedulerStatus['crontab_error'])
                <div class="alert alert-danger">تعذر فحص Crontab: {{ $schedulerStatus['crontab_error'] }}</div>
            @endif

            @foreach ($schedulerStatus['crontab_entries'] as $entry)
                <div class="cron-entry">{{ $entry }}</div>
            @endforeach

            @if ($schedulerStatus['crontab_configured'] === false)
                <div class="alert alert-danger">
                    Scheduler غير مضاف إلى Crontab لهذا المستخدم. أضف السطر التالي على السيرفر:
                    <div class="cron-entry">{{ $schedulerStatus['recommended_entry'] }}</div>
                </div>
            @endif

            <div class="backup-table-wrap">
                <table class="backup-table scheduler-table">
                    <thead>
                        <tr>
                            <th>المهمة</th>
                            <th>الحالة</th>
                            <th>سبب التشغيل</th>
                            <th>التأثير والحذف</th>
                            <th>الخطورة وضابط الأمان</th>
                            <th>الجدولة والتشغيل القادم</th>
                            <th>الأمر</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($schedulerStatus['jobs'] as $job)
                        <tr>
                            <td><strong>{{ $job['name'] }}</strong></td>
                            <td>
                                <span class="status-badge {{ $job['enabled'] ? 'status-on' : 'status-off' }}">
                                    @if (!$job['command_exists']) أمر غير موجود — لن تعمل
                                    @elseif ($job['enabled']) مفعلة وتعمل
                                    @elseif ($job['configured']) مجدولة لكن Scheduler متوقف
                                    @else غير مفعلة في هذه البيئة
                                    @endif
                                </span>
                            </td>
                            <td class="job-explanation">{{ $job['purpose'] }}</td>
                            <td class="job-effect">
                                <div>{{ $job['data_effect'] }}</div>
                                <strong>{{ $job['deletion'] }}</strong>
                            </td>
                            <td class="job-safety">
                                <span class="risk-badge risk-{{ $job['risk'] }}">{{ $job['risk_label'] }}</span>
                                <div>{{ $job['safety_note'] }}</div>
                            </td>
                            <td>
                                <div class="cron-expression">{{ $job['expression'] }}</div>
                                <div class="cell-ltr">{{ $job['next_run_at'] ? $job['next_run_at']->format('Y-m-d H:i:s') : '—' }}</div>
                            </td>
                            <td>
                                <div class="cron-command">{{ $job['command'] }}</div>
                                <small class="text-muted">
                                    {{ $job['without_overlapping'] ? 'بدون تداخل' : 'يسمح بالتداخل' }}
                                    — {{ $job['background'] ? 'في الخلفية' : 'في المقدمة' }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">لم يتم العثور على مهمات مجدولة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif
</main>

<script>
    const productProfileConfirmation = document.getElementById('productProfileConfirmation');
    const productProfileInputs = Array.from(document.querySelectorAll('input[name="product_profile"]'));
    const productProfilePhrase = document.getElementById('productProfileConfirmationPhrase');
    const profileConfirmationInput = document.querySelector('input[name="product_profile_confirmation"]');
    const superadminPasswordInput = document.querySelector('input[name="current_password"]');
    const productProfileReasonInput = document.querySelector('[name="product_profile_change_reason"]');

    function refreshProductProfileConfirmation() {
        if (!productProfileConfirmation) return;

        const currentProfile = productProfileConfirmation.dataset.currentProfile;
        const selectedProfile = productProfileInputs.find((input) => input.checked)?.value || currentProfile;
        const isChanging = selectedProfile !== currentProfile;
        const expectedPhrase = `تغيير إلى ${selectedProfile}`;

        productProfileConfirmation.classList.toggle('is-visible', isChanging);
        if (productProfilePhrase) productProfilePhrase.textContent = expectedPhrase;
        if (profileConfirmationInput) profileConfirmationInput.required = isChanging;
        if (superadminPasswordInput) superadminPasswordInput.required = isChanging;
        if (productProfileReasonInput) productProfileReasonInput.required = isChanging;
    }

    productProfileInputs.forEach((input) => input.addEventListener('change', refreshProductProfileConfirmation));
    refreshProductProfileConfirmation();

    const searchInput = document.getElementById('employeeSearch');
    const employeeCards = Array.from(document.querySelectorAll('.employee-card'));
    const emptyState = document.getElementById('employeeEmpty');

    searchInput?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visibleCount = 0;

        employeeCards.forEach((card) => {
            const isVisible = !query || card.dataset.search.includes(query);
            card.style.display = isVisible ? 'grid' : 'none';
            if (isVisible) {
                visibleCount++;
            }
        });

        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    });

    async function copyTerminalCommand(button, command) {
        if (!command) return;

        try {
            await navigator.clipboard.writeText(command);
        } catch (error) {
            const helper = document.createElement('textarea');
            helper.value = command;
            helper.setAttribute('readonly', '');
            helper.style.position = 'fixed';
            helper.style.opacity = '0';
            document.body.appendChild(helper);
            helper.select();
            document.execCommand('copy');
            helper.remove();
        }

        const originalLabel = button.textContent;
        button.textContent = 'تم النسخ';
        window.setTimeout(() => button.textContent = originalLabel, 1800);
    }

    const databaseFileInput = document.getElementById('databaseFileInput');
    const databaseSshConnection = document.getElementById('databaseSshConnection');
    const databaseUploadCommand = document.getElementById('databaseUploadCommand');
    const copyDatabaseUploadCommand = document.getElementById('copyDatabaseUploadCommand');
    const terminalSelectedFile = document.getElementById('terminalSelectedFile');
    const copyDatabaseTerminalPrefix = document.getElementById('copyDatabaseTerminalPrefix');
    const databaseTerminalCommandPrefix = document.getElementById('databaseTerminalCommandPrefix');
    const databaseRemoteProject = @json(config('database.backup_ssh.project_path'));
    const databaseRemotePhp = @json(config('database.backup_ssh.php_binary'));
    const databaseClientIsWindows = /Windows/i.test(navigator.userAgent);

    function posixQuote(value) {
        return `'${String(value).replaceAll("'", `'"'"'`)}'`;
    }

    function updateDatabaseImportCommand() {
        if (!databaseUploadCommand || !copyDatabaseUploadCommand) return;
        const file = databaseFileInput?.files?.[0];
        const connection = databaseSshConnection.value.trim().replace(/\\@/g, '@').replace(/^ssh\s+/, '');
        // Accept only a destination, never arbitrary SSH options or shell commands.
        const validConnection = /^[A-Za-z_][A-Za-z0-9_.-]*@(?:[A-Za-z0-9][A-Za-z0-9.-]*|\[[A-Fa-f0-9:]+\])$/.test(connection);
        const validFile = file && /\.sql(?:\.gz)?$/i.test(file.name) && !/[\/\\\r\n\0]/.test(file.name);
        const valid = Boolean(validConnection && validFile);
        copyDatabaseUploadCommand.disabled = !valid;
        copyDatabaseTerminalPrefix.disabled = !valid;
        copyDatabaseUploadCommand.dataset.command = '';
        copyDatabaseTerminalPrefix.dataset.commandPrefix = '';
        if (!valid) {
            databaseUploadCommand.textContent = validConnection ? 'اختر ملف SQL أو SQL.GZ لإنشاء أمر الرفع.' : 'اكتب الاتصال بصيغة ssh user@IP.';
            databaseTerminalCommandPrefix.textContent = 'اختر الملف لإنشاء أمر الاستيراد بعد الرفع.';
            terminalSelectedFile.textContent = file ? `الملف المختار: ${file.name}` : 'لم يتم اختيار ملف بعد.';
            return;
        }

        const directory = databaseRemoteProject.replace(/\/$/, '') + '/storage/app/database-backups';
        const serverPath = directory + '/' + file.name;
        const stagingPath = '/tmp/clickerp-database-upload-' + file.name;
        const importCommand = 'cd ' + posixQuote(databaseRemoteProject)
            + ' && sudo -u www-data ' + posixQuote(databaseRemotePhp)
            + ' artisan database:import-local ' + posixQuote(serverPath);
        const quote = databaseClientIsWindows
            ? (value) => `'${String(value).replaceAll("'", "''")}'`
            : posixQuote;
        const stagingDestination = quote(connection + ':' + stagingPath);
        const finalizeUpload = 'chmod 0644 -- ' + posixQuote(stagingPath)
            + ' && sudo -u www-data mkdir -p -- ' + posixQuote(directory)
            + ' && sudo -u www-data cp -- ' + posixQuote(stagingPath) + ' ' + posixQuote(serverPath)
            + '; upload_status=$?; rm -f -- ' + posixQuote(stagingPath) + '; exit $upload_status';
        const finalize = 'ssh ' + quote(connection) + ' ' + quote(finalizeUpload);
        let uploadCommand;
        if (databaseClientIsWindows) {
            uploadCommand = [
                '$backupName = ' + quote(file.name),
                '$backupFile = $null',
                'foreach ($backupDir in @((Get-Location).Path, [Environment]::GetFolderPath("UserProfile") + "\\Downloads", [Environment]::GetFolderPath("Desktop"), [Environment]::GetFolderPath("MyDocuments"))) { if ($backupDir) { $backupCandidate = Join-Path $backupDir $backupName; if ((Test-Path -LiteralPath $backupCandidate -PathType Leaf) -and (Get-Item -LiteralPath $backupCandidate).Length -eq ' + file.size + ') { $backupFile = $backupCandidate; break } } }',
                'if (!$backupFile) { $backupFile = Read-Host "Paste the full file path without quotes" }',
                'if (Test-Path -LiteralPath $backupFile -PathType Leaf) { scp "$backupFile" ' + stagingDestination + '; if ($LASTEXITCODE -eq 0) { ' + finalize + ' } } else { Write-Error "Backup file not found; upload cancelled." }',
            ].join('\n');
        } else {
            uploadCommand = [
                'backup_name=' + quote(file.name),
                'backup_file=',
                'for backup_dir in "$PWD" "$HOME/Downloads" "$HOME/Desktop" "$HOME/Documents"; do',
                '  if [ -f "$backup_dir/$backup_name" ] && [ "$(wc -c < "$backup_dir/$backup_name" | tr -d \' \')" = ' + quote(String(file.size)) + ' ]; then backup_file="$backup_dir/$backup_name"; break; fi',
                'done',
                'if [ -z "$backup_file" ]; then printf \'Paste the full file path without quotes: \'; IFS= read -r backup_file; fi',
                'if [ -f "$backup_file" ]; then',
                '  scp "$backup_file" ' + stagingDestination + ' && ' + finalize,
                'else',
                '  printf \'Backup file not found; upload cancelled.\\n\' >&2',
                'fi',
            ].join('\n');
        }
        // Parse the whole pasted command before a possible interactive path prompt.
        uploadCommand = databaseClientIsWindows ? '& {\n' + uploadCommand + '\n}' : '(\n' + uploadCommand + '\n)';
        databaseUploadCommand.textContent = uploadCommand;
        copyDatabaseUploadCommand.dataset.command = uploadCommand;
        databaseTerminalCommandPrefix.textContent = importCommand;
        copyDatabaseTerminalPrefix.dataset.commandPrefix = importCommand;
        terminalSelectedFile.textContent = `الملف المختار: ${file.name}`;
    }
    databaseFileInput?.addEventListener('change', updateDatabaseImportCommand);
    databaseSshConnection?.addEventListener('input', updateDatabaseImportCommand);
    copyDatabaseUploadCommand?.addEventListener('click', function () {
        if (this.dataset.command) copyTerminalCommand(this, this.dataset.command);
    });
    updateDatabaseImportCommand();

    copyDatabaseTerminalPrefix?.addEventListener('click', function () {
        copyTerminalCommand(this, this.dataset.commandPrefix || '');
    });

    document.querySelectorAll('.copy-terminal-command').forEach((button) => {
        button.addEventListener('click', function () {
            copyTerminalCommand(this, this.dataset.command || '');
        });
    });

    const databaseImportForm = document.getElementById('databaseImportForm');
    const databaseImportButton = document.getElementById('databaseImportButton');
    const databaseImportProgress = document.getElementById('databaseImportProgress');
    const databaseImportTrack = databaseImportProgress?.querySelector('[role="progressbar"]');
    const databaseImportBar = databaseImportProgress?.querySelector('.import-progress-bar');
    const databaseImportProgressText = databaseImportProgress?.querySelector('.import-progress-text');
    const databaseImportMessage = document.getElementById('databaseImportMessage');

    databaseImportForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!window.confirm('سيتم حفظ الملف كنسخة مرفوعة يدويًا، ثم أخذ نسخة أمان محلية واستبدال قاعدة البيانات. هل تريد المتابعة؟')) {
            return;
        }

        const xhr = new XMLHttpRequest();
        const formData = new FormData(databaseImportForm);
        databaseImportButton.disabled = true;
        databaseImportProgress.hidden = false;
        databaseImportProgress.classList.remove('is-processing');
        databaseImportMessage.hidden = true;
        databaseImportBar.style.width = '0%';
        databaseImportTrack.setAttribute('aria-valuenow', '0');
        databaseImportProgressText.textContent = 'جاري رفع الملف: 0%';

        xhr.open('POST', databaseImportForm.action);
        xhr.responseType = 'json';
        xhr.timeout = 2 * 60 * 60 * 1000;
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function (progressEvent) {
            if (!progressEvent.lengthComputable) return;
            const percentage = Math.min(100, Math.round((progressEvent.loaded / progressEvent.total) * 100));
            databaseImportBar.style.width = `${percentage}%`;
            databaseImportTrack.setAttribute('aria-valuenow', String(percentage));
            databaseImportProgressText.textContent = `جاري رفع الملف: ${percentage}%`;
        });

        xhr.upload.addEventListener('load', function () {
            databaseImportProgress.classList.add('is-processing');
            databaseImportTrack.setAttribute('aria-valuenow', '100');
            databaseImportProgressText.textContent = 'اكتمل الرفع — جاري تسجيل النسخة المحلية وفحص واستيراد قاعدة البيانات...';
        });

        xhr.addEventListener('load', function () {
            const response = xhr.response || {};
            if (xhr.status >= 200 && xhr.status < 300) {
                databaseImportProgress.classList.remove('is-processing');
                databaseImportBar.style.width = '100%';
                databaseImportProgressText.textContent = 'اكتملت العملية بنجاح.';
                databaseImportMessage.textContent = response.message || 'تم رفع النسخة واستيراد قاعدة البيانات بنجاح.';
                databaseImportMessage.className = 'import-progress-message is-success';
                databaseImportMessage.hidden = false;
                window.setTimeout(() => window.location.reload(), 1600);
                return;
            }

            const errors = response.errors || {};
            const details = Object.values(errors).flat().join(' ') || response.message || 'تعذر إكمال رفع واستيراد قاعدة البيانات.';
            showDatabaseImportError(details);
        });

        xhr.addEventListener('error', function () {
            showDatabaseImportError('انقطع الاتصال أثناء العملية. تحقق من عمل السيرفر ثم حدّث قائمة النسخ المحلية.');
        });

        xhr.addEventListener('timeout', function () {
            showDatabaseImportError('انتهت مهلة العملية. تحقق من قائمة النسخ المحلية وسجل الخادم قبل إعادة المحاولة.');
        });

        xhr.send(formData);
    });

    function showDatabaseImportError(message) {
        databaseImportProgress?.classList.remove('is-processing');
        if (databaseImportProgressText) databaseImportProgressText.textContent = 'لم تكتمل عملية الاستيراد.';
        if (databaseImportMessage) {
            databaseImportMessage.textContent = message;
            databaseImportMessage.className = 'import-progress-message is-error';
            databaseImportMessage.hidden = false;
        }
        if (databaseImportButton) databaseImportButton.disabled = false;
    }
</script>
</body>
</html>
