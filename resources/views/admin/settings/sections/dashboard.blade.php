@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.settings_section_dashboard') }}
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">{{ __('admin.settings_section_dashboard') }}</h4>
            <div class="text-muted small">{{ __('admin.settings_section_dashboard_hint') }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                <form class="row g-3 from-submit-global" method="post"
                      action="{{ route('settings.update', $settings->id) }}"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="col-12">
                        <h6 class="text-muted mb-2">بيانات لوحة التحكم</h6>
                        <div class="row g-3">
                            @foreach (config('translatable.locales') as $locale)
                                <div class="col-md-6">
                                    <label class="form-label">اسم لوحة التحكم {{ getFieldLanguage($locale) }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <div class="form-icon position-absolute">
                                            <i class="fa fa-gauge"></i>
                                        </div>
                                        <input type="text" name="dashboard_name:{{ $locale }}"
                                               value="{{ $settings->translate($locale)->dashboard_name ?? '' }}"
                                               class="form-control"
                                               placeholder="{{ $settings->translate($locale)->website_name ?? config('app.name') }}">
                                    </fieldset>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <hr>
                    </div>

                    <div class="col-12">
                        <h6 class="text-muted mb-2">هوية لوحة التحكم</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="mb-2">شعار لوحة التحكم</h6>
                                        <p class="text-muted small mb-2">يستخدم في الهيدر وشاشة تسجيل الدخول للوحة التحكم فقط.</p>
                                        <input type="file" data-default-file="{{ showFile($settings->admin_logo) ?? '' }}"
                                               name="admin_logo" class="dropify">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="mb-2">خلفية تسجيل دخول لوحة التحكم</h6>
                                        <p class="text-muted small mb-2">تظهر في صفحة <code>/dashboard/login</code> فقط.</p>
                                        <input type="file" data-default-file="{{ showFile($settings->admin_login_background) ?? '' }}"
                                               name="admin_login_background" class="dropify">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                            <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
                        @endif
                    </div>
                </form>
            @else
                @include('components.no_permissions')
            @endif
        </div>
    </div>
@endsection
