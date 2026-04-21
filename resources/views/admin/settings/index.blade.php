@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_additional'))

@section('settings-description', __('admin.settings_section_additional_hint'))

@section('settings-alerts')
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('settings-content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
        <style>
            .automation-grid {
                display: grid;
                gap: 1rem;
            }

            .automation-group {
                border: 1px solid #e9ecef;
                border-radius: 16px;
                padding: 1.25rem;
                background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
            }

            .automation-group__title {
                font-size: 1rem;
                font-weight: 700;
                margin-bottom: 0.25rem;
            }

            .automation-group__hint {
                color: #6c757d;
                font-size: 0.875rem;
                margin-bottom: 1rem;
            }

            .automation-field {
                border: 1px solid #eef1f5;
                border-radius: 12px;
                padding: 0.9rem;
                background: #fff;
                height: 100%;
            }

            .automation-field label {
                font-weight: 600;
                margin-bottom: 0.35rem;
            }

            .automation-field small {
                display: block;
                color: #6c757d;
                margin-top: 0.4rem;
                line-height: 1.5;
            }
        </style>

        <form class="row g-3" action="{{ route('dash.settings.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="section" value="operations">

            <div class="col-12">
                <div class="automation-grid">
                    <div class="automation-group">
                        <div class="automation-group__title">{{ __('admin.automation_settings') }}</div>
                        <div class="automation-group__hint">{{ __('admin.automation_settings_hint') }}</div>

                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.warranty_period_days') }}</label>
                                    <input type="number"
                                           name="warranty_period_days"
                                           value="{{ old('warranty_period_days', $data['warranty_period_days'] ?? 90) }}"
                                           class="form-control @error('warranty_period_days') is-invalid @enderror"
                                           min="1"
                                           max="3650"
                                           required>
                                    @error('warranty_period_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.warranty_period_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.warranty_status_update_time') }}</label>
                                    <input type="time"
                                           name="warranty_status_update_time"
                                           value="{{ old('warranty_status_update_time', $data['warranty_status_update_time'] ?? '01:00') }}"
                                           class="form-control @error('warranty_status_update_time') is-invalid @enderror"
                                           required>
                                    @error('warranty_status_update_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.warranty_status_update_time_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.warranty_initialize_interval_hours') }}</label>
                                    <input type="number"
                                           name="warranty_initialize_interval_hours"
                                           value="{{ old('warranty_initialize_interval_hours', $data['warranty_initialize_interval_hours'] ?? 6) }}"
                                           class="form-control @error('warranty_initialize_interval_hours') is-invalid @enderror"
                                           min="1"
                                           max="24"
                                           required>
                                    @error('warranty_initialize_interval_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.warranty_initialize_interval_hours_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="automation-group">
                        <div class="automation-group__title">{{ __('admin.order_automation_settings') }}</div>
                        <div class="automation-group__hint">{{ __('admin.order_automation_settings_hint') }}</div>

                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.order_expiration_hours') }}</label>
                                    <input type="number"
                                           name="order_expiration_hours"
                                           value="{{ old('order_expiration_hours', $data['order_expiration_hours'] ?? 24) }}"
                                           class="form-control @error('order_expiration_hours') is-invalid @enderror"
                                           min="1"
                                           max="720"
                                           required>
                                    @error('order_expiration_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.order_expiration_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.order_new_deadline_hours') }}</label>
                                    <input type="number"
                                           name="order_new_deadline_hours"
                                           value="{{ old('order_new_deadline_hours', $data['order_new_deadline_hours'] ?? 24) }}"
                                           class="form-control @error('order_new_deadline_hours') is-invalid @enderror"
                                           min="1"
                                           max="720"
                                           required>
                                    @error('order_new_deadline_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.order_new_deadline_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.order_under_process_deadline_hours') }}</label>
                                    <input type="number"
                                           name="order_under_process_deadline_hours"
                                           value="{{ old('order_under_process_deadline_hours', $data['order_under_process_deadline_hours'] ?? 48) }}"
                                           class="form-control @error('order_under_process_deadline_hours') is-invalid @enderror"
                                           min="1"
                                           max="720"
                                           required>
                                    @error('order_under_process_deadline_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.order_under_process_deadline_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.order_urgent_threshold_hours') }}</label>
                                    <input type="number"
                                           name="order_urgent_threshold_hours"
                                           value="{{ old('order_urgent_threshold_hours', $data['order_urgent_threshold_hours'] ?? 2) }}"
                                           class="form-control @error('order_urgent_threshold_hours') is-invalid @enderror"
                                           min="1"
                                           max="720"
                                           required>
                                    @error('order_urgent_threshold_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.order_urgent_threshold_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.old_pending_orders_days') }}</label>
                                    <input type="number"
                                           name="old_pending_orders_days"
                                           value="{{ old('old_pending_orders_days', $data['old_pending_orders_days'] ?? 30) }}"
                                           class="form-control @error('old_pending_orders_days') is-invalid @enderror"
                                           min="1"
                                           max="3650"
                                           required>
                                    @error('old_pending_orders_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.old_pending_orders_days_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="automation-group">
                        <div class="automation-group__title">{{ __('admin.scheduler_settings') }}</div>
                        <div class="automation-group__hint">{{ __('admin.scheduler_settings_hint') }}</div>

                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.orders_timer_interval_minutes') }}</label>
                                    <input type="number"
                                           name="orders_timer_interval_minutes"
                                           value="{{ old('orders_timer_interval_minutes', $data['orders_timer_interval_minutes'] ?? 30) }}"
                                           class="form-control @error('orders_timer_interval_minutes') is-invalid @enderror"
                                           min="1"
                                           max="60"
                                           required>
                                    @error('orders_timer_interval_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.orders_timer_interval_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.orders_expired_handler_interval_minutes') }}</label>
                                    <input type="number"
                                           name="orders_expired_handler_interval_minutes"
                                           value="{{ old('orders_expired_handler_interval_minutes', $data['orders_expired_handler_interval_minutes'] ?? 5) }}"
                                           class="form-control @error('orders_expired_handler_interval_minutes') is-invalid @enderror"
                                           min="1"
                                           max="60"
                                           required>
                                    @error('orders_expired_handler_interval_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.orders_expired_handler_interval_hint') }}</small>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="automation-field">
                                    <label class="form-label">{{ __('admin.orders_auto_cancel_interval_minutes') }}</label>
                                    <input type="number"
                                           name="orders_auto_cancel_interval_minutes"
                                           value="{{ old('orders_auto_cancel_interval_minutes', $data['orders_auto_cancel_interval_minutes'] ?? 60) }}"
                                           class="form-control @error('orders_auto_cancel_interval_minutes') is-invalid @enderror"
                                           min="1"
                                           max="60"
                                           required>
                                    @error('orders_auto_cancel_interval_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small>{{ __('admin.orders_auto_cancel_interval_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="automation-group">
                    <div class="automation-group__title">{{ __('admin.payment_notes_settings') }}</div>
                    <div class="automation-group__hint">{{ __('admin.payment_notes_settings_hint') }}</div>

                    <div class="row g-3 align-items-start">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('admin.bank_transfer_data') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <textarea id="editor" name="bank_transfer_data">{{ @$data['bank_transfer_data'] }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="automation-group">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('admin.sadad_transfer_data') }}</label>
                        </div>
                        <div class="col-lg-8">
                            <textarea id="editor2" name="sadad_transfer_data">{{ @$data['sadad_transfer_data'] }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">{{ __('admin.announcements_section_redirect_hint') }}</div>
                <a href="{{ route('dash.settings.announcements') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('admin.go_to_announcements_section') }}
                </a>
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
            </div>
        </form>
    @endif
@endsection

@section('js')
    @include('components.ckeditor')
@endsection
