@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.settings') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
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

        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
            <form class="row" action="{{ route('dash.settings.store') }}" method="post" enctype="multipart/form-data">
                @csrf

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.warrenty_period') }} ({{ __('admin.by_days') }})</label>
                    <div class="col-sm-5">
                        <input type="number" name="warrenty_period" value="{{ @$data['warrenty_period'] }}" class="form-control">
                    </div>
                </div>

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.order_expiration_hours') }}</label>
                    <div class="col-sm-5">
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

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.bank_transfer_data') }}</label>
                    <div class="col-sm-5">
                        <textarea id="editor" name="bank_transfer_data">{{ @$data['bank_transfer_data'] }}</textarea>
                    </div>
                </div>

                <hr>

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.sadad_transfer_data') }}</label>
                    <div class="col-sm-5">
                        <textarea id="editor2" name="sadad_transfer_data">{{ @$data['sadad_transfer_data'] }}</textarea>
                    </div>
                </div>

                <hr>

                <!-- Popup Settings -->
                <h4 class="mb-3">{{ __('admin.popup_settings') }}</h4>

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.show_popup') }}</label>
                    <div class="col-sm-5">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_popup" value="1"
                                {{ @$data['show_popup'] === '1' ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('admin.popup_image') }}</label>
                    <div class="col-sm-5">
                        <input type="file" name="popup_image" class="form-control" accept="image/*">
                        @if(@$data['popup_image'])
                            <div class="mt-2">
                                <img src="{{ showFile($data['popup_image']) }}" alt="Current Popup Image" style="max-width: 200px;">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mb-3 row">
                    <div class="col-sm-12 text-center">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                    </div>
                </div>
            </form>
        @endif
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    @include('components.ckeditor')
@endsection

@include('components.sweetalert')
