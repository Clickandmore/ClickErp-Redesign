@if (\App\Support\HourlyRentalSettings::maidProfilesLinked())
@php
    $hourlyMaid = $obj ?? null;
    $hourlyValues = $hourlyMaid?->hourly_settings ?? [];
    $savedServices=$hourlyValues['service_category_ids'] ?? [];
    $selectedServices=old('hourly.services_present') ? old('hourly.service_category_ids',[]) : old('hourly.service_category_ids',$savedServices);
    $hourlyServices=\Illuminate\Support\Facades\Schema::hasTable('hourly_service_categories') ? \App\Models\Hourly\HourlyServiceCategory::where(fn($q)=>$q->where('is_active',true)->orWhereIn('id',$savedServices))->ordered()->get() : collect();
    $hourlyMembers = $hourlyMaid ? \App\Models\Hourly\HourlyTeamMember::where('member_type','maid')->where('member_id',$hourlyMaid->id)->whereNull('left_at')->get() : collect();
    $hourlyTeams = \App\Models\Hourly\HourlyTeam::where(fn($q)=>$q->where('is_active',true)->orWhereIn('id',$hourlyMembers->pluck('hourly_team_id')))->orderBy('name')->get();
    $requiresDriver = (int) old('hourly.requires_driver',$hourlyValues['requires_driver'] ?? 1);
    $accountEnabled = (int) old('hourly.account_enabled',$hourlyValues['account_enabled'] ?? 0);
