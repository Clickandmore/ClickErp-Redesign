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
        // القسم يظهر بعد تشغيل الترحيل الذي يضيف العمود، حتى لا تنكسر النسخ غير المُرحّلة.
        $rentalModeAvailable = \Illuminate\Support\Facades\Schema::hasColumn('settings', 'rental_order_mode');
        $selectedRentalMode = old(
            'rental_order_mode',
            ($rentalModeAvailable ? $settings->rental_order_mode : null) ?: \App\Support\RentalOrderSettings::MODE_FULL
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

        @if (\Illuminate\Support\Facades\Schema::hasColumn('settings', 'external_booking_employee_selection'))
        <div class="col-12">
            <label class="form-label" for="external_booking_employee_selection">{{ __('admin.external_booking_employee_selection') }}</label>
            <select class="form-select" name="external_booking_employee_selection" id="external_booking_employee_selection">
                <option value="1" {{ old('external_booking_employee_selection', $settings->external_booking_employee_selection ?? true) ? 'selected' : '' }}>{{ __('admin.settings_enabled') }}</option>
                <option value="0" {{ !old('external_booking_employee_selection', $settings->external_booking_employee_selection ?? true) ? 'selected' : '' }}>{{ __('admin.settings_disabled') }}</option>
            </select>
            <small class="text-muted">{{ __('admin.external_booking_employee_selection_hint') }}</small>
        </div>
        @endif

        @if ($rentalModeAvailable)
        <div class="col-12">
            <hr class="my-2">
            <h6 class="fw-bold mb-1">{{ __('admin.rental_order_title') }}</h6>
            <div class="text-muted small mb-3">{{ __('admin.rental_order_description') }}</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="rental_order_full">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rental_order_mode"
                                id="rental_order_full" value="full"
                                {{ $selectedRentalMode === 'full' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.rental_order_full_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.rental_order_full_mode_hint') }}
                        </small>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="card h-100 border p-3 cursor-pointer" for="rental_order_simplified">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rental_order_mode"
                                id="rental_order_simplified" value="simplified"
                                {{ $selectedRentalMode === 'simplified' ? 'checked' : '' }}>
                            <span class="form-check-label fw-bold">
                                {{ __('admin.rental_order_simplified_mode') }}
                            </span>
                        </div>
                        <small class="text-muted mt-2">
                            {{ __('admin.rental_order_simplified_mode_hint') }}
                        </small>
                    </label>
                </div>
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
