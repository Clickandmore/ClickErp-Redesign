@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_customers.merge.duplicates'))
@section('style')
@include('admin.hourly_customers._style')
@endsection
@section('content')
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
@endphp
<div class="container-fluid hc" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <section class="hc-box">
        <div class="hc-head">
            <h3><i class="fa fa-clone"></i> {{ $c('merge.duplicates') }} <span class="hc-badge">{{ $groups->total() }}</span></h3>
            <a class="hc-btn" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-list"></i> {{ $c('show.back') }}</a>
        </div>
        <div class="hc-body">
            <p class="hc-muted">{{ $c('merge.duplicates_sub') }}</p>
            <div class="hc-note mb-3"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('merge.hint') }}</div></div>
            @forelse($groups as $group)
                <form method="GET" action="{{ route('admin.hourly-customers.merge-form') }}" class="hc-group-card mb-3" data-merge-select>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="hc-chips">@foreach($group['reasons'] as $reason)<span class="hc-badge">{{ $c('merge.reasons.'.$reason) }}</span>@endforeach</div>
                        @if($canMerge)<button class="hc-btn is-primary is-sm"><i class="fa fa-code-merge"></i> {{ $c('merge.merge') }}</button>@endif
                    </div>
                    @foreach($group['members'] as $member)
                        <label class="hc-member m-0">
                            <input type="checkbox" name="ids[]" value="{{ $member['id'] }}" checked aria-label="#{{ $member['id'] }}" {{ $canMerge ? '' : 'disabled' }}>
                            <span><a href="{{ route('admin.hourly-customers.show', $member['id']) }}" target="_blank" rel="noopener"><b>#{{ $member['id'] }}</b> {{ $member['name'] }}</a>
                                <span class="hc-muted d-block" dir="ltr" style="text-align:start">{{ $member['phone'] }}{{ $member['email'] ? ' · '.$member['email'] : '' }}{{ $member['national_num'] ?? null ? ' · '.$member['national_num'] : '' }}</span></span>
                            <span class="hc-muted text-end">{{ $c('merge.orders', ['count' => $member['orders']]) }}<br>
                                {{ $money($member['hourly_wallet_balance'] ?? 0) }} {{ $c('sar') }} · {{ \Carbon\Carbon::parse($member['created_at'])->format('d.m.Y') }}
                                @if(!empty($member['register_from']))<br>{{ $sources[$member['register_from']] ?? $member['register_from'] }}@endif</span>
                        </label>
                    @endforeach
                </form>
            @empty
                <div class="hc-empty">{{ $c('merge.empty') }}</div>
            @endforelse
            <div class="mt-2">{{ $groups->links('admin.hourly_customers._pagination') }}</div>
        </div>
    </section>
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
