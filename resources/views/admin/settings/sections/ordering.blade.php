@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_external_order'))

@section('settings-description')
    {{ __('admin.external_order_description') }}
@endsection

@section('settings-content')
    @php
        $selectedMode = old(
            'external_order_mode',
            $settings->external_order_mode ?: \App\Support\ExternalOrderSettings::MODE_AUTHENTICATED
        );
    @endphp

    <form class="row g-3 from-submit-global" method="post"
        action="{{ route('settings.update', $settings->id) }}">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="alert alert-info">
                {{ __('admin.external_order_legacy_default_hint') }}
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="external_order_authenticated">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="external_order_mode"
                                id="external_order_authenticated" value="authenticated"
                                {{ $selectedMode === 'authenticated' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.external_order_authenticated_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.external_order_authenticated_mode_hint') }}
                        </small>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="external_order_simplified">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="external_order_mode"
                                id="external_order_simplified" value="simplified"
                                {{ $selectedMode === 'simplified' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.external_order_simplified_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.external_order_simplified_mode_hint') }}
                        </small>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
