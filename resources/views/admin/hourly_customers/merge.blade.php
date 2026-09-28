@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_customers.merge.title'))
@section('style')
@include('admin.hourly_customers._style')
@endsection
@section('content')
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
    // الأساسي الافتراضي: العميل الذي فُتحت منه الشاشة، وإلا صاحب أكثر الطلبات ثم الأقدم.
    $default = $back && $users->contains('id', (int) $back) ? (int) $back
        : $users->sortBy([fn ($a, $b) => ($orders[$b->id] ?? 0) <=> ($orders[$a->id] ?? 0), fn ($a, $b) => $a->id <=> $b->id])->first()->id;
    $primary = (int) old('primary_id', $default);
@endphp
<div class="container-fluid hc" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.hourly-customers.merge') }}" class="hc-box" id="hc-merge-form">
        @csrf
        <div class="hc-head">
            <h3><i class="fa fa-code-merge"></i> {{ $c('merge.title') }}</h3>
            <a class="hc-btn" href="{{ $back ? route('admin.hourly-customers.show', [$back, 'merge']) : route('admin.hourly-customers.duplicates') }}"><i class="fa fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i> {{ $c('form.cancel') }}</a>
        </div>
        <div class="hc-body">
            <div class="hc-note mb-3"><i class="fa fa-circle-info mt-1"></i><div>{{ $c('merge.hint') }}</div></div>
            <div class="table-responsive"><table class="hc-table">
                <thead><tr><th>{{ $c('merge.primary') }}</th><th>{{ $c('merge.merge_into') }}</th><th>{{ $c('list.id') }}</th><th>{{ $c('list.name') }}</th><th>{{ $c('list.phone') }}</th><th>{{ $c('list.email') }}</th>
                    <th>{{ $c('form.national_num') }}</th><th>{{ $c('list.orders') }}</th><th>{{ $c('list.balance') }}</th><th>{{ $c('list.created') }}</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr data-merge-row="{{ $user->id }}">
                        <td><input type="radio" name="primary_id" value="{{ $user->id }}" {{ $primary === (int) $user->id ? 'checked' : '' }} aria-label="{{ $c('merge.primary') }} #{{ $user->id }}"></td>
                        <td><input type="checkbox" name="merge_ids[]" value="{{ $user->id }}" {{ $primary === (int) $user->id ? 'disabled' : 'checked' }} aria-label="{{ $c('merge.merge_into') }} #{{ $user->id }}"></td>
                        <td><a href="{{ route('admin.hourly-customers.show', $user->id) }}" target="_blank" rel="noopener">#{{ $user->id }}</a></td>
                        <td>{{ $user->name }}<div class="hc-muted small">{{ $sources[$user->register_from] ?? $user->register_from }}</div></td>
                        <td dir="ltr">{{ $user->phone }}</td>
                        <td dir="ltr">{{ $user->email ?: '—' }}</td>
                        <td dir="ltr">{{ $user->national_num ?: '—' }}</td>
                        <td class="hc-money">{{ $orders[$user->id] ?? 0 }}</td>
                        <td class="hc-money">{{ $money($user->hourly_wallet_balance) }}</td>
                        <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d.m.Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="hc-field mt-3">
                <label for="hc-merge-reason">{{ $c('merge.reason') }}</label>
                <textarea class="form-control" id="hc-merge-reason" name="reason" rows="2" maxlength="500">{{ old('reason') }}</textarea>
            </div>
            <div class="hc-actions"><button class="hc-btn is-primary"><i class="fa fa-code-merge"></i> {{ $c('merge.confirm') }}</button></div>
        </div>
    </form>
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
