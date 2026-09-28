{{-- مودال إجراءات الزيارة: ملخص الزيارة ثم بطاقات الإجراءات ثم حقول الإجراء المختار. --}}
<div class="modal fade om-modal" id="hs-visit-modal" data-op-step="pick" tabindex="-1" aria-labelledby="hs-visit-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" id="hs-visit-form" novalidate>
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-calendar-day" id="hs-visit-icon"></i></span><div><h5 id="hs-visit-title">إجراءات الزيارة</h5><p id="hs-visit-sub"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
        <div class="modal-body">
            <div class="hs-summary" id="hs-summary"></div>

            <div data-op-picker>
                <section class="om-op-section"><h6>الموعد والتعيين</h6><div class="om-op-grid">
                    <button type="button" class="om-op-tile om-op-indigo" data-pick="reschedule" data-edit data-label="إعادة جدولة" data-icon="fa-calendar-days"><i class="fa fa-calendar-days"></i><span>إعادة جدولة<small>تاريخ ووقت جديدان</small></span></button>
                    <button type="button" class="om-op-tile om-op-teal" data-pick="reassign" data-edit data-label="إعادة تعيين" data-icon="fa-right-left"><i class="fa fa-right-left"></i><span>إعادة تعيين<small>اختيار العاملات المتاحات</small></span></button>
                    <button type="button" class="om-op-tile om-op-red" data-pick="unassign" data-edit data-needs-worker data-label="إلغاء تعيين العامل" data-icon="fa-user-xmark"><i class="fa fa-user-xmark"></i><span>إلغاء تعيين العامل<small>يبقى المقعد شاغرًا</small></span></button>
                    <button type="button" class="om-op-tile om-op-green" data-pick="add_worker" data-edit data-price data-label="إضافة عامل" data-icon="fa-user-plus"><i class="fa fa-user-plus"></i><span>إضافة عامل<small>يزيد العدد والسعر</small></span></button>
                    <button type="button" class="om-op-tile om-op-red" data-pick="remove_worker" data-edit data-price data-needs-worker data-label="إزالة عامل" data-icon="fa-user-minus"><i class="fa fa-user-minus"></i><span>إزالة عامل<small>يقلل العدد والسعر</small></span></button>
                </div></section>
                <section class="om-op-section"><h6>المال والإغلاق</h6><div class="om-op-grid">
                    <button type="button" class="om-op-tile om-op-amber" data-pick="price" data-edit data-price data-label="تغيير سعر الزيارة" data-icon="fa-dollar-sign"><i class="fa fa-dollar-sign"></i><span>تغيير السعر<small>سعر هذه الزيارة فقط</small></span></button>
                    <button type="button" class="om-op-tile om-op-red" data-pick="cancel" data-edit data-label="إلغاء الزيارة" data-icon="fa-ban"><i class="fa fa-ban"></i><span>إلغاء الزيارة<small>مع خيارات الاسترداد</small></span></button>
                </div></section>
                <section class="om-op-section"><h6>أخرى</h6><div class="om-op-grid">
                    <button type="button" class="om-op-tile om-op-blue" data-pick="copy" data-label="نسخ التفاصيل" data-icon="fa-copy"><i class="fa fa-copy"></i><span>نسخ التفاصيل<small>حسب إعدادات النسخ</small></span></button>
                    <a class="om-op-tile om-op-violet" id="hs-order-link" href="#" target="_blank" rel="noopener"><i class="fa fa-arrow-up-right-from-square"></i><span>فتح الطلب<small>إدارة الطلب كاملًا</small></span></a>
                </div></section>
            </div>

            <div data-op-form>
                <div data-fields="reschedule"><label for="hs-starts-at"><i class="fa fa-calendar-days"></i> الموعد الجديد</label><input class="form-control" type="datetime-local" id="hs-starts-at" name="starts_at"><small class="form-text">يُتحقق من توفر العاملات والسائقين في الموعد الجديد.</small></div>
                <div data-fields="reassign">
                    <label><i class="fa fa-user-nurse"></i> العاملات <small id="hs-workers-note"></small></label>
                    <div class="hs-workers" id="hs-workers-list"><span class="form-text">جارٍ التحقق من التوفر…</span></div>
                </div>
                <div data-fields="unassign remove_worker"><label for="hs-worker-select"><i class="fa fa-user-nurse"></i> العاملة</label><select class="form-select" id="hs-worker-select" name="worker_id"></select></div>
                <div data-fields="add_worker"><label for="hs-add-select"><i class="fa fa-user-plus"></i> العاملة المضافة</label><select class="form-select" id="hs-add-select" name="add_worker_id"><option value="">جارٍ التحميل…</option></select><small class="form-text">يزيد عدد العاملات في هذه الزيارة ويرتفع سعرها بنسبة الزيادة.</small></div>
                <div data-fields="price"><div class="hs-price-row">
                    <div><label><i class="fa fa-tag"></i> سعر الزيارة</label><div class="input-group"><input class="form-control" id="hs-price-current" readonly><span class="input-group-text">SAR</span></div></div>
                    <div><label for="hs-price-new"><i class="fa fa-pen"></i> السعر الجديد</label><div class="input-group"><input class="form-control" type="number" min="0" step="0.01" id="hs-price-new" name="price"><span class="input-group-text">SAR</span></div></div>
                </div></div>
                <div data-fields="cancel"><label for="hs-refund"><i class="fa fa-rotate-left"></i> طريقة الاسترداد</label><select class="form-select" id="hs-refund" name="refund_method">@if($canCollect)<option value="wallet">إلى رصيد العميل</option><option value="cash">نقدًا</option><option value="bank_transfer">تحويل بنكي</option>@endif<option value="none">بدون استرداد (تُحتسب قيمة الزيارة كرسوم إلغاء)</option></select><small class="form-text">يُرد فقط ما يزيد من السداد على قيمة الطلب بعد الإلغاء.</small></div>
                <div data-fields="reassign unassign add_worker remove_worker price cancel"><label class="mt-2" for="hs-reason"><i class="fa fa-pen"></i> سبب التعديل <small>(يُسجَّل في السجلات)</small></label><textarea class="form-control" id="hs-reason" name="reason" rows="2" maxlength="2000"></textarea></div>
            </div>
            <div class="alert alert-danger mt-3" id="hs-visit-error" role="alert" hidden></div>
        </div>
        <div class="modal-footer" data-op-form>
            <button class="btn btn-primary" id="hs-visit-submit"><i class="fa fa-circle-check"></i> حفظ التغييرات</button>
            <button type="button" class="btn btn-light" id="hs-visit-back"><i class="fa fa-arrow-right"></i> كل الإجراءات</button>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
        </div>
    </form></div>
