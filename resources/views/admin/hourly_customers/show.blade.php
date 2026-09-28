@extends('admin.layout.indexs.index')
@section('page-title', $customer->name.' — '.__('hourly_customers.title'))
@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@include('admin.hourly_customers._style')
@endsection
@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
    $date = fn ($value, $format = 'd.m.Y') => $value ? \Carbon\Carbon::parse($value)->format($format) : '—';
    $icons = ['profile' => 'fa-user', 'addresses' => 'fa-location-dot', 'balance' => 'fa-wallet', 'calendar' => 'fa-calendar-days', 'orders' => 'fa-cart-shopping', 'visits' => 'fa-calendar-check',
        'documents' => 'fa-id-card', 'favorites' => 'fa-heart', 'ratings' => 'fa-star', 'report' => 'fa-file-invoice', 'merge' => 'fa-code-merge', 'logs' => 'fa-clock-rotate-left'];
    $mobile = \App\Support\PhoneNumber::saudiMobile($customer->phone);
    $phone = $mobile ? '+966'.$mobile : (\App\Support\PhoneNumber::digits($customer->phone) ?: null);
    $bucketIcons = ['completed' => 'fa-circle-check', 'pending' => 'fa-hourglass-half', 'cancelled' => 'fa-ban'];
    $hours = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp
