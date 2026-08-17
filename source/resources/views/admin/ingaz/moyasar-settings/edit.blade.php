@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات ميسر
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::MOYASAR_SETTINGS))
    @php
        $enabled = (bool) old('is_enabled', $moyasarSettings['is_enabled'] ?? false);
        $savedEnabled = (bool) ($moyasarSettings['is_enabled'] ?? false);
        $savedSettings = [
            'MOYASAR_BASE_URL' => $moyasarSettings['base_url'] ?? 'https://api.moyasar.com/v1',
            'MOYASAR_PUBLISHABLE_KEY' => \App\Support\MoyasarSettings::mask($moyasarSettings['publishable_key'] ?? ''),
            'MOYASAR_SECRET_KEY' => \App\Support\MoyasarSettings::mask($moyasarSettings['secret_key'] ?? ''),
            'MOYASAR_WEBHOOK_SECRET' => \App\Support\MoyasarSettings::mask($moyasarSettings['webhook_secret'] ?? ''),
        ];
        $hasMaskedSavedSecret = \App\Support\MoyasarSettings::containsMaskCharacters($moyasarSettings['publishable_key'] ?? '')
            || \App\Support\MoyasarSettings::containsMaskCharacters($moyasarSettings['secret_key'] ?? '')
            || \App\Support\MoyasarSettings::containsMaskCharacters($moyasarSettings['webhook_secret'] ?? '');
    @endphp

    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-credit-card me-2"></i>
                    إعدادات بوابة ميسر
                </h4>
                <div class="text-muted small">
                    هذه القيم تحفظ في قاعدة البيانات وتستخدم مباشرة عند إنشاء روابط دفع عقود الإيجار.
                </div>
            </div>
            <span class="badge {{ $enabled ? 'bg-success' : 'bg-secondary' }}">
                {{ $enabled ? 'مفعلة' : 'غير مفعلة' }}
            </span>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-info border-0 shadow-sm">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="fw-semibold">
                    <i class="fa fa-lock me-1"></i>
                    القيم المحفوظة حالياً في قاعدة البيانات
                </div>
                <span class="badge {{ $savedEnabled ? 'bg-success' : 'bg-secondary' }}">
                    {{ $savedEnabled ? 'مفعلة' : 'غير مفعلة' }}
                </span>
            </div>
            <div class="text-muted small mb-3">
                يتم عرض المفاتيح بشكل مخفي للأمان، والحقول الفارغة تعني عدم وجود قيمة محفوظة.
            </div>
            @if($hasMaskedSavedSecret)
                <div class="alert alert-warning py-2 mb-3">
                    توجد قيمة محفوظة تحتوي على نجوم (*) وليست مفتاحاً حقيقياً. أعد إدخال مفاتيح ميسر الحقيقية قبل إنشاء روابط الدفع.
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                    <tbody>
                        @foreach($savedSettings as $label => $value)
                            <tr>
                                <th class="text-nowrap" style="width: 260px;" dir="ltr">{{ $label }}</th>
                                <td dir="ltr">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">بيانات الربط</h5>
                    <div class="text-muted small">
                        عند تعطيل ميسر لن يظهر خيار ميسر في إنشاء عقد الإيجار ولن يتم إنشاء رابط دفع.
                    </div>
                </div>
                <span class="badge bg-light text-dark">Moyasar</span>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('moyasar-settings.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_enabled" value="0">
                            <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_enabled">تفعيل بوابة ميسر</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="base_url" class="form-label">MOYASAR_BASE_URL <span class="text-danger">*</span></label>
                        <input type="url" id="base_url" name="base_url" class="form-control" value="{{ old('base_url', $moyasarSettings['base_url'] ?? 'https://api.moyasar.com/v1') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="publishable_key" class="form-label">MOYASAR_PUBLISHABLE_KEY</label>
                        <input type="password" id="publishable_key" name="publishable_key" class="form-control" value="{{ old('publishable_key') }}" placeholder="{{ \App\Support\MoyasarSettings::mask($moyasarSettings['publishable_key'] ?? '') }}" autocomplete="new-password">
                        <small class="text-muted">اتركه فارغاً للإبقاء على القيمة الحالية.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="secret_key" class="form-label">MOYASAR_SECRET_KEY</label>
                        <input type="password" id="secret_key" name="secret_key" class="form-control" value="{{ old('secret_key') }}" placeholder="{{ \App\Support\MoyasarSettings::mask($moyasarSettings['secret_key'] ?? '') }}" autocomplete="new-password">
                        <small class="text-muted">اتركه فارغاً للإبقاء على القيمة الحالية.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="webhook_secret" class="form-label">MOYASAR_WEBHOOK_SECRET</label>
                        <input type="password" id="webhook_secret" name="webhook_secret" class="form-control" value="{{ old('webhook_secret') }}" placeholder="{{ \App\Support\MoyasarSettings::mask($moyasarSettings['webhook_secret'] ?? '') }}" autocomplete="new-password">
                        <small class="text-muted">اتركه فارغاً للإبقاء على القيمة الحالية.</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ الإعدادات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-danger">{{ __('admin.no_permissions') }}</div>
@endif
@endsection
