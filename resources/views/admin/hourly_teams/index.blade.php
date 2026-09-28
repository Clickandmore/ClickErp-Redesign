@extends('admin.layout.indexs.index')

@section('page-title', __('فرق التأجير بالساعة'))

@section('style')
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    <div class="container-fluid ht-page">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ session('error') }}</div>
        @endif

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-people-group"></i></div>
                <div>
                    <h5>{{ __('فرق التأجير بالساعة') }}</h5>
                    <p>{{ __('كل فريق ينبثق من مكان إيواء، وله عاملات وسائقون ومناطق تغطية على الخريطة') }}</p>
                </div>
                <div class="ht-actions">
                    @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_VIEW))
                        <a href="{{ route('admin.hourly-teams.map') }}" class="btn btn-outline-primary rounded-3"><i class="fa fa-map ms-1"></i> {{ __('خريطة الفرق') }}</a>
                    @endif
                    @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_CREATE))
                        <a href="{{ route('admin.hourly-teams.create') }}" class="btn btn-primary rounded-3"><i class="fa fa-plus ms-1"></i> {{ __('فريق جديد') }}</a>
                    @endif
                </div>
            </div>
            <div class="ht-body">
                <form method="GET" action="{{ route('admin.hourly-teams.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-md-3 col-6">
                        <label class="form-label" for="f_shelter">{{ __('مكان الإيواء') }}</label>
                        <select class="form-select" id="f_shelter" name="shelter_place_id">
                            <option value="">{{ __('الكل') }}</option>
                            @foreach ($shelters as $s)
                                <option value="{{ $s->id }}" {{ (string) ($filters['shelter_place_id'] ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->title ?? ('#' . $s->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label" for="f_city">{{ __('المدينة') }}</label>
                        <select class="form-select" id="f_city" name="city_id">
                            <option value="">{{ __('الكل') }}</option>
                            @foreach ($cities as $c)
                                <option value="{{ $c->id }}" {{ (string) ($filters['city_id'] ?? '') === (string) $c->id ? 'selected' : '' }}>{{ $c->name_ar ?: $c->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label" for="f_status">{{ __('الحالة') }}</label>
                        <select class="form-select" id="f_status" name="status">
                            <option value="">{{ __('الكل') }}</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('نشط') }}</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('متوقف') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label" for="f_search">{{ __('بحث') }}</label>
                        <input type="text" class="form-control" id="f_search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('الاسم أو الرمز') }}">
                    </div>
                    <div class="col-md-1 col-12 d-grid">
                        <button class="btn btn-outline-secondary rounded-3" type="submit"><i class="fa fa-filter"></i></button>
                    </div>
                </form>

                @if ($teams->isEmpty())
                    <div class="ht-note ht-note-info">
                        <i class="fa fa-circle-info mt-1"></i>
                        <div>{{ __('لا توجد فرق بعد. أنشئ أول فريق من مكان الإيواء الذي تنطلق منه العاملات، ثم ارسم مناطق تغطيته على الخريطة.') }}</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="ht-table">
                            <thead>
                                <tr>
                                    <th>{{ __('الفريق') }}</th>
                                    <th>{{ __('مكان الإيواء') }}</th>
                                    <th>{{ __('المدينة') }}</th>
                                    <th class="text-center">{{ __('العاملات') }}</th>
                                    <th class="text-center">{{ __('السائقون') }}</th>
                                    <th class="text-center">{{ __('المناطق') }}</th>
                                    <th>{{ __('ساعات العمل') }}</th>
                                    <th>{{ __('الحالة') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teams as $t)
                                    <tr>
                                        <td>
                                            <span class="ht-dot" style="background: {{ $t->color }}"></span>
                                            <strong class="ms-1">{{ $t->name }}</strong>
                                            @if ($t->code)<span class="text-muted small" dir="ltr">({{ $t->code }})</span>@endif
                                        </td>
                                        <td>{{ optional($t->shelterPlace)->title ?? '—' }}</td>
                                        <td>{{ optional($t->city)->name_ar ?? '—' }}</td>
                                        <td class="text-center ht-num">{{ $t->maids_count }}</td>
                                        <td class="text-center ht-num">{{ $t->drivers_count }}</td>
                                        <td class="text-center ht-num">
                                            {{ $t->areas_count }}
                                            @if ($t->areas_count === 0)<i class="fa fa-triangle-exclamation text-warning ms-1" title="{{ __('بلا مناطق تغطية') }}"></i>@endif
                                        </td>
                                        <td class="ht-num" dir="ltr">{{ $t->working_start ? substr($t->working_start, 0, 5) : '—' }} – {{ $t->working_end ? substr($t->working_end, 0, 5) : '—' }}</td>
                                        <td>
                                            @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_EDIT))
                                                <form method="POST" action="{{ route('admin.hourly-teams.toggle', $t) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent">
                                                        <span class="ht-chip {{ $t->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">
                                                            <i class="fa {{ $t->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i> {{ $t->is_active ? __('نشط') : __('متوقف') }}
                                                        </span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="ht-chip {{ $t->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $t->is_active ? __('نشط') : __('متوقف') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_EDIT))
                                                <a href="{{ route('admin.hourly-teams.edit', $t) }}" class="btn btn-sm btn-outline-primary rounded-3"><i class="fa fa-pen"></i></a>
                                            @endif
                                            @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_DELETE))
                                                <form method="POST" action="{{ route('admin.hourly-teams.destroy', $t) }}" class="d-inline" data-confirm-title="حذف الفريق" data-confirm="حذف الفريق؟ تُختم عضويات أعضائه بتاريخ اليوم." data-confirm-button="نعم، احذف">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-3"><i class="fa fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
@endpush
