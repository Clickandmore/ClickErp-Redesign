@extends('hourly_driver.layout')
@section('title', 'بوابة عاملات الساعات')
@section('content')
<h1>دخول عاملات الساعات</h1>
@if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
<form method="POST" action="{{ route('hourly-maid.authenticate') }}">@csrf
<label for="login">اسم المستخدم أو هاتف تسجيل الدخول</label><input id="login" name="login" value="{{ old('login') }}" required maxlength="255" autocomplete="username" dir="ltr">
<label for="password">كلمة المرور</label><input type="password" id="password" name="password" required maxlength="72" autocomplete="current-password" dir="ltr">
<button type="submit">تسجيل الدخول</button></form>
@endsection
