@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_customers.form.create_title'))
@section('style')
@include('admin.hourly_customers._style')
@endsection
@section('content')
<div class="container-fluid hc" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">
    <section class="hc-box">
        <div class="hc-head">
            <div class="hc-page-head">
                <span class="hc-icon"><i class="fa fa-user-plus"></i></span>
                <div><h3>{{ __('hourly_customers.form.create_title') }}</h3><p>{{ __('hourly_customers.form.create_sub') }}</p></div>
            </div>
            <a class="hc-btn" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-list"></i> {{ __('hourly_customers.show.back') }}</a>
        </div>
        <div class="hc-body">
            @include('admin.hourly_customers._form', ['action' => route('admin.hourly-customers.store'), 'method' => 'POST', 'readonly' => false])
        </div>
    </section>
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