</div>

{{-- إعدادات التقويم: نطاق الساعات وارتفاع الصف، وحقول نسخ التفاصيل بالسحب والإفلات. --}}
@if($canSettings)
<div class="modal fade om-modal" id="hs-settings-modal" tabindex="-1" aria-labelledby="hs-settings-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" id="hs-settings-form" novalidate>
        <div class="modal-header"><span class="om-m-icon"><i class="fa fa-gear"></i></span><div><h5 id="hs-settings-title">إعدادات التقويم</h5><p>تُطبَّق على جميع المستخدمين في هذا الحساب</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
        <div class="modal-body">
            <nav class="hs-tabs" role="tablist">
                <button type="button" class="active" data-tab="calendar" role="tab"><i class="fa fa-calendar-days"></i> التقويم</button>
                <button type="button" data-tab="copy" role="tab"><i class="fa fa-clipboard"></i> نسخ التفاصيل</button>
            </nav>
            <section data-tab-pane="calendar">
                <div class="hs-set-row"><div><b>وقت البدء</b><small>أول ساعة تظهر في التقويم</small></div><select class="form-select" name="start_time">@for($h = 0; $h < 24; $h++)@php $v = sprintf('%02d:00', $h); @endphp<option value="{{ $v }}" {{ $settings['start_time'] === $v ? 'selected' : '' }}>{{ $v }}</option>@endfor</select></div>
                <div class="hs-set-row"><div><b>وقت الانتهاء</b><small>آخر ساعة تظهر في التقويم</small></div><select class="form-select" name="end_time">@for($h = 1; $h <= 24; $h++)@php $v = sprintf('%02d:00', $h); @endphp<option value="{{ $v }}" {{ $settings['end_time'] === $v ? 'selected' : '' }}>{{ $v }}</option>@endfor</select></div>
                <div class="hs-set-row"><div><b>ارتفاع الصف</b><small>بالبكسل لكل 15 دقيقة، بين 10 و30</small></div><input class="form-control" type="number" name="row_height" min="10" max="30" value="{{ $settings['row_height'] }}" required></div>
            </section>
            <section data-tab-pane="copy" hidden>
                <p class="form-text mb-3">اختر الحقول التي يتم تضمينها عند نسخ التفاصيل من بطاقة الزيارة، واسحب لتحديد ترتيبها. يُطبَّق هذا على الجميع في هذا الحساب.</p>
                <ul class="hs-copy-list" id="hs-copy-list"></ul>
            </section>
            <div class="alert alert-danger mt-3" id="hs-settings-error" role="alert" hidden></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary" id="hs-settings-save"><i class="fa fa-floppy-disk"></i> حفظ</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button></div>
    </form></div>
</div>
@endif
