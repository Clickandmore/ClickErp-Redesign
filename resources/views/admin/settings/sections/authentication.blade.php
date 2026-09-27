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

        <div class="col-12 text-end">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_CONTROL, \App\PermKeys::WEBSITE_SETTINGS_CONTROL]))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