@endphp
<style>
    #maid-hourly-section { --mh-line: #e2e8f0; --mh-soft: #f8fafc; --mh-muted: #64748b; --mh-ink: #0f172a; --mh-accent: #6861ee; }
    #maid-hourly-section fieldset { all: unset; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; min-width: 0; }
    #maid-hourly-section fieldset[disabled] { display: none; }
    #maid-hourly-section .mh-titlebar { display: flex; align-items: center; gap: .75rem; margin: .35rem 0 1rem; padding: .8rem .9rem; border-radius: 11px; color: var(--mh-ink); background: linear-gradient(135deg, #eef2ff, #f8fafc); }
    #maid-hourly-section .mh-titlebar i { color: #4f46e5; }
    #maid-hourly-section .mh-titlebar strong { font-size: .95rem; }
    #maid-hourly-section .mh-titlebar small { display: block; margin-top: .12rem; color: var(--mh-muted); }
    #maid-hourly-section .mh-mode { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .85rem 1rem; border: 1px solid var(--mh-line); border-radius: 14px; background: var(--mh-soft); margin-bottom: 1rem; }
    #maid-hourly-section .mh-mode label { margin: 0; font-weight: 700; color: var(--mh-ink); }
    #maid-hourly-section .mh-mode select { max-width: 260px; }
    #maid-hourly-section .mh-card { border: 1px solid var(--mh-line); border-radius: 16px; background: #fff; margin: 0; overflow: hidden; min-width: 0; }
    #maid-hourly-section .mh-card--wide, #maid-hourly-section fieldset > h5 { grid-column: 1 / -1; }
    #maid-hourly-section fieldset > h5 { margin: 0 !important; padding-top: .15rem; font-size: 1rem; }
    #maid-hourly-section .mh-head { display: flex; align-items: center; gap: .75rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--mh-line); background: linear-gradient(135deg, #f8fafc, #fff); }
    #maid-hourly-section .mh-icon { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 11px; display: grid; place-items: center; background: #eef2ff; color: #4338ca; }
    #maid-hourly-section .mh-head h6 { margin: 0; font-weight: 700; color: var(--mh-ink); }
    #maid-hourly-section .mh-head p { margin: .1rem 0 0; font-size: .8rem; color: var(--mh-muted); }
    #maid-hourly-section .mh-head .mh-badge { margin-inline-start: auto; }
    #maid-hourly-section .mh-body { padding: 1rem 1.1rem; }
    #maid-hourly-section .mh-badge { font-size: .75rem; font-weight: 700; border-radius: 999px; padding: .2rem .65rem; background: #eef2ff; color: #4338ca; white-space: nowrap; }
    #maid-hourly-section .mh-badge.is-zero { background: #fef3c7; color: #92400e; }
    #maid-hourly-section .mh-services { display: flex; flex-wrap: wrap; gap: .5rem; }
    #maid-hourly-section .mh-service { position: relative; margin: 0; cursor: pointer; }
    #maid-hourly-section .mh-service input { position: absolute; opacity: 0; pointer-events: none; }
    #maid-hourly-section .mh-service span { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem .9rem; border: 1px solid var(--mh-line); border-radius: 999px; background: #fff; color: #334155; font-weight: 600; font-size: .88rem; transition: all .15s; }
    #maid-hourly-section .mh-service span i { color: #cbd5e1; }
    #maid-hourly-section .mh-service input:checked + span { border-color: var(--mh-accent); background: #eef2ff; color: #3730a3; }
    #maid-hourly-section .mh-service input:checked + span i { color: var(--mh-accent); }
    #maid-hourly-section .mh-service input:focus-visible + span { outline: 3px solid #c7d2fe; outline-offset: 2px; }
    #maid-hourly-section .mh-hint { font-size: .8rem; color: var(--mh-muted); margin-top: .75rem; display: block; }
    #maid-hourly-section .mh-days { width: 100%; border-collapse: separate; border-spacing: 0; }
    /* يوم في كل صف: اسم اليوم، ثم نظام العمل، ثم وقتا البداية والنهاية بعرض كافٍ لا يخرج منه النص */
    #maid-hourly-section .mh-days tbody { display: grid; grid-template-columns: minmax(0, 1fr); gap: .55rem; }
    #maid-hourly-section .mh-days tr { display: grid; grid-template-columns: 80px minmax(150px, 190px) minmax(0, 1fr); align-items: center; border-radius: 10px; background: var(--mh-soft); }
    #maid-hourly-section .mh-days select.form-control { padding-inline-end: 2rem; text-overflow: ellipsis; }
    #maid-hourly-section .mh-days tr.has-error { box-shadow: inset 0 0 0 1px #fca5a5; background: #fef2f2; }
    #maid-hourly-section .mh-time-error { display: block; font-size: .8rem; color: #b91c1c; margin-top: .25rem; }
    #maid-hourly-section .mh-help { display: block; font-size: .78rem; color: var(--mh-muted); margin-top: .25rem; }
    #maid-hourly-section .mh-field-error { display: block; font-size: .8rem; color: #b91c1c; margin-top: .25rem; }
    #maid-hourly-section .mh-days td { display: block; padding: .45rem; vertical-align: middle; background: transparent; }
    #maid-hourly-section .mh-days td:first-child { font-weight: 700; }
    #maid-hourly-section .mh-days tr.is-off td:first-child { color: #94a3b8; }
    #maid-hourly-section .mh-times { display: flex; align-items: center; gap: .4rem; }
    #maid-hourly-section .mh-times input { flex: 1 1 0; min-width: 0; max-width: 170px; }
    #maid-hourly-section .mh-times-note { font-size: .8rem; color: var(--mh-muted); }
    #maid-hourly-section .mh-section-title { font-size: .78rem; font-weight: 700; color: var(--mh-muted); margin: 0 0 .6rem; display: flex; align-items: center; gap: .5rem; }
    #maid-hourly-section .mh-section-title::after { content: ''; flex: 1; height: 1px; background: var(--mh-line); }
    @media (max-width: 1199.98px) {
        #maid-hourly-section fieldset { grid-template-columns: 1fr; }
        #maid-hourly-section .mh-card--wide, #maid-hourly-section fieldset > h5 { grid-column: auto; }
    }
    @media (max-width: 575.98px) {
        #maid-hourly-section .mh-mode { align-items: stretch; }
        #maid-hourly-section .mh-mode select { max-width: none; width: 100%; }
        #maid-hourly-section .mh-head { align-items: flex-start; flex-wrap: wrap; }
        #maid-hourly-section .mh-head .mh-badge { margin-inline-start: 0; }
        #maid-hourly-section .mh-body { padding: .85rem; }
        #maid-hourly-section .mh-days tr { grid-template-columns: 1fr; padding: .35rem; }
        #maid-hourly-section .mh-days td { width: 100% !important; padding: .25rem; }
        #maid-hourly-section .mh-times input { min-width: 0; max-width: none; }
    }