<div class="container-fluid hc" dir="{{ $rtl ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">
    @if(session('success'))<div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2"><span>{{ session('success') }}</span>@if(session('receipt_url'))<a class="btn btn-sm btn-success" target="_blank" href="{{ session('receipt_url') }}"><i class="fa fa-receipt"></i> عرض سند القبض</a>@endif</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if($deleted)
        <div class="hc-banner">
            <span><i class="fa fa-user-xmark"></i> {{ $profile->merged_into_id ? $c('show.merged_banner', ['id' => $profile->merged_into_id]) : $c('show.deleted_banner', ['date' => $date($profile->deleted_at, 'd.m.Y H:i')]) }}</span>
            @if($profile->merged_into_id)
                <a class="hc-btn is-sm" href="{{ route('admin.hourly-customers.show', $profile->merged_into_id) }}"><i class="fa fa-arrow-up-right-from-square"></i> #{{ $profile->merged_into_id }}</a>
            @elseif($canRestore)
                <form method="POST" action="{{ route('admin.hourly-customers.restore', $customer->id) }}">@csrf @method('PUT')<button class="hc-btn is-green is-sm"><i class="fa fa-rotate-left"></i> {{ $c('show.restore') }}</button></form>
            @endif
        </div>
    @endif

    {{-- مكتمل / قيد الانتظار / ملغي --}}
    <div class="hc-buckets">
        @foreach($buckets as $key => $bucket)
            <div class="hc-bucket is-{{ $key }}">
                <h4><i class="fa {{ $bucketIcons[$key] ?? 'fa-circle' }}"></i> {{ $c('show.buckets.'.$key) }}</h4>
                <div class="hc-bucket-stats">
                    <div><b>{{ $bucket['orders'] }}</b><span>{{ $c('show.bucket_orders') }}</span></div>
                    <div><b>{{ $bucket['visits'] }}</b><span>{{ $c('show.bucket_visits') }}</span></div>
                    <div><b>{{ $hours($bucket['hours']) }}</b><span>{{ $c('show.bucket_hours') }}</span></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="hc-layout">
        {{-- العمود الجانبي --}}
        <aside class="hc-box hc-side">
            <div class="hc-who">
                <h2><i class="fa fa-user-circle" style="color:var(--brand)"></i> {{ $customer->name }}
                    @if($profile->verified_at)<span class="hc-badge" style="background:#dcfce7;color:#166534"><i class="fa fa-circle-check"></i> {{ $c('verified') }}</span>@endif
                </h2>
                @if($profile->group)<span class="hc-badge is-group mt-1"><i class="fa fa-users"></i> {{ $profile->group->name }}</span>@endif
                @if($customer->is_blocked)<span class="hc-badge is-blocked mt-1">{{ $c('show.blocked') }}</span>@endif
            </div>
            @foreach(array_filter(['fa-envelope' => $customer->email, 'fa-phone' => $phone]) as $icon => $value)
                <div class="hc-copy"><span dir="ltr"><i class="fa {{ $icon }}" style="color:var(--muted)"></i> {{ $value }}</span>
                    <button type="button" data-copy="{{ $value }}" title="{{ $c('show.copy') }}" aria-label="{{ $c('show.copy') }}"><i class="fa fa-copy"></i></button></div>
            @endforeach
            <div class="hc-balance">
                <div><span class="hc-muted">{{ $c('show.balance') }}</span>
                    <b class="hc-money d-block {{ $stats['balance'] > 0 ? 'is-pos' : ($stats['balance'] < 0 ? 'is-neg' : '') }}">{{ $money($stats['balance']) }} {{ $c('sar') }}</b></div>
                <div class="d-flex flex-column gap-1">
                    @if($canAdjust)<button type="button" class="hc-btn is-sm" data-bs-toggle="modal" data-bs-target="#hc-adjust-modal"><i class="fa fa-pen"></i> {{ $c('show.adjust') }}</button>@endif
                    @if($canBalance)<button type="button" class="hc-btn is-green is-sm" data-bs-toggle="modal" data-bs-target="#hc-deposit-modal"><i class="fa fa-wallet"></i> {{ $c('show.add_balance') }}</button>@endif
                </div>
            </div>
            <div>
                <div class="hc-stat"><span><i class="fa fa-piggy-bank"></i> {{ $c('show.deposits') }}</span><b class="hc-money is-pos">{{ $money($stats['deposits']) }} {{ $c('sar') }}</b></div>
                <div class="hc-stat"><span><i class="fa fa-sack-dollar"></i> {{ $c('show.revenue') }}</span><b class="hc-money is-pos">{{ $money($stats['revenue']) }} {{ $c('sar') }}</b></div>
                <div class="hc-stat"><span><i class="fa fa-user-plus"></i> {{ $c('show.signup') }}</span><b>{{ $date($customer->created_at) }}<small>{{ $c('show.source') }}: {{ $sourceLabel ?: '—' }}</small></b></div>
                <div class="hc-stat"><span><i class="fa fa-cart-shopping"></i> {{ $c('show.first_order') }}</span><b>{{ $date($stats['first_order']) }}<small>{{ $c('show.first_visit') }}: {{ $date($stats['first_visit'], 'H:i d.m.Y') }}</small></b></div>
                <div class="hc-stat"><span><i class="fa fa-bag-shopping"></i> {{ $c('show.last_order') }}</span><b>{{ $date($stats['last_order']) }}<small>{{ $c('show.last_visit') }}: {{ $date($stats['last_visit'], 'H:i d.m.Y') }}</small></b></div>
                @if($profile->payment_term)
                    <div class="hc-stat"><span><i class="fa fa-file-invoice-dollar"></i> {{ $c('form.payment_term') }}</span><b>{{ $c('payment_terms.'.$profile->payment_term) }}</b></div>
                @endif
                <div class="hc-stat"><span><i class="fa fa-circle-check"></i> {{ $c('show.verified_toggle') }}</span>
                    <b><div class="form-check form-switch m-0"><input class="form-check-input hc-switch" type="checkbox" role="switch" aria-label="{{ $c('show.verified_toggle') }}" {{ $profile->verified_at ? 'checked' : '' }}
                        @if($canEdit) data-verify="{{ route('admin.hourly-customers.verify', $customer->id) }}" @else disabled @endif></div></b></div>
            </div>
            <div class="hc-toolbar">
                <a class="hc-btn is-sm" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-list"></i> {{ $c('show.back') }}</a>
                @if(!$deleted && AdminGuardHasPerm('hourly_orders_create') && !$customer->is_blocked)<a class="hc-btn is-primary is-sm" href="{{ route('admin.hourly-orders.wizard') }}"><i class="fa fa-cart-plus"></i> {{ $c('show.new_order') }}</a>@endif
                @if($canNotify)<button type="button" class="hc-btn is-sm" data-bs-toggle="modal" data-bs-target="#hc-notify-modal"><i class="fa fa-bell"></i> {{ $c('show.notify') }}</button>@endif
                @if($canDelete)<button type="button" class="hc-btn is-danger is-sm" data-bs-toggle="modal" data-bs-target="#hc-delete-modal"><i class="fa fa-trash"></i> {{ $c('show.delete') }}</button>@endif
            </div>
        </aside>

        {{-- التبويبات --}}
        <section class="hc-box">
            <nav class="hc-tabs" aria-label="{{ $c('title') }}">
                @foreach(\App\Http\Controllers\Hourly\CustomerController::TABS as $key)
                    @continue($key === 'merge' && !$canMerge)
                    <a class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif href="{{ route('admin.hourly-customers.show', [$customer->id, $key]) }}"><i class="fa {{ $icons[$key] ?? 'fa-circle' }}"></i> {{ $c('show.tabs.'.$key) }}</a>
                @endforeach
            </nav>
            <div class="hc-body">
                @if($tab === 'profile')
                    @unless($profile->exists)<div class="hc-note is-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>{{ $c('show.missing_profile') }}</div></div>@endunless
                    @if(!$canEdit && !$deleted)<div class="hc-note is-warn mb-3"><i class="fa fa-lock mt-1"></i><div>{{ $c('show.readonly') }}</div></div>@endif
                    @include('admin.hourly_customers._form', ['action' => route('admin.hourly-customers.update', $customer->id), 'method' => 'PUT', 'readonly' => !$canEdit || $deleted])
                @else
                    @include('admin.hourly_customers.tabs.'.$tab, $tabData)
                @endif
            </div>
        </section>
    </div>

    @if($canBalance)@include('admin.hourly_customers._deposit')@endif
    @include('admin.hourly_customers._actions')
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
@if($tab === 'addresses')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@include('admin.hourly._map-provider')
@endif
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
