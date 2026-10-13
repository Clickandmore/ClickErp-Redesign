<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('admin.layout.inc._meta')
    @include('admin.layout.inc._css')
    <title>@yield('page-title') — {{ config('app.name') }}</title>
    @yield('style')
</head>
<body>
<div class="container-fluid py-4">
    <a href="{{ route('dev-superadmin.hourly-app-review-accounts.index') }}" class="btn btn-outline-primary mb-3">حسابات اختبار التطبيقات</a>
    <a href="{{ route('dev-superadmin.modules') }}" class="btn btn-outline-primary mb-3">العودة إلى إدارة الموديولات</a>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div class="alert alert-info">لتشغيل الساعات، فعّل الموديول من إدارة الموديولات ثم فعّل الإعدادات هنا بعد اكتمال التهيئة.</div>
    @yield('content')
</div>
@yield('js')
</body>
</html>