</style>
<div class="col-12 ht-page" id="maid-hourly-section" style="display:none" dir="rtl">
    <div class="mh-titlebar">
        <i class="fa fa-clock"></i>
        <div><strong>إعدادات عاملة الإيجار</strong><small>تظهر إعدادات الساعات فقط عند اختيار «إيجار ساعات».</small></div>
    </div>
    <div class="mh-mode" @if($hourlyProfilePage ?? false) style="display:none" @endif>
        <label for="maid-rental-mode"><i class="fa fa-clock ms-1"></i> نوع عاملة الإيجار</label>
        <select class="form-control" name="rental_mode" id="maid-rental-mode" disabled>
            @unless($hourlyProfilePage ?? false)<option value="regular">عادية</option>@endunless
            <option value="hourly" {{ (old('rental_mode',$hourlyMaid?->rental_mode ?? request('rental_mode'))==='hourly') ? 'selected' : '' }}>إيجار ساعات</option>
        </select>
        <small class="text-muted">عاملة الساعات تُسند لزيارات قصيرة وتحتاج خدمات وفريقًا وجدول عمل.</small>
    </div>

    <fieldset id="maid-hourly-fields" disabled>
        <h5 class="mt-4 mb-0 fw-bold">بيانات عاملة الساعات</h5>
        @if(\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes'))
        @php
            $savedAttributes=$hourlyMaid?$hourlyMaid->productAttributes()->pluck('hourly_product_attributes.id')->all():[];
            $chosenAttributes=old('hourly.attributes_present')?old('hourly.attribute_ids',[]):$savedAttributes;
            $workerAttributes=\App\Models\Hourly\HourlyProductAttribute::where(fn($q)=>$q->selectable()->whereHas('category',fn($c)=>$c->where('is_active',true))->orWhereIn('id',$savedAttributes))->with(['parent','category'])->orderBy('sort_order')->get();
        @endphp
        <div class="mh-card mh-card--wide"><div class="mh-head"><h6>سمات العاملة لخدمات الساعات</h6></div><div class="mh-body"><input type="hidden" name="hourly[attributes_present]" value="1">
        @foreach($workerAttributes->groupBy(fn($a)=>$a->category?->name_ar.' — '.$a->parent?->name) as $label=>$attributes)<h6>{{ $label }}</h6>@foreach($attributes as $attribute)<label class="d-flex gap-2 mb-2"><input type="checkbox" name="hourly[attribute_ids][]" value="{{ $attribute->id }}" {{ in_array($attribute->id,(array)$chosenAttributes)?'checked':'' }}> {{ $attribute->name }}</label>@endforeach @endforeach
        <small>تُستخدم عند تفعيل «الإسناد حسب سمة المنتج» في الفئة.</small></div></div>
        @endif

        @if(\Illuminate\Support\Facades\Schema::hasTable('hourly_service_categories'))
        {{-- الخدمات --}}
        <div class="mh-card mh-card--services">
            <div class="mh-head">
                <div class="mh-icon"><i class="fa fa-broom"></i></div>
                <div><h6>نوع الخدمة</h6><p>الخدمات التي تُرشَّح لها العاملة عند إسناد الزيارات</p></div>
                <span class="mh-badge" data-service-count></span>
            </div>
            <div class="mh-body">
                <input type="hidden" name="hourly[services_present]" value="1">
                @if($hourlyServices->isEmpty())
                    <p class="text-muted mb-0">لا توجد خدمات نشطة. أضف فئات الخدمة من موديول الساعات أولًا.</p>
                @else
                    <div class="mh-services">
                        @foreach($hourlyServices as $service)
                        <label class="mh-service" for="maid-service-{{ $service->id }}">
                            <input type="checkbox" id="maid-service-{{ $service->id }}" name="hourly[service_category_ids][]" value="{{ $service->id }}" {{ in_array($service->id,(array)$selectedServices) ? 'checked' : '' }}>
                            <span><i class="fa fa-circle-check"></i>{{ app()->getLocale()==='en' ? ($service->name_en ?: $service->name_ar) : $service->name_ar }}{{ $service->is_active ? '' : ' (متوقفة)' }}</span>
                        </label>
                        @endforeach
                    </div>
                @endif
                <small class="mh-hint"><i class="fa fa-circle-info ms-1"></i> يمكن اختيار أكثر من خدمة. إلغاء جميع الاختيارات يوقف ترشيح العاملة للخدمات.</small>
            </div>
        </div>
        @endif

        {{-- الفرق والتشغيل --}}
        <div class="mh-card mh-card--operations">
            <div class="mh-head">
                <div class="mh-icon"><i class="fa fa-people-group"></i></div>
                <div><h6>الفرق والتشغيل</h6><p>الفريق الذي تنطلق معه العاملة وحدود تشغيلها</p></div>
            </div>
            <div class="mh-body row g-3 m-0">
                <div class="col-md-6"><label class="form-label" for="maid-primary-team">الفريق الأساسي</label><select class="form-control" id="maid-primary-team" name="hourly[primary_team_id]">
                    <option value="">بدون فريق</option>
                    @foreach($hourlyTeams as $team)<option value="{{ $team->id }}" {{ (old('hourly.primary_team_id',$hourlyMembers->firstWhere('role','primary')?->hourly_team_id)==$team->id) ? 'selected' : '' }}>{{ $team->name }}</option>@endforeach
                </select></div>
                <div class="col-md-6"><label class="form-label" for="maid-secondary-teams">الفرق الثانوية</label><select class="form-control" id="maid-secondary-teams" multiple name="hourly[secondary_team_ids][]" data-placeholder="ابحث عن فريق…" data-empty="لا توجد فرق نشطة.">
                    @foreach($hourlyTeams as $team)<option value="{{ $team->id }}" {{ (in_array($team->id,old('hourly.secondary_team_ids',$hourlyMembers->where('role','secondary')->pluck('hourly_team_id')->all()))) ? 'selected' : '' }}>{{ $team->name }}</option>@endforeach
                </select></div>
                <div class="col-md-6"><label class="form-label" for="maid-requires-driver">تعيين سائق؟</label><select class="form-control" id="maid-requires-driver" name="hourly[requires_driver]">
                    <option value="1" {{ $requiresDriver===1 ? 'selected' : '' }}>نعم، تحتاج سائقًا للوصول</option><option value="0" {{ $requiresDriver===0 ? 'selected' : '' }}>لا</option>
                </select></div>
                <div class="col-md-6"><label class="form-label" for="maid-max-orders">الحد الأقصى للطلبات في الساعة</label><input class="form-control" id="maid-max-orders" type="number" min="1" max="100" name="hourly[max_orders_per_hour]" value="{{ old('hourly.max_orders_per_hour',$hourlyValues['max_orders_per_hour'] ?? 1) }}" required></div>
            </div>
        </div>

        {{-- أيام وساعات العمل --}}
        <div class="mh-card mh-card--wide">
            <div class="mh-head">
                <div class="mh-icon"><i class="fa fa-calendar-week"></i></div>
                <div><h6>أيام وساعات العمل</h6><p>لكل يوم: إجازة، أو طوال اليوم، أو ساعات محددة</p></div>
            </div>
            <div class="mh-body">
                <table class="mh-days">
                    <tbody>
                        @foreach(\App\Models\Hourly\HourlyDriver::DAYS as $day=>$label)
                        @php($schedule=old('hourly.work_schedule.'.$day,$hourlyValues['work_schedule'][$day] ?? ['mode'=>'off']))
                        <tr class="hourly-day">
                            <td>{{ $label }}</td>
                            <td><select class="form-control hourly-day-mode" name="hourly[work_schedule][{{ $day }}][mode]" aria-label="نظام العمل يوم {{ $label }}">
                                @foreach(['off'=>'إجازة','all_day'=>'طوال اليوم','hours'=>'ساعات محددة'] as $value=>$title)<option value="{{ $value }}" {{ (($schedule['mode'] ?? 'off')===$value) ? 'selected' : '' }}>{{ $title }}</option>@endforeach
                            </select></td>
                            <td>
                                <div class="mh-times" data-times>
                                    <input aria-label="بداية دوام {{ $label }}" class="form-control hourly-time" type="time" name="hourly[work_schedule][{{ $day }}][start]" value="{{ $schedule['start'] ?? '09:00' }}">
                                    <span class="text-muted">إلى</span>
                                    <input aria-label="نهاية دوام {{ $label }}" class="form-control hourly-time" type="time" name="hourly[work_schedule][{{ $day }}][end]" value="{{ $schedule['end'] ?? '16:00' }}">
                                </div>
                                <span class="mh-times-note" data-times-note></span>
                                <span class="mh-time-error" data-time-error></span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- حساب التطبيق --}}
        <div class="mh-card mh-card--wide">
            <div class="mh-head">
                <div class="mh-icon"><i class="fa fa-mobile-screen"></i></div>
                <div><h6>معلومات التطبيق</h6><p>دخول العاملة لتطبيق الساعات</p></div>
                <span class="mh-badge {{ $accountEnabled ? '' : 'is-zero' }}" data-account-badge>{{ $accountEnabled ? 'الحساب مفعّل' : 'الحساب معطّل' }}</span>
            </div>
            <div class="mh-body">
                <div class="mh-section-title">الوصول</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label" for="maid-hourly-username">اسم المستخدم</label><input class="form-control bg-light" id="maid-hourly-username" readonly dir="ltr" value="{{ $hourlyMaid?->hourly_username }}" placeholder="يُنشأ تلقائيًا بعد الحفظ"></div>
                    <div class="col-md-4"><label class="form-label" for="maid-account-enabled">تفعيل الحساب</label><select class="form-control" id="maid-account-enabled" name="hourly[account_enabled]" data-account-enabled>
                        <option value="0">لا</option><option value="1" {{ $accountEnabled===1 ? 'selected' : '' }}>نعم</option>
                    </select></div>
                    <div class="col-md-4"><label class="form-label" for="maid-login-access">السماح بالدخول من</label><select class="form-control" id="maid-login-access" name="hourly[login_access]">
                        @foreach(['both'=>'الموقع والجوال','web'=>'الموقع','mobile'=>'الجوال'] as $value=>$label)<option value="{{ $value }}" {{ (old('hourly.login_access',$hourlyValues['login_access'] ?? 'both')===$value) ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select></div>
                </div>
                <div class="mh-section-title">بيانات الدخول</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="maid-login-phone">هاتف تسجيل الدخول</label><input class="form-control" id="maid-login-phone" name="hourly[login_phone]" dir="ltr" inputmode="numeric" maxlength="20" autocomplete="off" placeholder="05XXXXXXXX" value="{{ old('hourly.login_phone',$hourlyMaid?->hourly_login_phone) }}" data-hourly-login-phone><span class="mh-field-error" data-error-for="hourly[login_phone]"></span><small class="mh-help">اختياري: أرقام فقط من 5 إلى 20 رقمًا، بديل عن اسم المستخدم ولا يتكرر.</small></div>
                    <div class="col-md-6"><label class="form-label" for="maid-login-email">البريد الإلكتروني</label><input class="form-control" id="maid-login-email" type="email" dir="ltr" name="hourly[email]" value="{{ old('hourly.email',$hourlyMaid?->hourly_email) }}"><small class="mh-help">اختياري، بحروف إنجليزية.</small></div>
                    <div class="col-md-6"><label class="form-label" for="maid-login-password">كلمة المرور (8 أحرف على الأقل)</label><div class="input-group"><input class="form-control" id="maid-login-password" type="password" dir="ltr" autocomplete="new-password" maxlength="72" name="hourly[password]"><button type="button" class="btn btn-outline-secondary" data-toggle-password="maid-login-password" title="إظهار كلمة المرور" aria-label="إظهار كلمة المرور"><i class="fa fa-eye"></i></button></div><span class="mh-field-error" data-error-for="hourly[password]"></span><small class="mh-help">عند التعديل اتركها فارغة للإبقاء على كلمة المرور.</small></div>
                    <div class="col-md-6"><label class="form-label" for="maid-login-password-confirmation">تأكيد كلمة المرور</label><div class="input-group"><input class="form-control" id="maid-login-password-confirmation" type="password" dir="ltr" autocomplete="new-password" maxlength="72" name="hourly[password_confirmation]"><button type="button" class="btn btn-outline-secondary" data-toggle-password="maid-login-password-confirmation" title="إظهار كلمة المرور" aria-label="إظهار تأكيد كلمة المرور"><i class="fa fa-eye"></i></button></div><span class="mh-field-error" data-error-for="hourly[password_confirmation]"></span><small class="mh-help">أعد كتابة كلمة المرور نفسها.</small></div>
                </div>
            </div>
        </div>
    </fieldset>
</div>
@include('admin.hourly_teams._multiselect')
<script>
(function () {
const HAS_PASSWORD = @json((bool) ($hourlyMaid?->hourly_password));
// النهاية بعد البداية في اليوم نفسه، ولا تتساويان
function scheduleError(start, end) {
    if (!start || !end) return 'حدد وقت بداية العمل ونهايته.';
    return end <= start ? 'وقت النهاية يجب أن يكون بعد وقت البداية (لا يتساويان).' : '';
}
function initHourlyMaidFields() {
    const section = document.getElementById('maid-hourly-section');
    if (!section) return;
    const form = section.closest('form'), type = form.querySelector('select[name="type_id"]');
    const mode = document.getElementById('maid-rental-mode'), fields = document.getElementById('maid-hourly-fields');
    function refreshHourly() {
        const rental = /ايجار|إيجار|تأجير|rent/i.test(type?.options[type.selectedIndex]?.text || '');
        section.style.display = rental ? '' : 'none'; mode.disabled = !rental;
        fields.disabled = !rental || mode.value !== 'hourly';
        section.querySelectorAll('.hourly-day').forEach(row => {
            const dayMode = row.querySelector('.hourly-day-mode').value, hours = dayMode === 'hours';
            row.classList.toggle('is-off', dayMode === 'off');
            row.querySelector('[data-times]').style.display = hours ? '' : 'none';
            row.querySelector('[data-times-note]').textContent = hours ? '' : (dayMode === 'off' ? 'يوم إجازة' : 'متاحة طوال اليوم');
            row.querySelectorAll('.hourly-time').forEach(input => { input.disabled = fields.disabled || !hours; });
            const [start, end] = row.querySelectorAll('.hourly-time');
            const timeError = hours ? scheduleError(start.value, end.value) : '';
            row.classList.toggle('has-error', Boolean(timeError));
            row.querySelector('[data-time-error]').textContent = timeError;
        });
        const count = section.querySelectorAll('[name="hourly[service_category_ids][]"]:checked').length, badge = section.querySelector('[data-service-count]');
        if (badge) { badge.textContent = count ? count + ' خدمة مختارة' : 'لا توجد خدمة مختارة'; badge.classList.toggle('is-zero', !count); }
        const account = section.querySelector('[data-account-enabled]'), accountBadge = section.querySelector('[data-account-badge]');
        accountBadge.textContent = account.value === '1' ? 'الحساب مفعّل' : 'الحساب معطّل';
        accountBadge.classList.toggle('is-zero', account.value !== '1');
    }
    $(type).on('change', refreshHourly); mode.addEventListener('change', refreshHourly);
    section.addEventListener('change', refreshHourly);
    section.addEventListener('input', event => { if (event.target.classList.contains('hourly-time')) refreshHourly(); });
    refreshHourly();

    // هاتف الدخول: أرقام فقط؛ الحروف والرموز وإشارة السالب تُحذف فور كتابتها
    const phone = section.querySelector('[data-hourly-login-phone]');
    const password = document.getElementById('maid-login-password'), confirmation = document.getElementById('maid-login-password-confirmation');
    const setError = (name, text) => {
        const box = section.querySelector('[data-error-for="' + name + '"]'), input = form.elements[name];
        if (box) box.textContent = text || '';
        if (input) input.classList.toggle('is-invalid', Boolean(text));
    };
    const phoneError = () => !phone.value || /^\d{5,20}$/.test(phone.value) ? '' : 'هاتف تسجيل الدخول من 5 إلى 20 رقمًا.';
    const passwordError = () => password.value && password.value.length < 8 ? 'كلمة المرور يجب ألا تقل عن 8 أحرف (المُدخل ' + password.value.length + ').'
        : (!password.value && section.querySelector('[data-account-enabled]').value === '1' && !HAS_PASSWORD ? 'كلمة المرور مطلوبة لتفعيل حساب التطبيق.' : '');
    const confirmError = () => (password.value || confirmation.value) && confirmation.value !== password.value
        ? (confirmation.value ? 'تأكيد كلمة المرور غير مطابق لكلمة المرور.' : 'أعد كتابة كلمة المرور للتأكيد.') : '';
    phone.addEventListener('input', () => {
        const clean = phone.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 20);
        const stripped = clean !== phone.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
        if (clean !== phone.value) phone.value = clean;
        setError('hourly[login_phone]', stripped ? 'هاتف تسجيل الدخول يقبل الأرقام فقط، بدون حروف أو رموز أو إشارة سالب.' : (phone.classList.contains('is-invalid') ? phoneError() : ''));
    });
    phone.addEventListener('blur', () => setError('hourly[login_phone]', phoneError()));
    password.addEventListener('input', () => { if (password.classList.contains('is-invalid')) setError('hourly[password]', passwordError()); if (confirmation.value) setError('hourly[password_confirmation]', confirmError()); });
    password.addEventListener('blur', () => setError('hourly[password]', passwordError()));
    // التأكيد يُفحص أثناء الكتابة
    confirmation.addEventListener('input', () => setError('hourly[password_confirmation]', confirmError()));

    // معاينة كلمة المرور
    section.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword), show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.querySelector('i').className = 'fa ' + (show ? 'fa-eye-slash' : 'fa-eye');
        button.title = show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور';
    }));

    // قبل الحفظ: أخطاء قسم الساعات تمنع الإرسال وتظهر برسالة واضحة (يسبق معالج الحفظ في النموذج)
    form.addEventListener('submit', event => {
        if (fields.disabled) return;
        refreshHourly();
        const errors = Array.from(section.querySelectorAll('[data-time-error]')).map(el => el.textContent).filter(Boolean);
        [['hourly[login_phone]', phoneError()], ['hourly[password]', passwordError()], ['hourly[password_confirmation]', confirmError()]].forEach(([name, error]) => { setError(name, error); if (error) errors.push(error); });
        if (!errors.length) return;
        event.preventDefault(); event.stopImmediatePropagation();
        (section.querySelector('.has-error, .is-invalid') || section).scrollIntoView({behavior: 'smooth', block: 'center'});
        if (window.Swal) Swal.fire({icon: 'error', title: 'راجع إعدادات عاملة الساعات', html: errors.map(e => '<div>' + e.replace(/[<>&]/g, '') + '</div>').join(''), confirmButtonText: 'حسنًا'});
        else alert(errors.join('\n'));
    }, true);
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initHourlyMaidFields);
else initHourlyMaidFields();
})();
</script>
@endif
