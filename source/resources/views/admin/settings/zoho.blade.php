@extends('admin.settings.sections.layout')

@section('settings-title', 'إعدادات Zoho SalesIQ')
@section('settings-description', 'إدارة بيانات الربط لكل شركة من مكان واحد داخل إعدادات النظام.')

@section('settings-alerts')
    @if ($branches->isEmpty())
        <div class="alert alert-warning">
            لا توجد فروع/شركات مسجلة حتى الآن. أضف شركة أولاً حتى يمكن حفظ إعدادات Zoho عليها.
        </div>
    @elseif (!empty($missingConfig))
        <div class="alert alert-warning">
            <strong>بيانات الربط الفعلية غير مكتملة للشركة الحالية.</strong>
            <div class="mt-2">
                @foreach ($missingConfig as $key)
                    <code class="me-2">{{ $key }}</code>
                @endforeach
            </div>
        </div>
    @endif
@endsection

@section('settings-content')
    @if ($branches->isNotEmpty())
        @if ($branches->count() > 1)
            <form method="get" action="{{ route('dash.settings.zoho') }}" class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">الشركة / الفرع</label>
                    <select name="branch_id" class="form-select" onchange="this.form.submit()">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $currentBranch?->id === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="text-muted small">
                        يتم حفظ الإعدادات على الشركة المختارة، مع استخدام الإعداد العام من `.env` لأي حقل تتركه فارغاً.
                    </div>
                </div>
            </form>
        @else
            <div class="alert alert-light border">
                الشركة الحالية: <strong>{{ $currentBranch?->name }}</strong>
            </div>
        @endif

        <form method="post" action="{{ route('dash.settings.zoho.update') }}" class="row g-3">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $currentBranch?->id }}">

            <div class="col-md-4">
                <label class="form-label">Portal / Screen Name</label>
                <input type="text"
                       name="zoho_salesiq_settings[screen_name]"
                       class="form-control @error('zoho_salesiq_settings.screen_name') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.screen_name', data_get($storedZohoSettings, 'screen_name', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'screen_name', '') }}">
                @error('zoho_salesiq_settings.screen_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">SalesIQ Base URL</label>
                <input type="url"
                       name="zoho_salesiq_settings[salesiq_base_url]"
                       class="form-control @error('zoho_salesiq_settings.salesiq_base_url') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.salesiq_base_url', data_get($storedZohoSettings, 'salesiq_base_url', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'salesiq_base_url', 'https://salesiq.zoho.sa') }}">
                @error('zoho_salesiq_settings.salesiq_base_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Accounts Base URL</label>
                <input type="url"
                       name="zoho_salesiq_settings[accounts_base_url]"
                       class="form-control @error('zoho_salesiq_settings.accounts_base_url') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.accounts_base_url', data_get($storedZohoSettings, 'accounts_base_url', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'accounts_base_url', 'https://accounts.zoho.sa') }}">
                @error('zoho_salesiq_settings.accounts_base_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Client ID</label>
                <input type="text"
                       name="zoho_salesiq_settings[client_id]"
                       class="form-control @error('zoho_salesiq_settings.client_id') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.client_id', data_get($storedZohoSettings, 'client_id', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'client_id', '') }}">
                @error('zoho_salesiq_settings.client_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Client Secret</label>
                <input type="password"
                       name="zoho_salesiq_settings[client_secret]"
                       class="form-control @error('zoho_salesiq_settings.client_secret') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.client_secret') }}"
                       placeholder="{{ data_get($storedZohoSettings, 'client_secret') ? 'محفوظ - اتركه فارغاً للإبقاء عليه' : 'يستخدم الإعداد العام إن وجد' }}">
                @error('zoho_salesiq_settings.client_secret')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Refresh Token</label>
                <input type="password"
                       name="zoho_salesiq_settings[refresh_token]"
                       class="form-control @error('zoho_salesiq_settings.refresh_token') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.refresh_token') }}"
                       placeholder="{{ data_get($storedZohoSettings, 'refresh_token') ? 'محفوظ - اتركه فارغاً للإبقاء عليه' : 'يستخدم الإعداد العام إن وجد' }}">
                @error('zoho_salesiq_settings.refresh_token')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-8">
                <label class="form-label">Widget URL</label>
                <input type="url"
                       name="zoho_salesiq_settings[widget_url]"
                       class="form-control @error('zoho_salesiq_settings.widget_url') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.widget_url', data_get($storedZohoSettings, 'widget_url', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'widget_url', 'https://salesiq.zohopublic.sa/widget?wc=...') }}">
                <small class="text-muted">سكريبت الموقع العام يستخدم إعداد الفرع الرئيسي كقيمة افتراضية.</small>
                @error('zoho_salesiq_settings.widget_url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Timeout بالثواني</label>
                <input type="number"
                       min="5"
                       max="120"
                       name="zoho_salesiq_settings[timeout]"
                       class="form-control @error('zoho_salesiq_settings.timeout') is-invalid @enderror"
                       value="{{ old('zoho_salesiq_settings.timeout', data_get($storedZohoSettings, 'timeout', '')) }}"
                       placeholder="{{ data_get($zohoEffectiveSettings, 'timeout', 20) }}">
                @error('zoho_salesiq_settings.timeout')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.zoho-salesiq.conversations.index', $currentBranch ? ['branch_id' => $currentBranch->id] : []) }}"
                       class="btn btn-outline-secondary">
                        اختبار المحادثات
                    </a>
                    <button type="submit" class="btn btn-primary">
                        حفظ إعدادات Zoho
                    </button>
                </div>
            </div>
        </form>
    @endif
@endsection
