@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.settings') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
            <form class="row" action="{{ route('dash.settings.store') }}" method="post">
                @csrf
        @endif

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">{{ __('admin.warrenty_period') }} ({{ __('admin.by_days') }})</label>
            <div class="col-sm-5">
                <input type="number" name="warrenty_period" value="{{ @$data['warrenty_period'] }}" class="form-control">
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">{{ __('admin.bank_transfer_data') }}</label>
            <div class="col-sm-5">
                <textarea id="editor" name="bank_transfer_data"  >{{ @$data['bank_transfer_data'] }}</textarea>
            </div>
        </div>

        <hr>

        <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">{{ __('admin.sadad_transfer_data') }}</label>
            <div class="col-sm-5">
                <textarea id="editor2" name="sadad_transfer_data" >{{ @$data['sadad_transfer_data'] }}</textarea>
            </div>
        </div>

        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
            <div class="mb-3 row">
                <div class="mt-10 text-center col-sm-12 text-sm-end">
                    <button type="submit" class="btn btn-fr-bg">{{ __('admin.confirm') }}</button>
                </div>
            </div>
            </form>
        @endif
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
@endsection

@include('components.sweetalert')
@include('components.ckeditor')
