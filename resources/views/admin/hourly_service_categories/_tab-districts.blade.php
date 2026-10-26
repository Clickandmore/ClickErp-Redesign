{{-- تبويب مناطق الخدمة: بطاقة لكل مدينة مغطاة وبداخلها أحياء الفئة. يتطلب: $coverageCities, $cityDistricts, $districtLinks, $teams, $canEdit --}}
<style>
    .cd-card .ht-head { align-items: center; }
    .cd-scroll { max-height: 410px; overflow: auto; }
    .cd-scroll thead { position: sticky; top: 0; z-index: 1; background: #fff; }
    .cd-card .ht-table tbody tr:nth-child(odd) { background: #f6f6fa; }
    .cd-search { position: relative; width: 220px; }
    .cd-search i { position: absolute; top: 50%; inset-inline-start: .7rem; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; }
    .cd-search input { padding-inline-start: 2rem; border-radius: 10px; height: 34px; font-size: .85rem; }
    .cd-foot { display: flex; gap: .5rem; flex-wrap: wrap; font-size: .8rem; }
    .cd-foot b { font-variant-numeric: tabular-nums; }
</style>
@php
    $teamNames = $teams->pluck('name', 'id');
    $teamOptions = $teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name.($t->is_active ? '' : __('hourly_category_ui.inactive_suffix'))])->values();
@endphp

@if ($coverageCities->isEmpty())
    <div class="ht-note ht-note-info"><i class="fa fa-circle-info mt-1"></i><div>{{ __('hourly_category_ui.no_cities') }}</div></div>
