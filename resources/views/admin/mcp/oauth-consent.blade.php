<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'ربط مساعد ذكي — ClickERP'])
    <style>
        .consent-list { margin: 14px 0; padding: 0 18px; line-height: 2; color: var(--text-soft); font-size: .9rem; }
        .consent-meta { display: grid; gap: 8px; margin: 14px 0; font-size: .88rem; }
        .consent-meta div { display: flex; justify-content: space-between; gap: 12px; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--r-sm); }
        .consent-meta span { color: var(--muted); }
        .consent-actions { display: flex; gap: 10px; margin-top: 18px; }
        .consent-actions .btn { flex: 1; justify-content: center; }
    </style>
</head>
<body class="sa-login">
<main class="login-shell">
    <div class="login-brand">
        <div class="login-mark">C</div>
        <div>
            <h1>ربط مساعد ذكي بـ ClickERP</h1>
            <p>{{ $client->client_name ?: 'تطبيق خارجي' }} يطلب الوصول إلى بيانات النظام</p>
        </div>
    </div>

    <section class="login-panel">
        <div class="consent-meta">
            <div><span>التطبيق</span><b>{{ $client->client_name ?: 'بدون اسم' }}</b></div>
            <div><span>سيعود إلى</span><b dir="ltr">{{ $redirectHost }}</b></div>
            <div><span>بحساب</span><b>{{ $admin->name }}</b></div>
        </div>

        <p>عند الموافقة يستطيع هذا المساعد:</p>
        <ul class="consent-list">
            <li><b>قراءة</b> البيانات التي تراها أنت فقط، بصلاحياتك وداخل فرعك.</li>
            <li>لا يستطيع إضافة أو تعديل أو حذف أي شيء.</li>
            <li>كل طلب منه يُسجَّل، ويمكن إلغاء الربط في أي وقت من لوحة Super Admin.</li>
        </ul>

        <p class="login-note">لا توافق إلا إذا كنت أنت من بدأ الربط الآن من {{ $client->client_name ?: 'هذا التطبيق' }}.</p>

        <form method="POST" action="{{ route('mcp.oauth.decision') }}">
            @csrf
            @foreach ($params as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <div class="consent-actions">
                <button class="btn btn-primary" type="submit" name="decision" value="approve">موافقة وربط</button>
                <button class="btn" type="submit" name="decision" value="deny">رفض</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
