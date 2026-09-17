@extends('admin.layout.indexs.index')

@section('page-title', 'إعداد ربط منصة الاستقدام')

@section('content')
<div class="page-body p-4 text-dark" dir="rtl">
    <div class="container" style="max-width: 1000px">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h3 class="mb-1">إعداد الربط مع تكامل</h3>
                <p class="text-muted mb-0">بيانات المنشأة وبيانات الاعتماد الخاصة بمنصة الاستقدام.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('istiqdam.index') }}"><i class="fas fa-arrow-right me-1"></i> العمليات</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($environment === 'production')<div class="alert alert-warning">أنت تعدّل إعدادات بيئة الإنتاج.</div>@endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>بيانات الربط</strong><span class="badge {{ $isConfigured ? 'bg-success' : 'bg-secondary' }}">{{ $isConfigured ? 'الإعداد مكتمل' : 'بانتظار الإكمال' }}</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('istiqdam.settings.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label" for="environment">بيئة الاتصال <span class="text-danger">*</span></label>
                        <select class="form-select @error('environment') is-invalid @enderror" id="environment" name="environment" required>
                            <option value="staging" @if(old('environment', $istiqdamSettings?->environment ?: 'staging') === 'staging') selected @endif>Staging — اختبار</option>
                            <option value="uat" @if(old('environment', $istiqdamSettings?->environment) === 'uat') selected @endif>UAT — قبول المستخدم</option>
                            <option value="production" @if(old('environment', $istiqdamSettings?->environment) === 'production') selected @endif>Production — إنتاج</option>
                        </select>
                        <div class="form-text">محفوظ في قاعدة البيانات ولا يعتمد على APP_ENV أو ملف .env.</div>
                        @error('environment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button class="btn btn-outline-secondary" id="load-environment-defaults" type="button">تعبئة الروابط القياسية للبيئة</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="base_url">رابط بوابة API <span class="text-danger">*</span></label>
                        <input type="url" dir="ltr" class="form-control @error('base_url') is-invalid @enderror" id="base_url" name="base_url" value="{{ old('base_url', $istiqdamSettings?->base_url ?: 'https://gw-apic.qiwa.info') }}" required>
                        @error('base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="oauth_url">رابط OAuth Token <span class="text-danger">*</span></label>
                        <input type="url" dir="ltr" class="form-control @error('oauth_url') is-invalid @enderror" id="oauth_url" name="oauth_url" value="{{ old('oauth_url', $istiqdamSettings?->oauth_url ?: 'https://gw-apic.qiwa.info/takamol/staging/takamol-oauth/oauth2/token') }}" required>
                        @error('oauth_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="outbound_public_ip">عنوان الخروج العام للخادم <span class="text-danger">*</span></label>
                        <input dir="ltr" class="form-control @error('outbound_public_ip') is-invalid @enderror" id="outbound_public_ip" name="outbound_public_ip" value="{{ old('outbound_public_ip', $istiqdamSettings?->outbound_public_ip ?: '130.110.98.217') }}" required>
                        <div class="form-text">Public Outbound IP الذي يجب إضافته لدى تكامل في قائمة السماح (Whitelist)، وليس عنوان الشبكة الداخلي.</div>
                        @error('outbound_public_ip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="establishment_name">English establishment name <span class="text-muted">(اختياري)</span></label>
                        <input class="form-control @error('establishment_name') is-invalid @enderror" id="establishment_name" name="establishment_name" value="{{ old('establishment_name', $istiqdamSettings?->establishment_name) }}" autocomplete="organization">
                        <div class="form-text">للتعريف داخل النظام فقط؛ لا يدخل في مصادقة OAuth.</div>
                        @error('establishment_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="client_id">Client ID <span class="text-danger">*</span></label>
                        <input class="form-control @error('client_id') is-invalid @enderror" id="client_id" name="client_id" value="{{ old('client_id', $istiqdamSettings?->client_id) }}" required autocomplete="off">
                        @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="client_secret">Client Secret {{ $istiqdamSettings?->client_secret ? '' : '*' }}</label>
                        <input class="form-control @error('client_secret') is-invalid @enderror" id="client_secret" name="client_secret" type="password" {{ $istiqdamSettings?->client_secret ? '' : 'required' }} autocomplete="new-password" placeholder="{{ $istiqdamSettings?->client_secret ? 'محفوظ — اتركه فارغاً للإبقاء عليه' : '' }}">
                        <div class="form-text">تُخزن القيمة مشفّرة ولا تظهر مرة أخرى.</div>
                        @error('client_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="api_key">API KEY <span class="text-muted">(اختياري)</span></label>
                        <input class="form-control @error('api_key') is-invalid @enderror" id="api_key" name="api_key" type="password" autocomplete="new-password" placeholder="{{ $istiqdamSettings?->api_key ? 'محفوظ — اتركه فارغاً للإبقاء عليه' : 'اتركه فارغاً إذا لم تصدره تكامل' }}">
                        <div class="form-text">تكامل تصدر Client ID وClient Secret فقط في الغالب؛ لا يُرسل هذا الهيدر إلا إذا كانت له قيمة.</div>
                        @error('api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($istiqdamSettings?->api_key)
                            <div class="form-check mt-2">
                                <input class="form-check-input" id="remove_api_key" name="remove_api_key" type="checkbox" value="1">
                                <label class="form-check-label text-danger" for="remove_api_key">حذف المفتاح المخزّن وعدم إرساله مع الطلبات</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="scope">OAuth Scope <span class="text-danger">*</span></label>
                        <input dir="ltr" class="form-control @error('scope') is-invalid @enderror" id="scope" name="scope" value="{{ old('scope', $istiqdamSettings?->scope ?: 'IntegrationScope') }}" required>
                        @error('scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2"><label class="form-label" for="timeout">مهلة الطلب (ث)</label><input type="number" class="form-control" id="timeout" name="timeout" min="1" max="300" value="{{ old('timeout', $istiqdamSettings?->timeout ?: 30) }}" required>@error('timeout')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    <div class="col-md-2"><label class="form-label" for="connect_timeout">مهلة الاتصال (ث)</label><input type="number" class="form-control" id="connect_timeout" name="connect_timeout" min="1" max="120" value="{{ old('connect_timeout', $istiqdamSettings?->connect_timeout ?: 10) }}" required>@error('connect_timeout')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    <div class="col-md-2"><label class="form-label" for="token_cache_ttl">حفظ التوكن (ث)</label><input type="number" class="form-control" id="token_cache_ttl" name="token_cache_ttl" min="60" max="86400" value="{{ old('token_cache_ttl', $istiqdamSettings?->token_cache_ttl ?: 3500) }}" required>@error('token_cache_ttl')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6 d-flex align-items-end pb-2"><div class="form-check form-switch"><input type="hidden" name="logging_enabled" value="0"><input class="form-check-input" id="logging_enabled" name="logging_enabled" type="checkbox" value="1" @if((bool) old('logging_enabled', $istiqdamSettings?->logging_enabled ?? true)) checked @endif><label class="form-check-label" for="logging_enabled">تفعيل سجل طلبات وأخطاء التكامل</label></div></div>
                    <div class="col-12 pt-2"><button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk me-1"></i> حفظ إعدادات الربط</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.getElementById('load-environment-defaults')?.addEventListener('click', function () {
    const defaults = {
        staging: ['https://gw-apic.qiwa.info', 'https://gw-apic.qiwa.info/takamol/staging/takamol-oauth/oauth2/token'],
        uat: ['https://gw-apic.uat.qiwa.info', 'https://gw-apic.uat.qiwa.info/takamol/staging/takamol-oauth/oauth2/token'],
        production: ['https://gw-apic.qiwa.sa', 'https://gw-apic.qiwa.sa/takamol/prod/takamol-oauth/oauth2/token']
    };
    const selected = defaults[document.getElementById('environment').value];
    if (!selected) return;
    document.getElementById('base_url').value = selected[0];
    document.getElementById('oauth_url').value = selected[1];
});
</script>
@endsection