@else
    @if ($districtLinks->isEmpty())
        <div class="ht-note ht-note-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>{{ __('hourly_category_ui.no_links') }}</div></div>
    @endif
    @php $teamless = $districtTeams->filter(fn ($team) => $team === false)->count(); @endphp
    @if ($teamless)
        <div class="ht-note ht-note-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>{{ __('hourly_category_ui.teamless_districts', ['count' => $teamless]) }}</div></div>
    @endif
    <div class="row g-3">
        @foreach ($coverageCities as $city)
            @php
                $all = $cityDistricts->get($city->id, collect());
                $linked = $all->filter(fn ($d) => $districtLinks->has($d->id));
                $available = $all->reject(fn ($d) => $districtLinks->has($d->id));
                $activeCount = $linked->filter(fn ($d) => $districtLinks[$d->id]->is_active)->count();
            @endphp
            <div class="col-xl-6">
                <div class="ht-card cd-card h-100" data-city-card>
                    <div class="ht-head">
                        <div class="ht-head-icon"><i class="fa fa-city"></i></div>
                        <div><h5>{{ (app()->getLocale() === 'en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar) }}</h5><p>{{ __('hourly_category_ui.linked_count', ['linked'=>$linked->count(), 'total'=>$all->count()]) }}</p></div>
                        <div class="ht-actions align-items-center">
                            @if($canEdit)<form method="POST" action="{{ route('admin.hourly-category-districts.bulk',$category) }}" class="m-0">@csrf @method('PUT')<input type="hidden" name="city_id" value="{{ $city->id }}"><input type="hidden" name="action" value="{{ $activeCount === $linked->count() ? 'deactivate' : 'activate' }}"><button type="submit" class="ht-toggle" role="switch" aria-checked="{{ $linked->isNotEmpty() && $activeCount === $linked->count() ? 'true' : 'false' }}" aria-label="{{ __('hourly_category_ui.edit_all') }}" @if($linked->isEmpty()) disabled @endif><span>{{ $linked->isNotEmpty() && $activeCount === $linked->count() ? '✓' : '' }}</span></button></form>@endif
                            <div class="cd-search"><i class="fa fa-magnifying-glass"></i><input type="search" class="form-control" placeholder="{{ __('hourly_category_ui.search_districts') }}" aria-label="{{ __('hourly_category_ui.search_districts') }}" data-city-search></div>
                        </div>
                    </div>
                    @if ($canEdit)
                        <div class="px-3 pt-3 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#cdAddModal"
                                    data-city="{{ json_encode(['id' => $city->id, 'name' => (app()->getLocale() === 'en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar), 'districts' => $available->map(fn ($d) => ['id' => $d->id, 'name' => (app()->getLocale()==='en' ? ($d->name_en ?: $d->name) : $d->name).($d->is_active ? '' : __('hourly_category_ui.inactive_suffix'))])->values()]) }}" {{ $available->isEmpty() ? 'disabled' : '' }}>
                                <i class="fa fa-plus ms-1"></i> {{ __('hourly_category_ui.add_area') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3" data-bs-toggle="modal" data-bs-target="#cdBulkModal" data-city="{{ json_encode(['id' => $city->id, 'name' => (app()->getLocale() === 'en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar)]) }}" {{ $linked->isEmpty() ? 'disabled' : '' }}>
                                <i class="fa fa-pen-to-square ms-1"></i> {{ __('hourly_category_ui.edit_all') }}
                            </button>
                            <form method="POST" action="{{ route('admin.hourly-category-districts.destroy-city', [$category, $city]) }}" class="m-0" data-confirm="{{ __('hourly_category_ui.delete_city', ['city'=>app()->getLocale()==='en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger rounded-3" {{ $linked->isEmpty() ? 'disabled' : '' }}><i class="fa fa-trash ms-1"></i> {{ __('hourly_category_ui.delete_all') }}</button>
                            </form>
                        </div>
                    @endif
                    <div class="ht-body">
                        @if ($linked->isEmpty())
                            <div class="ht-empty py-3"><i class="fa fa-map-pin"></i> {{ __('hourly_category_ui.empty_city', ['city'=>app()->getLocale()==='en' ? ($city->name_en ?: $city->name_ar) : $city->name_ar]) }}</div>
                        @else
                            <div class="table-responsive cd-scroll">
                                <table class="ht-table">
                                    <thead><tr><th>{{ __('hourly_category_ui.district') }}</th><th>{{ __('hourly_category_ui.team') }}</th><th>{{ __('hourly_category_ui.status') }}</th>@if ($canEdit)<th></th>@endif</tr></thead>
                                    <tbody>
                                        @foreach ($linked as $district)
                                            @php $link = $districtLinks[$district->id]; @endphp
                                            <tr data-district-row data-search="{{ mb_strtolower($district->name.' '.$district->name_en.' '.($teamNames[$link->team_id] ?? '')) }}">
                                                <td><strong>{{ (app()->getLocale()==='en' ? ($district->name_en ?: $district->name) : $district->name) }}</strong>@unless ($district->is_active)<div class="small text-warning">{{ __('hourly_category_ui.directory_inactive') }}</div>@endunless</td>
                                                {{-- $fallback: null = فريق الربط نشط، كائن = فريق الحي يُستخدم بدلًا منه، false = لا فريق نشط --}}
                                                @php $fallback = $districtTeams[$district->id] ?? null; @endphp
                                                <td data-team-name>
                                                    {{ $link->team_id ? ($teamNames[$link->team_id] ?? '—') : __('hourly_category_ui.no_team') }}
                                                    @if ($fallback === false)
                                                        <div class="small text-danger"><i class="fa fa-triangle-exclamation"></i> {{ __('hourly_category_ui.no_active_team') }}</div>
                                                    @elseif ($fallback)
                                                        <div class="small text-muted">{{ __('hourly_category_ui.district_team_used', ['team' => $fallback->name]) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button type="button" class="ht-toggle" role="switch" aria-checked="{{ $link->is_active ? 'true' : 'false' }}" aria-label="{{ __('hourly_category_ui.status') }} {{ (app()->getLocale()==='en' ? ($district->name_en ?: $district->name) : $district->name) }}"
                                                            data-link-url="{{ route('admin.hourly-category-districts.update', [$category, $district]) }}" {{ $canEdit ? '' : 'disabled' }}><span>{{ $link->is_active ? '✓' : '' }}</span></button>
                                                </td>
                                                @if ($canEdit)
                                                    <td class="text-nowrap">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary ht-icon-btn" title="{{ __('hourly_category_ui.edit') }}" data-bs-toggle="modal" data-bs-target="#cdEditModal"
                                                                data-link="{{ json_encode(['url' => route('admin.hourly-category-districts.update', [$category, $district]), 'name' => $district->name, 'team_id' => $link->team_id, 'is_active' => (bool) $link->is_active]) }}"><i class="fa fa-pen"></i></button>
                                                        <form method="POST" action="{{ route('admin.hourly-category-districts.destroy', [$category, $district]) }}" class="d-inline" data-confirm="{{ __('hourly_category_ui.delete_district', ['district'=>$district->name]) }}">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger ht-icon-btn" title="{{ __('hourly_category_ui.remove') }}"><i class="fa fa-xmark"></i></button>
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
                    <div class="ht-foot cd-foot" data-city-foot>
                        <span class="ht-chip ht-chip-off">{{ __('hourly_category_ui.total') }} <b data-count="total">{{ $linked->count() }}</b></span>
                        <span class="ht-chip ht-chip-on">{{ __('hourly_category_ui.active_count') }} <b data-count="active">{{ $activeCount }}</b></span>
                        <span class="ht-chip hd-chip-warn">{{ __('hourly_category_ui.inactive_count') }} <b data-count="inactive">{{ $linked->count() - $activeCount }}</b></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($canEdit)
    {{-- إضافة منطقة --}}
    <div class="modal fade ht-page" id="cdAddModal" tabindex="-1" aria-labelledby="cdAddLabel" aria-hidden="true" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.hourly-category-districts.store', $category) }}" class="modal-content border-0 rounded-4">
                @csrf
                <input type="hidden" name="city_id">
                <div class="modal-header"><h5 class="modal-title" id="cdAddLabel">{{ __('hourly_category_ui.add_area') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly_category_ui.close') }}"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="cd_district_ids">{{ __('hourly_category_ui.unlinked') }}</label>
                    <select id="cd_district_ids" name="district_ids[]" class="form-select" multiple required data-placeholder="{{ __('hourly_category_ui.search_district') }}" data-empty="{{ __('hourly_category_ui.all_linked') }}"></select>
                    <label class="form-label mt-3" for="cd_add_team">{{ __('hourly_category_ui.team') }}</label>
                    <select id="cd_add_team" name="team_id" class="form-select"><option value="">{{ __('hourly_category_ui.no_team') }}</option>@foreach ($teamOptions as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach</select>
                    <div class="ht-switch form-switch mt-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="cd_add_active" name="is_active" value="1" checked>
                        <div><label for="cd_add_active">{{ __('hourly_category_ui.active') }}</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary rounded-3 px-4"><i class="fa fa-plus ms-1"></i> {{ __('hourly_category_ui.add') }}</button><button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">{{ __('hourly_category_ui.cancel') }}</button></div>
            </form>
        </div>
    </div>

    {{-- تعديل منطقة --}}
    <div class="modal fade ht-page" id="cdEditModal" tabindex="-1" aria-labelledby="cdEditLabel" aria-hidden="true" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0 rounded-4">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title" id="cdEditLabel">{{ __('hourly_category_ui.edit_area') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly_category_ui.close') }}"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="cd_edit_team">{{ __('hourly_category_ui.team') }}</label>
                    <select id="cd_edit_team" name="team_id" class="form-select"><option value="">{{ __('hourly_category_ui.no_team') }}</option>@foreach ($teamOptions as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach</select>
                    <div class="ht-switch form-switch mt-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="cd_edit_active" name="is_active" value="1">
                        <div><label for="cd_edit_active">{{ __('hourly_category_ui.active') }}</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> {{ __('hourly_category_ui.save') }}</button><button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">{{ __('hourly_category_ui.cancel') }}</button></div>
            </form>
        </div>
    </div>

    {{-- تعديل الكل --}}
    <div class="modal fade ht-page" id="cdBulkModal" tabindex="-1" aria-labelledby="cdBulkLabel" aria-hidden="true" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.hourly-category-districts.bulk', $category) }}" class="modal-content border-0 rounded-4">
                @csrf @method('PUT')
                <input type="hidden" name="city_id">
                <div class="modal-header"><h5 class="modal-title" id="cdBulkLabel">{{ __('hourly_category_ui.edit_all') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly_category_ui.close') }}"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">{{ __('hourly_category_ui.bulk_hint') }}</p>
                    @foreach (['activate' => __('hourly_category_ui.activate_all'), 'deactivate' => __('hourly_category_ui.deactivate_all'), 'team' => __('hourly_category_ui.change_team')] as $value => $label)
                        <label class="d-flex align-items-center gap-2 mb-2"><input type="radio" name="action" value="{{ $value }}" {{ $loop->first ? 'checked' : '' }} data-bulk-action> {{ $label }}</label>
                    @endforeach
                    <select name="team_id" class="form-select mt-2" data-bulk-team disabled aria-label="{{ __('hourly_category_ui.team') }}"><option value="">{{ __('hourly_category_ui.choose_team') }}</option>@foreach ($teamOptions as $team)<option value="{{ $team['id'] }}">{{ $team['name'] }}</option>@endforeach</select>
                </div>
                <div class="modal-footer"><button class="btn btn-primary rounded-3 px-4"><i class="fa fa-check ms-1"></i> {{ __('hourly_category_ui.apply') }}</button><button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">{{ __('hourly_category_ui.cancel') }}</button></div>
            </form>
        </div>
    </div>
@endif

@push('category-js')
<script>
(() => {
    // بحث داخل بطاقة المدينة
    document.querySelectorAll('[data-city-search]').forEach(input => input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        input.closest('[data-city-card]').querySelectorAll('[data-district-row]').forEach(row => row.classList.toggle('d-none', q !== '' && !row.dataset.search.includes(q)));
    }));
    const recount = card => {
        const toggles = [...card.querySelectorAll('[data-link-url]')], active = toggles.filter(t => t.getAttribute('aria-checked') === 'true').length;
        card.querySelector('[data-count="active"]').textContent = active;
        card.querySelector('[data-count="inactive"]').textContent = toggles.length - active;
        const action=card.querySelector('input[name="action"]');
        if(action){const on=toggles.length>0 && active===toggles.length;action.value=on?'deactivate':'activate';const toggle=action.form.querySelector('[role=switch]');toggle.setAttribute('aria-checked',String(on));toggle.querySelector('span').textContent=on?'✓':'';}
    };
    // مفتاح الحالة يُحفظ فورًا
    document.querySelectorAll('[data-link-url]').forEach(button => button.addEventListener('click', async () => {
        const next = button.getAttribute('aria-checked') !== 'true';
        button.disabled = true;
        try {
            const data = await categoryUi.request(button.dataset.linkUrl, 'PUT', {is_active: next});
            button.setAttribute('aria-checked', String(data.is_active));
            button.querySelector('span').textContent = data.is_active ? '✓' : '';
            recount(button.closest('[data-city-card]'));
            categoryUi.toast(data.message);
        } catch (e) { categoryUi.toast(e.message, false); } finally { button.disabled = false; }
    }));
    const add = document.getElementById('cdAddModal');
    if (!add) return;
    add.addEventListener('show.bs.modal', event => {
        const city = JSON.parse(event.relatedTarget.dataset.city);
        add.querySelector('[name=city_id]').value = city.id;
        add.querySelector('.modal-title').textContent = @json(__('hourly_category_ui.add_area')) + ' — ' + city.name;
        const select = add.querySelector('#cd_district_ids');
        select.replaceChildren(...city.districts.map(d => new Option(d.name, d.id)));
        select.dispatchEvent(new Event('change', {bubbles: true}));
    });
    const edit = document.getElementById('cdEditModal');
    edit.addEventListener('show.bs.modal', event => {
        const link = JSON.parse(event.relatedTarget.dataset.link);
        edit.querySelector('form').action = link.url;
        edit.querySelector('.modal-title').textContent = @json(__('hourly_category_ui.edit_area')) + ' — ' + link.name;
        edit.querySelector('#cd_edit_team').value = link.team_id ?? '';
        edit.querySelector('#cd_edit_active').checked = link.is_active;
    });
    const bulk = document.getElementById('cdBulkModal'), team = bulk.querySelector('[data-bulk-team]');
    bulk.addEventListener('show.bs.modal', event => {
        const city = JSON.parse(event.relatedTarget.dataset.city);
        bulk.querySelector('[name=city_id]').value = city.id;
        bulk.querySelector('.modal-title').textContent = @json(__('hourly_category_ui.edit_all')) + ' — ' + city.name;
    });
    bulk.querySelectorAll('[data-bulk-action]').forEach(radio => radio.addEventListener('change', () => {
        team.disabled = bulk.querySelector('[data-bulk-action]:checked').value !== 'team';
        team.required = !team.disabled;
    }));
})();
</script>
@endpush
