<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'تعذر الربط — ClickERP'])
</head>
<body class="sa-login">
<main class="login-shell">
    <section class="login-panel">
        <h1>تعذر إكمال الربط</h1>
        <p>{{ $message }}</p>
        <p class="login-note">أغلق هذه الصفحة وابدأ الربط من جديد من التطبيق. إن تكرر الخطأ فأرسل لمسؤول النظام اسم التطبيق.</p>
    </section>
</main>
</body>
</html>
