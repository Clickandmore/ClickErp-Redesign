@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_customers.groups.title'))
@section('style')
@include('admin.hourly_customers._style')
@endsection
@section('content')
@php $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace); @endphp
<div class="container-fluid hc" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" id="hourly-customers" data-csrf="{{ csrf_token() }}">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <section class="hc-box">
        <div class="hc-head">
            <div>
                <h3><i class="fa fa-users"></i> {{ $c('groups.title') }}</h3>
                <div class="hc-muted mt-1">{{ $c('groups.sub') }}</div>
            </div>
            <div class="d-flex gap-2">
                <a class="hc-btn" href="{{ route('admin.hourly-customers.index') }}"><i class="fa fa-list"></i> {{ $c('show.back') }}</a>
                @if($canCreate)<button type="button" class="hc-btn is-primary" data-group-edit="{{ json_encode(['action' => route('admin.hourly-customer-groups.store'), 'method' => 'POST', 'title' => $c('groups.add'), 'is_active' => true]) }}"><i class="fa fa-plus"></i> {{ $c('groups.add') }}</button>@endif
            </div>
        </div>
        <div class="hc-body">
            <div class="alert alert-info border-0 rounded-3 small d-flex gap-2 align-items-start"><i class="fa fa-circle-info mt-1"></i><span>{{ $c('groups.discount_hint') }}</span></div>
            @if($groups->isEmpty())
                <div class="hc-empty">
                    <i class="fa fa-users d-block mb-2" style="font-size:2rem;color:#cbd5e1"></i>
                    <div class="mb-3">{{ $c('groups.empty') }}</div>
                    @if($canCreate)<button type="button" class="hc-btn is-primary" data-group-edit="{{ json_encode(['action' => route('admin.hourly-customer-groups.store'), 'method' => 'POST', 'title' => $c('groups.add'), 'is_active' => true]) }}"><i class="fa fa-plus"></i> {{ $c('groups.add') }}</button>@endif
                </div>
            @else
                <div class="table-responsive"><table class="hc-table">
                    <thead><tr><th>{{ $c('groups.name') }}</th><th>{{ $c('groups.description') }}</th><th>{{ $c('groups.discount') }}</th><th>{{ $c('groups.members') }}</th><th>{{ $c('groups.status') }}</th>@if($canManage)<th>{{ $c('groups.actions') }}</th>@endif</tr></thead>
                    <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td><span class="hc-badge is-group"><i class="fa fa-users"></i> {{ $group->name }}</span></td>
                            <td>{{ $group->description ?: '—' }}</td>
                            <td class="hc-money">{{ $group->discount_percent !== null ? rtrim(rtrim(number_format((float) $group->discount_percent, 2, '.', ''), '0'), '.').'%' : '—' }}</td>
                            <td><a href="{{ route('admin.hourly-customers.index', ['group_id' => $group->id]) }}">{{ $group->profiles_count }}</a></td>
                            <td><span class="hc-badge" style="{{ $group->is_active ? 'background:#dcfce7;color:#166534' : 'background:#f1f5f9;color:#64748b' }}">{{ $group->is_active ? $c('groups.active') : $c('groups.inactive') }}</span></td>
                            @if($canManage)
                                <td class="d-flex gap-1">
                                    @if($canEdit)<button type="button" class="hc-btn is-sm" data-group-edit="{{ json_encode(['action' => route('admin.hourly-customer-groups.update', $group->id), 'method' => 'PUT', 'title' => $c('groups.edit'),
                                        'name' => $group->name, 'description' => $group->description, 'discount_percent' => $group->discount_percent, 'is_active' => (bool) $group->is_active]) }}"><i class="fa fa-pen"></i> {{ $c('list.edit') }}</button>@endif
                                    @if($canDelete)<form method="POST" action="{{ route('admin.hourly-customer-groups.destroy', $group->id) }}" data-confirm-group-delete>@csrf @method('DELETE')
                                        <button class="hc-btn is-danger is-sm"><i class="fa fa-trash"></i> {{ $c('groups.delete') }}</button></form>@endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </div>
    </section>

    @if($canManage)
    <div class="modal fade om-modal" id="hc-group-modal" tabindex="-1" aria-labelledby="hc-group-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('admin.hourly-customer-groups.store') }}" id="hc-group-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-users"></i></span><div><h5 id="hc-group-title">{{ $c('groups.add') }}</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $c('deposit.close') }}"></button></div>
            <div class="modal-body">
                <label for="hc-group-name">{{ $c('groups.name') }} *</label>
                <input class="form-control mb-3" id="hc-group-name" name="name" maxlength="150" required>
                <label for="hc-group-description">{{ $c('groups.description') }}</label>
                <textarea class="form-control mb-3" id="hc-group-description" name="description" rows="2" maxlength="1000"></textarea>
                <label for="hc-group-discount">{{ $c('groups.discount') }}</label>
                <input class="form-control" type="number" min="0" max="100" step="0.01" id="hc-group-discount" name="discount_percent" dir="ltr">
                <div class="form-text mb-3">{{ $c('groups.discount_hint') }}</div>
                <label class="hc-check"><input type="checkbox" name="is_active" value="1" id="hc-group-active" checked> {{ $c('groups.active') }}</label>
                <div class="form-text">{{ $c('groups.inactive_hint') }}</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $c('deposit.close') }}</button><button class="btn btn-primary"><i class="fa fa-check"></i> {{ $c('groups.save') }}</button></div>
        </form></div>
    </div>
    @endif
</div>
@php $payload = ['i18n' => __('hourly_customers.js')]; @endphp
<script type="application/json" id="hc-data">@json($payload)</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-customers.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-customers.js')) }}"></script>
@endpush
