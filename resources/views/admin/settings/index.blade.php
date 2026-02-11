@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_additional'))

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
        <form class="row g-3" action="{{ route('dash.settings.store') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="col-12">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.warrenty_period') }} ({{ __('admin.by_days') }})</label>
                    </div>
                    <div class="col-lg-8">
                        <input type="number" name="warrenty_period" value="{{ @$data['warrenty_period'] }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.order_expiration_hours') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <input type="number"
                               name="order_expiration_hours"
                               value="{{ old('order_expiration_hours', \App\Models\ingaz\Setting::getValue('order_expiration_hours', 24)) }}"
                               class="form-control @error('order_expiration_hours') is-invalid @enderror"
                               min="1"
                               max="720">
                        @error('order_expiration_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">{{ __('admin.order_expiration_hint') }}</small>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.bank_transfer_data') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <textarea id="editor" name="bank_transfer_data">{{ @$data['bank_transfer_data'] }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <hr>
            </div>

            <div class="col-12">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.sadad_transfer_data') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <textarea id="editor2" name="sadad_transfer_data">{{ @$data['sadad_transfer_data'] }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <hr>
            </div>

            <div class="col-12">
                <h6 class="text-muted mb-2">{{ __('admin.popup_settings') }}</h6>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.show_popup') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_popup" value="1"
                                {{ @$data['show_popup'] === '1' ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">{{ __('admin.popup_image') }}</label>
                    </div>
                    <div class="col-lg-8">
                        <input type="file" name="popup_image" class="form-control" accept="image/*">
                        @if(@$data['popup_image'])
                            <div class="mt-2">
                                <img src="{{ showFile($data['popup_image']) }}" alt="Current Popup Image" style="max-width: 200px;">
                            </div>
                        @endif
                    </div>
                </div>
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
