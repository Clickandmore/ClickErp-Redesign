@extends('admin.layout.indexs.index')
@section('page-title','خادمات الساعات')
@section('style')
@include('admin.hourly_teams._style')
<style>
.maid-availability{display:inline-flex;align-items:center;cursor:pointer;position:relative}
.maid-availability input{position:absolute;opacity:0;width:100%;height:100%;cursor:pointer}
.maid-availability-track{width:64px;height:34px;border:1px solid #e6e8f0;border-radius:30px;background:#fff;padding:3px;display:block;box-shadow:0 4px 16px #6366f11c;pointer-events:none}
.maid-availability-track:before{content:'−';display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#dce0e9;color:white;transition:transform .2s,background .2s;transform:translateX(-27px)}
.maid-availability input:checked + .maid-availability-track:before{content:'✓';background:#6563ed;transform:translateX(0)}
.maid-availability input:focus-visible + .maid-availability-track{outline:2px solid #6563ed;outline-offset:3px}
.maid-availability input:disabled + .maid-availability-track{opacity:.55}
.mh-stat{text-decoration:none;color:inherit;border:2px solid transparent;transition:transform .15s,border-color .15s}
.mh-stat:hover{transform:translateY(-2px);color:inherit}
.mh-stat.is-current{border-color:#6861ee}
.mh-summary{display:flex;align-items:center;flex-wrap:wrap;gap:.5rem}
.mh-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .8rem;border-radius:999px;font-size:.8rem;font-weight:600;white-space:nowrap}
.mh-pill b{font-weight:800}
.mh-pill-dark{background:#64748b;color:#fff}
.mh-pill-green{background:#dcfce7;color:#166534}
.mh-pill-amber{background:#fef3c7;color:#92400e}
.mh-pill-yellow{background:#facc15;color:#422006}
.mh-sep{width:1px;height:22px;background:#cbd5e1;margin:0 .25rem}
.mh-now{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .7rem;border-radius:999px;font-size:.78rem;font-weight:700;white-space:nowrap}
.mh-now-visit{background:#fef3c7;color:#92400e;border:1px solid #fcd34d}
.mh-now-ready{background:#dcfce7;color:#166534;border:1px solid #86efac}
.mh-now-shelter{background:#e0f2fe;color:#075985;border:1px solid #7dd3fc}
.mh-now-muted{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1}
.mh-now-transit{background:#e0f2fe;color:#075985;border:1px solid #7dd3fc}
.mh-now-waiting,.mh-now-ended{background:#fef3c7;color:#92400e;border:1px solid #fcd34d}
.mh-now-overdue{background:#ffedd5;color:#9a3412;border:1px solid #fdba74}
.mh-now-escalated{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.mh-now-meta{font-size:.75rem;color:#64748b;margin-top:.3rem;white-space:nowrap}
.mh-now-meta a{font-weight:700}
.mh-now-count{display:inline-block;margin-inline-start:.25rem;padding:0 .4rem;border-radius:6px;background:#ede9fe;color:#5b21b6;font-weight:700}
.mh-open{display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;border-radius:8px}
@media (max-width:1399px){.mh-open span{display:none}}
.mh-row-actions{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;flex-wrap:nowrap}
.mh-row-actions form,.mh-row-actions span[tabindex]{display:inline-flex;margin:0}
.mh-open-login{color:#0f766e;background:#f0fdfa;border:1px solid #99f6e4}
.mh-open-login:hover,.mh-open-login:focus{color:#fff;background:#0f766e;border-color:#0f766e}
.mh-open-login:disabled{color:#94a3b8;background:#f8fafc;border-color:#e2e8f0}
</style>
@endsection
@section('content')
@php
    $activeFilters = count(\Illuminate\Support\Arr::except($filters, ['per_page']));
    $stateLabels = ['waiting' => 'بانتظار الوصول', 'arrived' => 'تم تسجيل الوصول', 'inside' => 'داخل الإيواء', 'ready' => 'متاحة للتأجير', 'return_overdue' => 'عودة متأخرة — موقوفة عن الحجز']
        + ($visitsTracked ? ['available_now' => __('rental_mode.presence.filter_available'), 'out_now' => __('rental_mode.presence.filter_in_visit')] : []);
    $selects = [
        'attribute_id'=>['السمة','fa-tags',$attributes->mapWithKeys(fn($a)=>[$a->id=>$a->category?->name_ar.' — '.$a->parent?->name.' — '.$a->name])->all()],
        'team_id' => ['الفريق', 'fa-people-group', $teams->mapWithKeys(fn ($t) => [$t->id => $t->name.($t->is_active ? '' : ' (متوقف)')])->all()],
        'nationality_id' => ['الجنسية', 'fa-flag', $nationalities->mapWithKeys(fn ($n) => [$n->id => $n->sub_title ?: $n->title])->all()],
        'state' => ['المرحلة', 'fa-route', $stateLabels],
        'availability' => ['الإتاحة', 'fa-toggle-on', ['available' => 'مسموح بالإتاحة', 'suspended' => 'معلّقة']],
    ];
    $statCards = [
        'total' => ['الإجمالي', 'fa-users', '', ''],
        'waiting' => ['بانتظار الوصول', 'fa-plane-arrival', 'waiting', 'hd-stat-warn'],
        'inside' => ['داخل الإيواء', 'fa-house-user', 'inside', 'hd-stat-info'],
        'ready' => ['متاحة للتأجير', 'fa-circle-check', 'ready', 'hd-stat-on'],
    ];
    // الخروج المؤقت للزيارات لا يظهر في شاشات الإيواء، فيُعرض هنا صراحة.
    if ($visitsTracked) {
        $statCards['available_now'] = [__('rental_mode.presence.available_in_shelter'), 'fa-house-circle-check', 'available_now', 'hd-stat-on'];
        $statCards['out_now'] = [__('rental_mode.presence.in_visit'), 'fa-person-walking-arrow-right', 'out_now', 'hd-stat-warn'];
    }
@endphp
<div class="container-fluid ht-page" dir="rtl">
    <div class="ht-card"><div class="ht-head">
        <div class="ht-head-icon"><i class="fa fa-user-clock"></i></div>
        <div><h5>خادمات الساعات</h5><p>متابعة عاملات إيجار الساعات من الوصول حتى الإيواء والإتاحة للتأجير.</p></div>
        <div class="ht-actions d-flex flex-wrap gap-2">
            @if(AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
                <button type="button" class="btn btn-outline-primary rounded-3" data-bs-toggle="modal" data-bs-target="#assignShelterMaidModal" {{ $allocatableMaids->isEmpty() ? 'disabled' : '' }}>
                    <i class="fa fa-house-user ms-1"></i> تخصيص عاملة من الإيواء
                    <span class="badge bg-primary me-1">{{ $allocatableMaids->count() }}</span>
                </button>
            @endif
            @if(AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                <a class="btn btn-primary rounded-3" href="{{ route('maids.rental.create',['rental_mode'=>'hourly']) }}"><i class="fa fa-plus ms-1"></i> إضافة عاملة جديدة</a>
            @endif
        </div>
    </div></div>

    @if(AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
    <div class="modal fade" id="assignShelterMaidModal" tabindex="-1" aria-labelledby="assignShelterMaidTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('admin.hourly-maids.assign-from-shelter') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignShelterMaidTitle"><i class="fa fa-house-user ms-2"></i> تخصيص عاملة من الإيواء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">تظهر هنا عاملات التأجير الموجودات داخل الإيواء والراغبات في العمل، واللاتي لا يوجد لديهن عقد تأجير نشط.</p>
                    <label class="form-label" for="shelter-maid-id">العاملة المتاحة</label>
                    <select class="form-select" id="shelter-maid-id" name="maid_id" required>
                        <option value="">اختر العاملة</option>
                        @foreach($allocatableMaids as $candidate)
                            <option value="{{ $candidate->id }}" {{ (string)old('maid_id') === (string)$candidate->id ? 'selected' : '' }}>
                                {{ $candidate->name }} — #{{ $candidate->id }}{{ $candidate->passport_num ? ' — جواز '.$candidate->passport_num : '' }}{{ $candidate->nationality ? ' — '.($candidate->nationality->sub_title ?: $candidate->nationality->title) : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('maid_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    <div class="alert alert-info mt-3 mb-0"><i class="fa fa-circle-info ms-1"></i> لن تُنشأ سيرة جديدة؛ ستُحفظ بيانات العاملة وسجل الإيواء الحالي، ثم تُفتح صفحة ملف الساعات لإكمال الفريق والخدمات والجدول.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" {{ $allocatableMaids->isEmpty() ? 'disabled' : '' }}><i class="fa fa-check ms-1"></i> تخصيص للساعات</button>
                </div>
            </form>
        </div></div>
    </div>
    @endif

    {{-- بطاقات المراحل: الضغط عليها يصفّي القائمة --}}
    <div class="hd-stats">
        @foreach($statCards as $key => [$label, $icon, $state, $tone])
        <a class="hd-stat mh-stat {{ $tone }} {{ ($filters['state'] ?? '') === $state ? 'is-current' : '' }}" href="{{ route('admin.hourly-maids.index', array_merge(\Illuminate\Support\Arr::except($filters, ['state']), $state ? ['state' => $state] : [])) }}">
            <div class="hd-stat-icon"><i class="fa {{ $icon }}"></i></div>
            <div><span>{{ $label }}</span><strong class="ht-num" data-stat="{{ $key }}">{{ $stats[$key] }}</strong></div>
        </a>
        @endforeach
    </div>

    @if(($overdueCount ?? 0)>0)
    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2" role="status">
        <span><i class="fa fa-triangle-exclamation ms-1" aria-hidden="true"></i> <strong>{{ $overdueCount }} عاملة تحتاج مراجعة العودة للإيواء.</strong> أُوقف الحجز والإسناد الجديد مؤقتًا حتى تأكيد العودة الفعلية؛ رفع التعليق وحده لا يزيل هذا المنع.</span>
        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.hourly-maids.index',['state'=>'return_overdue']) }}">عرض المتأخرات عن العودة</a>
    </div>
    @endif
    <div id="availability-feedback" class="alert d-none" role="status"></div>

    <form method="GET" action="{{ route('admin.hourly-maids.index') }}" class="hd-filter mb-3">
        <div class="hd-filter-head">
            <div class="hd-filter-title">
                <i class="fa fa-sliders"></i> فلتر قائمة العاملات
                @if($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
            </div>
            <div class="hd-filter-search">
                <i class="fa fa-magnifying-glass"></i>
                <input id="search" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="150" placeholder="الاسم، الهاتف، الجواز، الإقامة أو اسم المستخدم" aria-label="بحث">
            </div>
        </div>
        <div class="hd-filter-body hd-filter-body-compact">
            @foreach($selects as $name => [$label, $icon, $options])
                @php $current = (string) ($filters[$name] ?? ''); @endphp
                <div class="hd-filter-field">
                    <label for="f_{{ $name }}"><i class="fa {{ $icon }}"></i> {{ $label }}</label>
                    <select id="f_{{ $name }}" name="{{ $name }}" class="form-select {{ $current !== '' ? 'is-set' : '' }}" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        @foreach($options as $value => $text)
                            <option value="{{ $value }}" {{ $current === (string) $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="hd-filter-field">
                <label for="per_page"><i class="fa fa-list-ol"></i> عدد الصفوف</label>
                <select id="per_page" name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10,25,50,100] as $size)<option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="hd-filter-foot">
            <div class="mh-summary">
                <span class="mh-pill mh-pill-dark"><i class="fa fa-users"></i> المجموع: <b class="ht-num">{{ $stats['total'] }}</b></span>
                <span class="mh-pill mh-pill-green"><i class="fa fa-circle-check"></i> متاحة: <b class="ht-num" data-stat="ready">{{ $stats['ready'] }}</b></span>
                <span class="mh-pill mh-pill-amber"><i class="fa fa-circle-pause"></i> معلّقة: <b class="ht-num">{{ $suspendedCount }}</b></span>
                <span class="mh-sep"></span>
                <span class="mh-pill mh-pill-yellow"><i class="fa fa-filter"></i> نتائج التصفية: <b class="ht-num">{{ $maids->total() }}</b></span>
            </div>
            <div class="d-flex gap-2">
                @if($activeFilters)
                    <a class="btn btn-light rounded-3" href="{{ route('admin.hourly-maids.index', isset($filters['per_page']) ? ['per_page' => $filters['per_page']] : []) }}"><i class="fa fa-xmark ms-1"></i> مسح الفلاتر</a>
                @endif
                <button class="btn btn-primary rounded-3 px-4"><i class="fa fa-filter ms-1"></i> تطبيق</button>
            </div>
        </div>
    </form>

    <div class="ht-card"><div class="ht-body">
        <div class="table-responsive"><table class="ht-table"><thead><tr><th>#</th><th>العاملة</th><th>الجنسية</th><th>الفرق</th><th>الوصول</th><th>الإيواء</th>@if($visitsTracked)<th>{{ __('rental_mode.presence.title') }}</th>@endif<th>الإتاحة</th><th>الإجراءات</th></tr></thead><tbody>
        @forelse($maids as $maid)
            @php
                $inside=$maid->inside_shelter_count>0;
                $ready=in_array($maid->id,$readyIds);
                $arrival=$maid->arrival_date ? 'وصلت' : ($inside ? 'داخل الإيواء' : ($maid->rental_source==='outside' ? 'بانتظار الوصول' : 'داخل المملكة'));
                $contractStatus=$maid->autoRecruitmentContract?->status;
                if ($contractStatus instanceof \BackedEnum) $contractStatus=$contractStatus->value;
                $stages=['new'=>'بانتظار الاعتماد','msaned'=>'عقد مساند','tafwed'=>'التفويض','taffez'=>'التفييز','tazker'=>'التذكرة','arrival'=>'مرحلة الوصول','completed'=>'مكتمل','cancelled'=>'ملغي'];
            @endphp
            <tr>
                <td>{{ $maid->id }}</td><td><a href="{{ route('admin.hourly-maids.show',$maid) }}"><strong>{{ $maid->name }}</strong></a><div class="small text-muted">{{ $maid->hourly_username }} · {{ $maid->passport_num }}</div>
                    <span data-visibility-badge class="badge bg-{{ $maid->is_show ? 'success' : 'dark' }} mt-1"><i class="fa {{ $maid->is_show ? 'fa-eye' : 'fa-eye-slash' }}"></i> {{ helperTrans($maid->is_show ? 'ingaz.maid_visibility_visible' : 'ingaz.maid_visibility_hidden') }}</span>
                </td>
                <td>{{ $maid->nationality?->sub_title ?: ($maid->nationality?->title ?: '—') }}</td>
                <td>@forelse($maid->hourlyTeamMemberships as $member)<div>{{ $member->team?->name ?? '—' }} <small class="text-muted">({{ $member->role==='primary' ? 'أساسي' : 'ثانوي' }})</small></div>@empty — @endforelse</td>
                <td><span class="badge bg-{{ $maid->arrival_date || $inside ? 'success' : 'warning text-dark' }}">{{ $arrival }}</span>
                    @if($maid->arrival_date)<div class="small mt-1">{{ $maid->arrival_date->format('Y-m-d') }}</div>@elseif($contractStatus)<div class="small mt-1">{{ $stages[$contractStatus] ?? $contractStatus }}</div>@endif
                </td>
                <td>
                    <span class="badge bg-{{ $inside ? 'success' : 'secondary' }}" title="عقد الإيواء، لا مكان العاملة الآن">{{ $inside ? 'له عقد إيواء ساري' : 'بلا عقد إيواء ساري' }}</span>
                    @if(isset($shelterExceptionHints[$maid->id]))
                        <div class="mt-1"><span class="badge bg-warning text-dark" title="{{ $shelterExceptionHints[$maid->id] }}"><i class="fa fa-triangle-exclamation me-1"></i>استكمال بيانات الإيواء</span></div>
                    @endif
                </td>
                @if($visitsTracked)
                    @php
                        $trip = $outNow[$maid->id] ?? null;
                        $next = $upcoming[$maid->id] ?? null;
                        [$nowClass, $nowIcon, $nowLabel] = match (true) {
                            (bool) $trip => ['mh-now-visit', 'fa-person-walking-arrow-right', __('rental_mode.presence.in_visit')],
                            (bool) $maid->hourly_is_suspended => ['mh-now-muted', 'fa-circle-pause', __('rental_mode.presence.suspended')],
                            $ready => ['mh-now-ready', 'fa-house-circle-check', __('rental_mode.presence.available_in_shelter')],
                            $inside => ['mh-now-shelter', 'fa-house-user', __('rental_mode.presence.in_shelter_not_ready')],
                            default => ['mh-now-muted', 'fa-house-circle-xmark', __('rental_mode.presence.outside_shelter')],
                        };
                    @endphp
                    <td>
                        @if($trip)
                            @include('admin.hourly_maids._current-visit-status', ['trip' => $trip])
                        @else
                            <span class="mh-now {{ $nowClass }}"><i class="fa {{ $nowIcon }}"></i> {{ $nowLabel }}</span>
                        @endif
                        @if($trip)
                            <div class="mh-now-meta">
                                @include('admin.hourly_maids._visit-reference',['trip'=>$trip])
                            </div>
                        @endif
                        <div class="mh-now-meta">
                            @if($next)
                                <i class="fa fa-calendar-day"></i> {{ __('rental_mode.presence.next_visit', ['time' => \Carbon\Carbon::parse($next['next_at'])->translatedFormat('D d/m H:i')]) }}
                                <span class="mh-now-count">{{ trans_choice('rental_mode.presence.upcoming', $next['count'], ['count' => $next['count']]) }}</span>
                            @else
                                <span class="text-muted">{{ trans_choice('rental_mode.presence.upcoming', 0) }}</span>
                            @endif
                        </div>
                    </td>
                @endif
                <td>
                    <label class="maid-availability" title="السماح بإتاحة العاملة للساعات أو تعليقها">
                        <input type="checkbox" role="switch" aria-label="السماح بإتاحة {{ $maid->name }} للساعات" data-availability-url="{{ route('admin.hourly-maids.availability',$maid->id) }}" {{ !$maid->hourly_is_suspended ? 'checked' : '' }} {{ AdminGuardHasPerm('hourly_maids_edit') ? '' : 'disabled' }}>
                        <span class="maid-availability-track"></span>
                    </label>
                    <div><span data-availability-label class="badge bg-{{ $ready ? 'success' : 'secondary' }}">{{ $maid->hourly_is_suspended ? 'معلّقة' : (isset($overdueReturns[$maid->id]) ? 'عودة متأخرة — غير متاحة للحجز' : ($ready ? 'متاحة للتأجير' : 'غير متاحة حاليًا')) }}</span></div>
                </td>
                <td class="text-center">
                    <div class="mh-row-actions">
                        @if(AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS))
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-visibility-url="{{ route('maids.show', $maid->id) }}" title="{{ helperTrans('ingaz.toggle_visibility') }}" aria-label="{{ helperTrans('ingaz.toggle_visibility') }}: {{ $maid->name }}"><i class="fa fa-adjust"></i> {{ helperTrans('ingaz.toggle_visibility') }}</button>
                        @endif
                        @include('admin.hourly_maids._login-as',['buttonClass'=>'btn btn-sm mh-open mh-open-login'])
                        <a class="btn btn-sm btn-outline-primary mh-open" href="{{ route('admin.hourly-maids.show',$maid) }}" title="تفاصيل العاملة" aria-label="تفاصيل العاملة {{ $maid->name }}"><i class="fa fa-arrow-up-right-from-square"></i><span>التفاصيل</span></a>
                    </div>
                </td>
            </tr>
        @empty<tr><td colspan="{{ $visitsTracked ? 9 : 8 }}" class="text-center py-4">لا توجد عاملات ساعات مطابقة للبحث.</td></tr>@endforelse
        </tbody></table></div>
        <p class="text-muted small mt-3">المفتاح يسمح بالإتاحة أو يعلّقها في الساعات. الإتاحة الفعلية تتطلب استيفاء شروط الوصول والإيواء وجاهزية التأجير، ولا تعني خلو موعد محدد من الحجوزات.</p>
        {{ $maids->links() }}
    </div></div>
</div>
@endsection
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->has('maid_id'))
    const assignModal=document.getElementById('assignShelterMaidModal');
    if(assignModal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(assignModal).show();
    @endif
    const feedback=document.getElementById('availability-feedback');
    document.querySelectorAll('[data-visibility-url]').forEach(button=>button.addEventListener('click',async function () {
        const result=await Swal.fire({icon:'question',title:@json(helperTrans('ingaz.confirm_toggle_visibility')),showCancelButton:true,confirmButtonText:@json(helperTrans('ingaz.confirm')),cancelButtonText:@json(helperTrans('ingaz.cancel'))});
        if(!result.isConfirmed) return;
        button.disabled=true;
        try {
            const response=await fetch(button.dataset.visibilityUrl,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},cache:'no-store'});
            const data=await response.json();
            if(!response.ok || !data.success) throw new Error(data.message || @json(helperTrans('ingaz.unexpected_error')));
            const visible=Boolean(Number(data.new_status));
            const badge=button.closest('tr').querySelector('[data-visibility-badge]');
            badge.className='badge bg-'+(visible ? 'success' : 'dark')+' mt-1';
            badge.replaceChildren();
            const icon=document.createElement('i');
            icon.className='fa '+(visible ? 'fa-eye' : 'fa-eye-slash');
            badge.append(icon,document.createTextNode(' '+(visible ? @json(helperTrans('ingaz.maid_visibility_visible')) : @json(helperTrans('ingaz.maid_visibility_hidden')))));
            feedback.textContent=data.message;feedback.className='alert alert-success';
        } catch(error) {
            feedback.textContent=error.message;feedback.className='alert alert-danger';
        } finally {button.disabled=false;}
    }));
    document.querySelectorAll('[data-availability-url]').forEach(input=>input.addEventListener('change',async function () {
        const previous=!input.checked;
        input.disabled=true;
        try {
            const response=await fetch(input.dataset.availabilityUrl,{method:'PUT',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({is_suspended:!input.checked})});
            const data=await response.json();
            if(!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'تعذر حفظ الإتاحة.');
            input.checked=!data.is_suspended;
            const label=input.closest('td').querySelector('[data-availability-label]');
            label.textContent=data.label;label.className='badge bg-'+(data.is_ready ? 'success' : 'secondary');
            document.querySelectorAll('[data-stat="ready"]').forEach(el=>el.textContent=data.ready_count);
            feedback.textContent=data.message;feedback.className='alert alert-success';
            if(@json(request('state')==='ready') && !data.is_ready) input.closest('tr').remove();
        } catch(error) {
            input.checked=previous;feedback.textContent=error.message;feedback.className='alert alert-danger';
        } finally {input.disabled=false;}
    }));
});
</script>
@endpush
