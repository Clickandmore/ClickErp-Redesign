@extends('admin.layout.indexs.index')
@section('page-title', __('hourly.package_tiers'))

@section('style')
    @include('admin.hourly_teams._style')
    <style>
        .pt-code { display: inline-flex; align-items: center; gap: .35rem; font-family: ui-monospace, Menlo, monospace; font-size: .78rem; background: var(--ht-soft); border: 1px solid var(--ht-line); border-radius: 8px; padding: .15rem .5rem; color: var(--ht-muted); }
        .pt-names { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; min-width: 260px; }
        .pt-names .form-control { height: 38px; }
        .pt-sort { width: 68px; height: 38px; text-align: center; }
        .pt-row.is-dirty td { background: #fffbeb; }
        .pt-row.is-off .pt-title { color: var(--ht-muted); }
        .pt-dirty { display: none; font-size: .72rem; color: #b45309; }
        .pt-row.is-dirty .pt-dirty { display: block; }
    </style>
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_package_tiers_edit');
        $frequency = fn ($tier) => match ($tier->frequency) {
            'one_off' => [__('hourly.tier_freq_one_off'), 'ht-chip-off', 'fa-1'],
            'weekly' => [__('hourly.tier_freq_weekly', ['count' => rtrim(rtrim(number_format($tier->visits_per_week, 2), '0'), '.')]), 'hd-chip-primary', 'fa-calendar-week'],
            'biweekly' => [__('hourly.tier_freq_biweekly'), 'hd-chip-info', 'fa-calendar-days'],
            'monthly' => [__('hourly.tier_freq_monthly'), 'hd-chip-info', 'fa-calendar'],
            default => [$tier->frequency, 'ht-chip-off', 'fa-repeat'],
        };
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        @include('admin.hourly.messages')

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-layer-group"></i></div>
                <div>
                    <h5>{{ __('hourly.package_tiers') }}</h5>
                    <p>{{ __('hourly.tiers_hint') }}</p>
                </div>
                <div class="ht-actions">
                    @if (AdminGuardHasPerm('hourly_packages_view'))
                        <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-service-items.index') }}"><i class="fa fa-box-open ms-1"></i> باقات الخدمات</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="hd-stats">
            <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-layer-group"></i></div><div><span>{{ __('hourly.tiers_total') }}</span><strong class="ht-num">{{ $tiers->count() }}</strong></div></div>
            <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>{{ __('hourly.tiers_active') }}</span><strong class="ht-num">{{ $tiers->where('is_active', true)->count() }}</strong></div></div>
            <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-box-open"></i></div><div><span>{{ __('hourly.tiers_items_total') }}</span><strong class="ht-num">{{ $tiers->sum('items_count') }}</strong></div></div>
        </div>

        <div class="ht-card">
            <div class="ht-body">
                <div class="table-responsive">
                    <table class="ht-table">
                        <thead>
                            <tr>
                                <th style="width: 60px">ID</th>
                                <th>{{ __('hourly.tier_name') }}</th>
                                <th>{{ __('hourly.tier_frequency') }}</th>
                                <th>{{ __('hourly.tier_name_ar') }} / {{ __('hourly.tier_name_en') }}</th>
                                <th class="text-center">{{ __('hourly.tier_items') }}</th>
                                <th class="text-center">{{ __('hourly.tier_sort') }}</th>
                                <th>{{ __('hourly.status') }}</th>
                                @if ($canEdit)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tiers as $tier)
                                @php [$freqLabel, $freqClass, $freqIcon] = $frequency($tier); $formId = 'tier-form-'.$tier->id; @endphp
                                <tr class="pt-row {{ $tier->is_active ? '' : 'is-off' }}" data-tier-row>
                                    <td class="text-muted ht-num">{{ $tier->id }}</td>
                                    <td>
                                        <strong class="pt-title text-nowrap">{{ $tier->name }}</strong>
                                        <div class="mt-1"><span class="pt-code" title="{{ __('hourly.tier_code') }}"><i class="fa fa-lock"></i>{{ $tier->code }}</span></div>
                                        <span class="pt-dirty"><i class="fa fa-circle-exclamation ms-1"></i>{{ __('hourly.tier_unsaved') }}</span>
                                    </td>
                                    <td><span class="ht-chip {{ $freqClass }}"><i class="fa {{ $freqIcon }}"></i> {{ $freqLabel }}</span></td>
                                    <td>
                                        <div class="pt-names">
                                            <input form="{{ $formId }}" aria-label="{{ __('hourly.tier_name_ar') }} {{ $tier->code }}" name="name_ar" class="form-control" value="{{ $tier->name_ar }}" maxlength="255" required {{ $canEdit ? '' : 'disabled' }}>
                                            <input form="{{ $formId }}" aria-label="{{ __('hourly.tier_name_en') }} {{ $tier->code }}" name="name_en" class="form-control" dir="ltr" value="{{ $tier->name_en }}" maxlength="255" placeholder="English" {{ $canEdit ? '' : 'disabled' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($tier->items_count)
                                            <span class="ht-chip hd-chip-primary ht-num">{{ $tier->items_count }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input form="{{ $formId }}" aria-label="{{ __('hourly.tier_sort') }} {{ $tier->code }}" name="sort_order" type="number" min="0" max="10000" class="form-control pt-sort ht-num d-inline-block" value="{{ $tier->sort_order }}" required {{ $canEdit ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <div class="ht-switch form-switch py-2 px-3 m-0" style="background: transparent; border: 0">
                                            <input form="{{ $formId }}" name="is_active" type="hidden" value="0">
                                            <input form="{{ $formId }}" id="tier-active-{{ $tier->id }}" name="is_active" type="checkbox" value="1" class="form-check-input" role="switch" {{ $tier->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            <div><label for="tier-active-{{ $tier->id }}" class="small">{{ $tier->is_active ? __('hourly.tier_active') : __('hourly.tier_inactive') }}</label></div>
                                        </div>
                                    </td>
                                    @if ($canEdit)
                                        <td class="text-nowrap">
                                            <form id="{{ $formId }}" method="POST" action="{{ route('admin.hourly-package-tiers.update', $tier) }}" class="m-0">
                                                @csrf @method('PUT')
                                                <button class="btn btn-sm btn-outline-primary ht-icon-btn" data-tier-save title="{{ __('hourly.tier_save') }}" aria-label="{{ __('hourly.tier_save') }}"><i class="fa fa-floppy-disk"></i></button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
// إبراز الصف المعدَّل وتفعيل زر حفظه
document.querySelectorAll('[data-tier-row]').forEach(row => {
    const save = row.querySelector('[data-tier-save]');
    if (!save) return;
    const fields = [...row.querySelectorAll('input[form]:not([type=hidden])')];
    const snapshot = () => fields.map(f => f.type === 'checkbox' ? f.checked : f.value).join('|');
    const initial = snapshot();
    const sync = () => {
        const dirty = snapshot() !== initial;
        row.classList.toggle('is-dirty', dirty);
        save.classList.toggle('btn-primary', dirty);
        save.classList.toggle('btn-outline-primary', !dirty);
    };
    fields.forEach(f => f.addEventListener(f.type === 'checkbox' ? 'change' : 'input', sync));
});
</script>
@endpush
