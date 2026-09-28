@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_customers.title'))
@section('style')
@include('admin.hourly_customers._style')
@endsection
@section('content')
@php
    $rtl = app()->getLocale() === 'ar';
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $f = fn (string $key, $default = '') => (string) ($filters[$key] ?? $default);
    $money = fn ($value) => number_format((float) $value, 2);
@endphp
<div class="container-fluid hc" dir="{{ $rtl ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">

    {{-- الفلاتر --}}
    <form method="GET" action="{{ route('admin.hourly-customers.index') }}" class="hc-box" id="hc-filters" role="search">
        <div class="hc-head">
            <h3><i class="fa fa-filter"></i> {{ $c('filters.title') }}</h3>
            <button type="button" class="hc-collapse" data-bs-toggle="collapse" data-bs-target="#hc-filter-body" aria-expanded="true" aria-controls="hc-filter-body" aria-label="{{ $c('filters.toggle') }}"><i class="fa fa-circle-chevron-up"></i></button>
        </div>
        <div class="collapse show" id="hc-filter-body">
            <div class="hc-body hc-filters">
                <div class="hc-row">
                    <div class="hc-field is-wide">
                        <label for="hc-search"><i class="fa fa-magnifying-glass"></i> {{ $c('filters.search') }}</label>
                        <input class="form-control {{ $f('search') !== '' ? 'is-set' : '' }}" id="hc-search" name="search" value="{{ $f('search') }}" placeholder="{{ $c('filters.search_hint') }}">
                    </div>
                    <div class="hc-field is-wide">
                        <span class="hc-label">{{ $c('filters.balance') }}</span>
                        <div class="hc-seg" role="radiogroup" aria-label="{{ $c('filters.balance') }}">
                            @foreach(['all' => 'fa-users', 'positive' => 'fa-plus', 'negative' => 'fa-minus', 'non_zero' => 'fa-not-equal'] as $key => $icon)
                                <label class="{{ $key === 'positive' ? 'is-pos' : ($key === 'negative' ? 'is-neg' : '') }}"><input type="radio" name="balance" value="{{ $key }}" {{ $f('balance', 'all') === $key ? 'checked' : '' }}><span><i class="fa {{ $icon }}"></i> {{ $c('balance_states.'.$key) }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="hc-field">
                        <span class="hc-label">{{ $c('filters.type') }}</span>
                        <div class="hc-seg" role="radiogroup" aria-label="{{ $c('filters.type') }}">
                            <label><input type="radio" name="type" value="" {{ $f('type') === '' ? 'checked' : '' }}><span><i class="fa fa-users"></i> {{ $c('all') }}</span></label>
                            <label><input type="radio" name="type" value="individual" {{ $f('type') === 'individual' ? 'checked' : '' }}><span><i class="fa fa-user"></i> {{ $c('types.individual') }}</span></label>
                            <label><input type="radio" name="type" value="company" {{ $f('type') === 'company' ? 'checked' : '' }}><span><i class="fa fa-building"></i> {{ $c('types.company') }}</span></label>
                        </div>
                    </div>
                    <div class="hc-field is-wide">
                        <span class="hc-label">{{ $c('filters.activity') }}</span>
                        <div class="hc-seg" role="radiogroup" aria-label="{{ $c('filters.activity') }}">
                            @foreach(['all' => 'fa-users', 'inactive_2m' => 'fa-clock', 'no_orders' => 'fa-circle-xmark'] as $key => $icon)
                                <label><input type="radio" name="activity" value="{{ $key }}" {{ $f('activity', 'all') === $key ? 'checked' : '' }}><span><i class="fa {{ $icon }}"></i> {{ $c('activities.'.$key) }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    @php
                        $selects = [
                            'group_id' => [$c('filters.group'), $groups->mapWithKeys(fn ($g) => [$g->id => $g->name])->put('none', $c('none'))],
                            'marketing_source' => [$c('filters.marketing_source'), collect(\App\Models\Hourly\HourlyCustomerProfile::MARKETING_SOURCES)->mapWithKeys(fn ($k) => [$k => $c('marketing.'.$k)])->put('none', $c('none'))],
                            'city_id' => [$c('filters.city'), $cities->mapWithKeys(fn ($city) => [$city->id => $city->name])],
                            'district_id' => [$c('filters.district'), $districtOptions->mapWithKeys(fn ($d) => [$d->id => $d->name])],
                            'register_from' => [$c('filters.register_from'), collect($sources)->put('none', $c('none'))],
                            'verified' => [$c('filters.verified'), collect(['1' => $c('verified'), '0' => $c('unverified')])],
                        ];
                    @endphp
                    @foreach($selects as $key => [$label, $items])
                        <div class="hc-field">
                            <label for="hc-{{ $key }}">{{ $label }}</label>
                            <select class="form-select {{ $f($key) !== '' ? 'is-set' : '' }}" id="hc-{{ $key }}" name="{{ $key }}">
                                <option value="">{{ $c('all') }}</option>
                                @foreach($items as $value => $text)
                                    <option value="{{ $value }}" {{ $f($key) === (string) $value ? 'selected' : '' }} @if($key === 'district_id') data-city="{{ $districtOptions->firstWhere('id', $value)?->city_id }}" @endif>{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                <div class="hc-actions">
                    <button class="hc-btn is-primary"><i class="fa fa-magnifying-glass"></i> {{ $c('filters.apply') }}</button>
                    <a class="hc-btn" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-rotate"></i> {{ $c('filters.clear') }}</a>
                    @if($canExport)<a class="hc-btn is-amber" href="{{ route('admin.hourly-customers.export', request()->query()) }}"><i class="fa fa-file-excel"></i> {{ $c('filters.export') }}</a>@endif
                    <label class="hc-check ms-auto"><input type="checkbox" name="show_deleted" value="1" {{ !empty($filters['show_deleted']) ? 'checked' : '' }}> <i class="fa fa-user-xmark"></i> {{ $c('filters.show_deleted') }}</label>
                    <input type="hidden" name="per_page" value="{{ $f('per_page', 25) }}">
                    @foreach(['sort', 'direction'] as $key)@if($f($key) !== '')<input type="hidden" name="{{ $key }}" value="{{ $f($key) }}">@endif @endforeach
                </div>
            </div>
        </div>
    </form>

    {{-- القائمة --}}
    <section class="hc-box">
        <div class="hc-head">
            <h3><i class="fa fa-address-book"></i> {{ $c('list.title') }} <span class="hc-badge is-count">{{ $c('list.records', ['count' => number_format($customers->total())]) }}</span></h3>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="hc-pill is-pos"><i class="fa fa-arrow-trend-up"></i> {{ $c('list.positive') }} <b class="hc-money">{{ $money($balances['positive']) }}</b> {{ $c('sar') }}</span>
                <span class="hc-pill is-neg"><i class="fa fa-arrow-trend-down"></i> {{ $c('list.negative') }} <b class="hc-money">{{ $money($balances['negative']) }}</b> {{ $c('sar') }}</span>
                @if($canCreate)<a class="hc-btn is-green" href="{{ route('admin.hourly-customers.create') }}"><i class="fa fa-user-plus"></i> {{ $c('list.create') }}</a>@endif
            </div>
        </div>
        @if($customers->isEmpty())
            <div class="hc-empty"><i class="fa fa-user-slash fa-2x d-block mb-2"></i> {{ $c('list.empty') }}</div>
        @else
            <div class="table-responsive">
                <table class="hc-table">
                    @php
                        // رأس عمود قابل للترتيب: الضغط يعكس الاتجاه، والفلاتر الحالية تبقى في الرابط.
                        $sortLink = function (string $column, string $label) use ($filters, $c) {
                            $active = ($filters['sort'] ?? null) === $column;
                            $direction = $active && ($filters['direction'] ?? 'desc') === 'desc' ? 'asc' : 'desc';
                            $url = route('admin.hourly-customers.index', array_merge(request()->except('page'), ['sort' => $column, 'direction' => $direction]));
                            $icon = $active ? (($filters['direction'] ?? 'desc') === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-sort';
                            return '<a class="hc-sort'.($active ? ' is-active' : '').'" href="'.e($url).'" title="'.e($c('list.sort', ['column' => $label])).'">'.e($label).' <i class="fa '.$icon.'"></i></a>';
                        };
                        $displayPhone = function ($customer) {
                            $mobile = \App\Support\PhoneNumber::saudiMobile($customer->phone);
                            return $mobile ? '+966 '.substr($mobile, 0, 2).' '.substr($mobile, 2, 3).' '.substr($mobile, 5) : \App\Support\PhoneNumber::digits($customer->phone);
                        };
                    @endphp
                    <thead><tr>
                        <th title="{{ $c('list.id') }}">#</th><th>{{ $c('list.customer') }}</th><th>{{ $c('list.contact') }}</th><th>{!! $sortLink('created_at', $c('list.created')) !!}</th>
                        <th>{{ $c('list.group') }}</th><th class="is-num">{!! $sortLink('balance', $c('list.balance')) !!}</th><th class="text-center">{{ $c('list.verified') }}</th>
                        <th class="is-num"><div class="hc-th-stack">{!! $sortLink('orders_count', $c('list.orders')) !!}{!! $sortLink('orders_total', $c('list.orders_total')) !!}</div></th><th class="is-actions">{{ $c('list.actions') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($customers as $customer)
                        @php $balance = (float) $customer->hourly_wallet_balance; $gone = $customer->hourly_deleted_at !== null; @endphp
                        <tr class="{{ $gone ? 'is-deleted' : '' }}">
                            <td><span class="hc-id">{{ $customer->id }}</span></td>
                            <td class="hc-cell-name">
                                <div class="hc-name-line">
                                    <a class="hc-name" href="{{ route('admin.hourly-customers.show', $customer->id) }}">{{ $customer->name }}</a>
                                    <span class="hc-done {{ $customer->completed_count ? '' : 'is-zero' }}" title="{{ $customer->completed_count ? $c('list.completed', ['count' => $customer->completed_count]) : $c('list.no_completed') }}" data-bs-toggle="tooltip"><i class="fa fa-circle-check"></i> {{ $customer->completed_count }}</span>
                                </div>
                                <div class="hc-tags">
                                    @if($customer->verified_at)<span class="hc-badge is-verified"><i class="fa fa-circle-check"></i> {{ $c('verified') }}</span>@endif
                                    @if($customer->is_blocked)<span class="hc-badge is-blocked">{{ $c('list.blocked') }}</span>@endif
                                    @if($gone)<span class="hc-badge is-blocked">{{ $customer->merged_into_id ? $c('list.merged', ['id' => $customer->merged_into_id]) : $c('list.deleted') }}</span>@endif
                                    @if($customer->marketing_source)<span class="hc-muted"><i class="fa fa-bullhorn"></i> {{ $c('marketing.'.$customer->marketing_source) }}</span>@endif
                                </div>
                            </td>
                            <td class="hc-cell-contact">
                                <span class="hc-phone" dir="ltr"><i class="fa fa-phone"></i> {{ $displayPhone($customer) ?: '—' }}</span>
                                @if($customer->email)<span class="hc-email" dir="ltr" title="{{ $customer->email }}"><i class="fa fa-envelope"></i> <span>{{ $customer->email }}</span></span>@endif
                            </td>
                            <td>
                                <div class="hc-date" dir="ltr">{{ $customer->created_at?->format('d/m/Y') }} <small>{{ $customer->created_at?->format('H:i') }}</small></div>
                                @if($customer->register_from)<span class="hc-badge is-source">{{ $sources[$customer->register_from] ?? $customer->register_from }}</span>@endif
                            </td>
                            <td>@if($customer->group_name)<span class="hc-badge is-group"><i class="fa fa-users"></i> {{ $customer->group_name }}</span>@else <span class="hc-muted">—</span> @endif
                                <div class="hc-muted mt-1"><i class="fa {{ $customer->customer_type === 'company' ? 'fa-building' : 'fa-user' }}"></i> {{ $c('types.'.($customer->customer_type ?: 'individual')) }}</div></td>
                            <td class="is-num"><span class="hc-money {{ $balance > 0 ? 'is-pos' : ($balance < 0 ? 'is-neg' : 'is-zero') }}">{{ $money($balance) }}</span></td>
                            <td class="text-center">
                                <div class="form-check form-switch m-0 d-inline-block">
                                    <input class="form-check-input hc-switch" type="checkbox" role="switch" aria-label="{{ $c('list.verified') }}" {{ $customer->verified_at ? 'checked' : '' }}
                                           @if($canEdit) data-verify="{{ route('admin.hourly-customers.verify', $customer->id) }}" @else disabled @endif>
                                </div>
                            </td>
                            <td class="is-num">
                                <span class="hc-count {{ $customer->orders_count ? '' : 'is-zero' }}">{{ $customer->orders_count }}</span>
                                <div class="mt-1"><span class="hc-money {{ (float) $customer->orders_total ? '' : 'is-zero' }}">{{ $money($customer->orders_total) }}</span> <small class="hc-muted">{{ $c('sar') }}</small></div>
                            </td>
                            <td class="is-actions">
                                @if($gone)
                                    @if($canRestore && !$customer->merged_into_id)
                                        <form method="POST" action="{{ route('admin.hourly-customers.restore', $customer->id) }}" class="d-inline">@csrf @method('PUT')
                                            <button class="hc-icon-btn is-green" title="{{ $c('list.restore') }}" aria-label="{{ $c('list.restore') }}" data-bs-toggle="tooltip"><i class="fa fa-rotate-left"></i></button></form>
                                    @endif
                                    <a class="hc-icon-btn" href="{{ route('admin.hourly-customers.show', $customer->id) }}" title="{{ $c('list.open') }}" aria-label="{{ $c('list.open') }}" data-bs-toggle="tooltip"><i class="fa fa-eye"></i></a>
                                @else
                                    <a class="hc-icon-btn is-primary" href="{{ route('admin.hourly-customers.show', $customer->id) }}" title="{{ $canEdit ? $c('list.edit') : $c('list.open') }}" aria-label="{{ $canEdit ? $c('list.edit') : $c('list.open') }}" data-bs-toggle="tooltip"><i class="fa {{ $canEdit ? 'fa-pen-to-square' : 'fa-eye' }}"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hc-list-foot">
                <div class="d-flex align-items-center gap-2">
                    <label for="hc-per-page" class="hc-muted m-0">{{ $c('filters.per_page') }}</label>
                    <select class="form-select form-select-sm" id="hc-per-page" style="width:auto">@foreach([25, 50, 100, 250] as $size)<option value="{{ $size }}" {{ (int) $f('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>@endforeach</select>
                </div>
                {{ $customers->links('admin.hourly_customers._pagination') }}
            </div>
        @endif
    </section>
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
