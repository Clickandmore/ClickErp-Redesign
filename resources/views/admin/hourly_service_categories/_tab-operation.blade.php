{{-- تبويب تشغيل/إيقاف: الإغلاقات المؤقتة. يتطلب: $closures, $cityDistricts, $districtLinks, $canEdit --}}
@php
    $linkedDistricts = $cityDistricts->flatten()->filter(fn ($d) => $districtLinks->has($d->id))->sortBy('name');
    $districtNames = $cityDistricts->flatten()->mapWithKeys(fn ($d)=>[$d->id=>app()->getLocale()==='en' ? ($d->name_en ?: $d->name) : $d->name]);
@endphp
<div class="row g-3">
    @if ($canEdit)
        <div class="modal fade" id="categoryClosureModal" tabindex="-1" aria-labelledby="categoryClosureTitle"><div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="ht-card">
                <div class="ht-head">
                    <div class="ht-head-icon"><i class="fa fa-circle-pause"></i></div>
                    <div><h5 id="categoryClosureTitle">{{ __('hourly_category_ui.add_closure') }}</h5><p>{{ __('hourly_category_ui.closure_hint') }}</p></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly.cancel') }}"></button>
                </div>
                <form method="POST" action="{{ route('admin.hourly-category-closures.store', $category) }}">
                    @csrf
                    <div class="ht-body">
                        <div class="mb-3">
                            <label class="form-label" for="closure_starts">{{ __('hourly_category_ui.from') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="closure_starts" name="starts_at" value="{{ old('starts_at') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="closure_ends">{{ __('hourly_category_ui.to') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="closure_ends" name="ends_at" value="{{ old('ends_at') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="closure_districts">{{ __('hourly_category_ui.closure_districts') }}</label>
                            <select id="closure_districts" name="district_ids[]" class="form-select" multiple data-placeholder="{{ __('hourly_category_ui.search_district') }}" data-empty="{{ __('hourly_category_ui.closure_all_hint') }}">
                                @foreach ($linkedDistricts as $district)<option value="{{ $district->id }}" {{ in_array($district->id, old('district_ids', [])) ? 'selected' : '' }}>{{ $districtNames[$district->id] }}</option>@endforeach
                            </select>
                        </div>
                        <label class="form-label" for="closure_reason">{{ __('hourly_category_ui.reason') }}</label>
                        <input class="form-control" id="closure_reason" name="reason" maxlength="500" value="{{ old('reason') }}" placeholder="{{ __('hourly_category_ui.reason_example') }}">
                    </div>
                    <div class="ht-foot"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('hourly.cancel') }}</button><button class="btn btn-primary rounded-3 flex-fill"><i class="fa fa-plus ms-1"></i> {{ __('hourly_category_ui.submit_closure') }}</button></div>
                </form>
            </div>
        </div></div></div>
    @endif
    <div class="col-12">
        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-calendar-xmark"></i></div>
                <div><h5>{{ __('hourly_category_ui.closures') }}</h5><p>{{ __('hourly_category_ui.closure_counts', ['active'=>$closures->where('ends_at','>=',now())->count(), 'total'=>$closures->count()]) }}</p></div>
                @if ($canEdit)
                    <div class="ht-actions"><button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#categoryClosureModal"><i class="fa fa-plus"></i> {{ __('hourly_category_ui.add_closure') }}</button>
                        <form method="POST" action="{{ route('admin.hourly-category-closures.clear', $category) }}" data-confirm="{{ __('hourly_category_ui.clear_confirm') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rounded-3"><i class="fa fa-broom ms-1"></i> {{ __('hourly_category_ui.clear_closures') }}</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="ht-body">
                @if ($closures->isEmpty())
                    <div class="ht-empty py-5"><i class="fa fa-circle-play"></i> {{ __('hourly_category_ui.no_closures') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="ht-table">
                            <thead><tr><th>{{ __('hourly_category_ui.period') }}</th><th>{{ __('hourly_category_ui.scope') }}</th><th>{{ __('hourly_category_ui.reason') }}</th><th>{{ __('hourly_category_ui.status') }}</th>@if ($canEdit)<th></th>@endif</tr></thead>
                            <tbody>
                                @foreach ($closures as $closure)
                                    @php $state = $closure->ends_at->isPast() ? [__('hourly_category_ui.expired'), 'ht-chip-off'] : ($closure->starts_at->isFuture() ? [__('hourly_category_ui.upcoming'), 'hd-chip-info'] : [__('hourly_category_ui.current'), 'hd-chip-warn']); @endphp
                                    <tr class="{{ $closure->ends_at->isPast() ? 'text-muted' : '' }}">
                                        <td class="ht-num small" dir="ltr" style="text-align: right">{{ $closure->starts_at->format('Y-m-d H:i') }}<br>{{ $closure->ends_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if ($closure->district_ids === null)
                                                <span class="ht-chip hd-chip-primary">{{ __('hourly_category_ui.all_districts') }}</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1">@foreach ($closure->district_ids as $id)<span class="ht-chip ht-chip-off">{{ $districtNames[$id] ?? '#'.$id }}</span>@endforeach</div>
                                            @endif
                                        </td>
                                        <td>{{ $closure->reason ?: '—' }}</td>
                                        <td><span class="ht-chip {{ $state[1] }}">{{ $state[0] }}</span></td>
                                        @if ($canEdit)
                                            <td>
                                                <form method="POST" action="{{ route('admin.hourly-category-closures.destroy', [$category, $closure]) }}" data-confirm="{{ __('hourly_category_ui.delete_closure') }}">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger ht-icon-btn" title="{{ __('hourly_category_ui.remove') }}"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($errors->has('starts_at') || $errors->has('ends_at') || $errors->has('reason'))
@push('category-js')<script>document.addEventListener('DOMContentLoaded',()=>{if(window.bootstrap){bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#tab-operation"]')).show();bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryClosureModal')).show();}});</script>@endpush
@endif
