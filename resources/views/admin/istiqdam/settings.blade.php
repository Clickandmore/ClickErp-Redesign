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
                        <label class="form-label" for="establishment_name">English establishment name <span class="text-danger">*</span></label>
                        <input class="form-control @error('establishment_name') is-invalid @enderror" id="establishment_name" name="establishment_name" value="{{ old('establishment_name', $istiqdamSettings?->establishment_name) }}" required autocomplete="organization">
                        <div class="form-text">الاسم الإنجليزي للمنشأة كما هو مسجل لدى تكامل.</div>
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
                        <label class="form-label" for="api_key">API KEY {{ $istiqdamSettings?->api_key ? '' : '*' }}</label>
                        <input class="form-control @error('api_key') is-invalid @enderror" id="api_key" name="api_key" type="password" {{ $istiqdamSettings?->api_key ? '' : 'required' }} autocomplete="new-password" placeholder="{{ $istiqdamSettings?->api_key ? 'محفوظ — اتركه فارغاً للإبقاء عليه' : '' }}">
                        @error('api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 pt-2"><button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk me-1"></i> حفظ إعدادات الربط</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
