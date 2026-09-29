@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_external_auth'))

@section('settings-description')
    {{ __('admin.external_auth_description') }}
@endsection

@section('settings-content')
    @php
        $selectedMode = old(
            'external_auth_mode',
            $settings->external_auth_mode ?: \App\Support\ExternalAuthSettings::MODE_PASSWORD
        );
    @endphp

    <form class="row g-3 from-submit-global" method="post"
        action="{{ route('settings.update', $settings->id) }}">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="alert alert-info">
                {{ __('admin.external_auth_legacy_default_hint') }}
            </div>
        </div>

        <div class="col-12">
            <h5>{{ __('admin.external_auth_heading') }}</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="external_auth_password">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="external_auth_mode"
                                id="external_auth_password" value="password"
                                {{ $selectedMode === 'password' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.external_auth_password_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.external_auth_password_mode_hint') }}
                        </small>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="external_auth_otp">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="external_auth_mode"
                                id="external_auth_otp" value="otp"
                                {{ $selectedMode === 'otp' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.external_auth_otp_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.external_auth_otp_mode_hint') }}
                        </small>
                    </label>
                </div>
            </div>
        </div>

        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
            <div class="col-12 mt-4">
                <h5>{{ __('admin.app_auth_heading') }}</h5>
                <p class="text-muted">{{ __('admin.app_auth_description') }}</p>
                @php($appMode = old('app_auth_mode', $settings->app_auth_mode ?: \App\Support\AppAuthSettings::MODE_PASSWORD))
                <div class="row g-3">
                    @foreach (['password', 'otp'] as $mode)
                        <div class="col-md-6">
                            <label class="card h-100 border p-3 cursor-pointer" for="app_auth_{{ $mode }}">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="app_auth_mode"
                                        id="app_auth_{{ $mode }}" value="{{ $mode }}"
                                        {{ $appMode === $mode ? 'checked' : '' }}>
                                    <span class="form-check-label fw-bold">{{ __('admin.external_auth_' . $mode . '_mode') }}</span>
                                </div>
                                <small class="text-muted mt-2">{{ __('admin.external_auth_' . $mode . '_mode_hint') }}</small>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="col-12 text-end">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_CONTROL, \App\PermKeys::WEBSITE_SETTINGS_CONTROL]))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
