{{-- تبويب تشغيل/إيقاف: الإغلاقات المؤقتة. يتطلب: $closures, $cityDistricts, $districtLinks, $canEdit --}}
@php
    $linkedDistricts = $cityDistricts->flatten()->filter(fn ($d) => $districtLinks->has($d->id))->sortBy('name');
    $districtNames = $cityDistricts->flatten()->mapWithKeys(fn ($d)=>[$d->id=>app()->getLocale()==='en' ? ($d->name_en ?: $d->name) : $d->name]);
@endphp
<div class="row g-3">
    @if ($canEdit)
        @php $u = fn (string $key, array $replace = []) => __('hourly_category_ui.'.$key, $replace); @endphp
        <style>
            #ccm-form { display: contents; }
            #categoryClosureModal .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, .22); }
            #categoryClosureModal .ccm-head { display: flex; align-items: flex-start; gap: .85rem; padding: 1.1rem 1.25rem; background: linear-gradient(135deg, #fff7ed, #fff); border-bottom: 1px solid #fde7c7; }
            #categoryClosureModal .ccm-icon { width: 44px; height: 44px; flex: 0 0 44px; display: grid; place-items: center; border-radius: 13px; background: #f59e0b; color: #fff; font-size: 1.1rem; box-shadow: 0 6px 16px rgba(245, 158, 11, .3); }
            #categoryClosureModal .ccm-head h5 { margin: 0 0 .2rem; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
            #categoryClosureModal .ccm-head p { margin: 0; font-size: .8rem; color: #64748b; line-height: 1.6; }
            #categoryClosureModal .ccm-head .btn-close { margin-inline-start: auto; flex: 0 0 auto; }
            #categoryClosureModal .ccm-body { padding: 1.1rem 1.25rem; display: grid; gap: 1rem; }
            #categoryClosureModal .ccm-section { border: 1px solid #e8ecf2; border-radius: 14px; padding: .9rem 1rem; background: #fff; }
            #categoryClosureModal .ccm-section-title { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; font-size: .88rem; font-weight: 700; color: #1e293b; }
            #categoryClosureModal .ccm-section-title i { width: 26px; height: 26px; display: grid; place-items: center; border-radius: 8px; background: #f1f5f9; color: #475569; font-size: .78rem; }
            #categoryClosureModal .ccm-section-title .ccm-badge { margin-inline-start: auto; }
            #categoryClosureModal .ccm-presets { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-bottom: .8rem; }
            #categoryClosureModal .ccm-presets small { color: #94a3b8; font-size: .75rem; margin-inline-end: .2rem; }
            #categoryClosureModal .ccm-preset { border: 1px solid #e2e8f0; background: #f8fafc; color: #334155; border-radius: 999px; padding: .25rem .8rem; font-size: .78rem; font-weight: 600; }
            #categoryClosureModal .ccm-preset:hover, #categoryClosureModal .ccm-preset:focus-visible { border-color: #f59e0b; background: #fffbeb; color: #b45309; outline: 0; }
            #categoryClosureModal .ccm-range { display: grid; grid-template-columns: 1fr auto 1fr; align-items: end; gap: .6rem; }
            #categoryClosureModal .ccm-range .ccm-arrow { padding-bottom: .55rem; color: #cbd5e1; }
            #categoryClosureModal .form-label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
            #categoryClosureModal .form-control { border-radius: 10px; border-color: #e2e8f0; min-height: 42px; }
            #categoryClosureModal .form-control:focus { border-color: #fbbf24; box-shadow: 0 0 0 3px rgba(245, 158, 11, .15); }
            #categoryClosureModal .ccm-summary { display: flex; align-items: center; gap: .5rem; margin-top: .8rem; padding: .55rem .75rem; border-radius: 10px; background: #f8fafc; color: #64748b; font-size: .8rem; }
            #categoryClosureModal .ccm-summary strong { color: #b45309; }
            #categoryClosureModal .ccm-summary.is-invalid { background: #fef2f2; color: #b91c1c; }
            #categoryClosureModal .ccm-badge { font-size: .72rem; font-weight: 700; border-radius: 999px; padding: .2rem .65rem; background: #fef3c7; color: #92400e; }
            #categoryClosureModal .ccm-badge.is-all { background: #fee2e2; color: #991b1b; }
            #categoryClosureModal .ccm-hint { display: block; margin-top: .45rem; font-size: .75rem; color: #94a3b8; }
            #categoryClosureModal .ccm-foot { display: flex; justify-content: flex-end; gap: .5rem; padding: .9rem 1.25rem; border-top: 1px solid #f1f5f9; background: #fafbfc; }
            #categoryClosureModal .ccm-foot .btn { border-radius: 10px; padding: .55rem 1.2rem; font-weight: 600; }
            #categoryClosureModal .ccm-submit { background: #f59e0b; border-color: #f59e0b; color: #fff; min-width: 170px; }
            #categoryClosureModal .ccm-submit:hover, #categoryClosureModal .ccm-submit:focus-visible { background: #d97706; border-color: #d97706; color: #fff; }
            @media (max-width: 575px) {
                #categoryClosureModal .ccm-range { grid-template-columns: 1fr; }
                #categoryClosureModal .ccm-range .ccm-arrow { display: none; }
                #categoryClosureModal .ccm-foot .btn { flex: 1 1 0; min-width: 0; }
            }
        </style>
        <div class="modal fade" id="categoryClosureModal" tabindex="-1" aria-labelledby="categoryClosureTitle"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="ccm-head">
                <div class="ccm-icon"><i class="fa fa-circle-pause"></i></div>
                <div><h5 id="categoryClosureTitle">{{ $u('add_closure') }}</h5><p>{{ $u('closure_hint') }}</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('hourly.cancel') }}"></button>
            </div>
            <form method="POST" action="{{ route('admin.hourly-category-closures.store', $category) }}" id="ccm-form" novalidate
                  data-days="{{ $u('duration_days') }}" data-hours="{{ $u('duration_hours') }}" data-minutes="{{ $u('duration_minutes') }}"
                  data-invalid="{{ $u('closure_invalid_range') }}" data-pick="{{ $u('closure_pick_dates') }}" data-all="{{ $u('closure_scope_all') }}" data-some="{{ $u('closure_scope_some') }}">
                @csrf
                <div class="modal-body ccm-body">
                    <section class="ccm-section">
                        <div class="ccm-section-title"><i class="fa fa-calendar-days"></i> {{ $u('closure_period') }}</div>
                        <div class="ccm-presets">
                            <small>{{ $u('closure_quick') }}:</small>
                            <button type="button" class="ccm-preset" data-preset="today">{{ $u('preset_today') }}</button>
                            <button type="button" class="ccm-preset" data-preset="tomorrow">{{ $u('preset_tomorrow') }}</button>
                            <button type="button" class="ccm-preset" data-preset="3">{{ $u('preset_3days') }}</button>
                            <button type="button" class="ccm-preset" data-preset="7">{{ $u('preset_week') }}</button>
                        </div>
                        <div class="ccm-range">
                            <div>
                                <label class="form-label" for="closure_starts">{{ $u('from') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" id="closure_starts" name="starts_at" value="{{ old('starts_at') }}" min="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}" required>
                                @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <i class="fa fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ccm-arrow" aria-hidden="true"></i>
                            <div>
                                <label class="form-label" for="closure_ends">{{ $u('to') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" id="closure_ends" name="ends_at" value="{{ old('ends_at') }}" min="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}" required>
                                @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="ccm-summary" id="ccm-summary" aria-live="polite"><i class="fa fa-hourglass-half"></i> <span>{{ $u('closure_pick_dates') }}</span></div>
                    </section>

                    <section class="ccm-section">
                        <div class="ccm-section-title"><i class="fa fa-map-location-dot"></i> <label for="closure_districts" class="m-0">{{ $u('closure_districts_label') }}</label> <span class="ccm-badge is-all" id="ccm-scope">{{ $u('closure_scope_all') }}</span></div>
                        <select id="closure_districts" name="district_ids[]" class="form-select" multiple data-placeholder="{{ $u('search_district') }}" data-empty="{{ $u('closure_all_hint') }}">
                            @foreach ($linkedDistricts as $district)<option value="{{ $district->id }}" {{ in_array($district->id, old('district_ids', [])) ? 'selected' : '' }}>{{ $districtNames[$district->id] }}</option>@endforeach
                        </select>
                        <small class="ccm-hint"><i class="fa fa-circle-info"></i> {{ $u('closure_districts_hint') }}</small>
                    </section>

                    <section class="ccm-section">
                        <div class="ccm-section-title"><i class="fa fa-comment-dots"></i> <label for="closure_reason" class="m-0">{{ $u('reason') }}</label></div>
                        <input class="form-control" id="closure_reason" name="reason" maxlength="500" value="{{ old('reason') }}" placeholder="{{ $u('reason_example') }}">
                        <small class="ccm-hint">{{ $u('closure_reason_hint') }}</small>
                    </section>
                </div>
                <div class="ccm-foot">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('hourly.cancel') }}</button>
                    <button type="submit" class="btn ccm-submit"><i class="fa fa-plus ms-1"></i> {{ $u('submit_closure') }}</button>
                </div>
            </form>
        </div></div></div>
        <script>
        (() => {
            const form = document.getElementById('ccm-form');
            if (!form) return;
            const start = form.querySelector('#closure_starts'), end = form.querySelector('#closure_ends');
            const summary = form.querySelector('#ccm-summary'), scope = form.querySelector('#ccm-scope'), districts = form.querySelector('#closure_districts');
            const pad = n => String(n).padStart(2, '0');
            const local = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            const dayStart = (offset = 0) => { const d = new Date(); d.setHours(0, 0, 0, 0); d.setDate(d.getDate() + offset); return d; };
            const fill = (text, count) => text.replace(':count', count);

            function refresh() {
                end.min = start.value || start.min;
                const from = start.value ? new Date(start.value) : null, to = end.value ? new Date(end.value) : null;
                summary.classList.remove('is-invalid');
                if (!from || !to) { summary.lastElementChild.textContent = form.dataset.pick; return; }
                const minutes = Math.round((to - from) / 60000);
                if (minutes <= 0) { summary.classList.add('is-invalid'); summary.lastElementChild.textContent = form.dataset.invalid; return; }
                const parts = [[Math.floor(minutes / 1440), form.dataset.days], [Math.floor(minutes % 1440 / 60), form.dataset.hours], [minutes % 60, form.dataset.minutes]]
                    .filter(([n]) => n > 0).map(([n, text]) => fill(text, n));
                summary.lastElementChild.innerHTML = '';
                summary.lastElementChild.append(document.createElement('strong'));
                summary.lastElementChild.firstChild.textContent = parts.join(' · ');
            }
            function refreshScope() {
                const count = districts ? districts.selectedOptions.length : 0;
                scope.textContent = count ? fill(form.dataset.some, count) : form.dataset.all;
                scope.classList.toggle('is-all', !count);
            }

            form.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
                const preset = button.dataset.preset;
                // اليوم/غدًا يوم كامل؛ 3 أيام/أسبوع تبدأ من يوم البداية المختار (أو اليوم).
                const from = preset === 'tomorrow' ? dayStart(1) : preset === 'today' || !start.value ? dayStart() : new Date(new Date(start.value).setHours(0, 0, 0, 0));
                const days = preset === 'today' || preset === 'tomorrow' ? 1 : Number(preset);
                const to = new Date(from); to.setDate(to.getDate() + days); to.setMinutes(to.getMinutes() - 1);
                start.value = local(from); end.value = local(to); refresh();
            }));
            [start, end].forEach(input => input.addEventListener('input', refresh));
            districts?.addEventListener('change', refreshScope);
            form.addEventListener('submit', e => {
                if (!form.checkValidity() || summary.classList.contains('is-invalid')) { e.preventDefault(); form.reportValidity(); return; }
                if (form.dataset.sent) { e.preventDefault(); return; }
                form.dataset.sent = '1';
                form.querySelector('[type=submit]').disabled = true;
            });
            refresh(); refreshScope();
        })();
        </script>
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
