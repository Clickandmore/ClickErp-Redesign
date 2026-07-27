@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات تخزين R2
@endsection

@section('content')
@php
    $data = array_replace($r2Settings, old());
    $isVerified = filled($r2Settings['verified_at'] ?? null);
    $isReady = \App\Support\R2StorageSettings::isReady($r2Settings);
@endphp

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1"><i class="fa fa-cloud me-2"></i>إعدادات تخزين Cloudflare R2</h4>
            <div class="text-muted small">رفع ملفات السير الذاتية والصور والفيديو إلى مساحة خارجية متوافقة مع S3.</div>
        </div>
        <span class="badge {{ $isReady ? 'bg-success' : ($isVerified ? 'bg-warning text-dark' : 'bg-secondary') }} p-2">
            {{ $isReady ? 'مفعّل ومتصل' : ($isVerified ? 'تم الاختبار وغير مفعّل' : 'غير متصل') }}
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        لن يبدأ الرفع إلى R2 إلا بعد حفظ الإعدادات، نجاح اختبار الاتصال، وتفعيل الخيار. الملفات المحلية القديمة ستظل قابلة للقراءة.
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>بيانات الربط</strong>
            @if($isVerified)
                <small class="text-muted">آخر تحقق: {{ \Carbon\Carbon::parse($r2Settings['verified_at'])->format('Y-m-d H:i') }}</small>
            @endif
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('storage.settings.update') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="r2Enabled"
                            {{ filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="r2Enabled">استخدام R2 في ملفات السير الذاتية الجديدة</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">S3 API Endpoint</label>
                    <input type="url" name="endpoint" class="form-control" required
                        placeholder="https://ACCOUNT_ID.r2.cloudflarestorage.com" value="{{ $data['endpoint'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bucket</label>
                    <input type="text" name="bucket" class="form-control" required minlength="3" maxlength="63"
                        pattern="[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?"
                        placeholder="clickerp-demo" value="{{ $data['bucket'] ?? '' }}">
                    <small class="text-muted">اكتب الاسم الكامل كما يظهر في Cloudflare، مثال: clickerp-demo</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">بادئة المسار</label>
                    <input type="text" name="root_prefix" class="form-control" placeholder="clickerp"
                        value="{{ $data['root_prefix'] ?? 'clickerp' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Access Key ID</label>
                    <input type="text" name="access_key_id" class="form-control" required autocomplete="off"
                        value="{{ $data['access_key_id'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secret Access Key</label>
                    <input type="password" name="secret_access_key" class="form-control" autocomplete="new-password"
                        placeholder="{{ filled($r2Settings['secret_access_key'] ?? '') ? 'محفوظ — اتركه فارغًا للإبقاء عليه' : 'أدخل المفتاح السري' }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Public URL / Custom Domain <span class="text-muted">(اختياري)</span></label>
                    <input type="url" name="public_url" class="form-control"
                        placeholder="https://files.example.com" value="{{ $data['public_url'] ?? '' }}">
                    <small class="text-muted">عند تركه فارغًا تُنشأ روابط مؤقتة صالحة لمدة 30 دقيقة.</small>
                </div>
                <div class="col-md-4 d-flex align-items-center pt-4">
                    <div class="form-check">
                        <input type="hidden" name="use_path_style_endpoint" value="0">
                        <input class="form-check-input" type="checkbox" name="use_path_style_endpoint" value="1" id="pathStyle"
                            {{ filter_var($data['use_path_style_endpoint'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pathStyle">Path-style endpoint</label>
                    </div>
                </div>
                @if(@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="fa fa-save me-1"></i>حفظ الإعدادات</button>
                    </div>
                @endif
            </form>

            @if(@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <hr>
                <form method="post" action="{{ route('storage.settings.test') }}">
                    @csrf
                    <button class="btn btn-outline-success">
                        <i class="fa fa-plug me-1"></i>اختبار الكتابة والقراءة والحذف
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
