{{-- تنبيه المسؤول عند وصول حجز من الموقع الخارجي (كان سابقاً داخل إعدادات ربط SMS). --}}
@php
    $externalOrderData = array_replace(
        ['enabled' => false, 'recipient_mode' => 'selected_marketer', 'unified_phone' => ''],
        (array) ($externalOrderAlert ?? [])
    );
    if (old('section') === 'alerts' && is_array(old('external_order_alert'))) {
        $externalOrderData = array_replace($externalOrderData, old('external_order_alert'));
    }
@endphp
<section class="alerts-card" id="external-order-alert-settings" style="scroll-margin-top:90px">
    <input type="hidden" name="external_order_alert[enabled]" value="0">
    <div class="alerts-card__header">
        <div class="alerts-card__heading">
            <span class="alerts-card__icon alerts-card__icon--teal"><i class="fa-solid fa-globe"></i></span>
            <div>
                <h5>تنبيه المسؤول عند وصول حجز من الموقع الخارجي</h5>
                <p>رسالة SMS بعد إنشاء الطلب بنجاح. لا تُرسل إذا كانت بوابة الرسائل النصية متوقفة في «إعدادات الربط».</p>
            </div>
        </div>
        <div class="form-check form-switch alerts-switch m-0">
            <input class="form-check-input" type="checkbox" id="externalOrderAdminNotificationEnabled"
                name="external_order_alert[enabled]" value="1" @checked(filter_var($externalOrderData['enabled'], FILTER_VALIDATE_BOOLEAN))>
            <label class="form-check-label" for="externalOrderAdminNotificationEnabled">تفعيل التنبيه</label>
        </div>
    </div>
    <div class="alerts-card__body">
        <div class="row g-3" id="externalOrderAdminNotificationFields">
            <div class="col-md-6">
                <label class="form-label" for="externalOrderAdminRecipientMode">إرسال التنبيه إلى</label>
                <select class="form-control" name="external_order_alert[recipient_mode]" id="externalOrderAdminRecipientMode">
                    <option value="selected_marketer" @selected($externalOrderData['recipient_mode'] !== 'unified')>المسوق الذي اختاره العميل من الموقع</option>
                    <option value="unified" @selected($externalOrderData['recipient_mode'] === 'unified')>رقم مسؤول موحد</option>
                </select>
                <div class="form-text">عند اختيار المسوق، يستخدم النظام رقم جواله المسجل. إذا لم يكن له رقم صالح فلن يرسل التنبيه.</div>
            </div>
            <div class="col-md-6" id="externalOrderUnifiedPhoneWrapper">
                <label class="form-label" for="externalOrderUnifiedPhone">رقم المسؤول الموحد</label>
                <input type="text" class="form-control" dir="ltr" name="external_order_alert[unified_phone]"
                    id="externalOrderUnifiedPhone" value="{{ $externalOrderData['unified_phone'] }}" placeholder="9665XXXXXXXX">
                <div class="form-text">الصيغ المقبولة: <span dir="ltr">05XXXXXXXX</span> أو <span dir="ltr">5XXXXXXXX</span> أو <span dir="ltr">9665XXXXXXXX</span> أو <span dir="ltr">+9665XXXXXXXX</span>.</div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var enabled = document.getElementById('externalOrderAdminNotificationEnabled');
        var mode = document.getElementById('externalOrderAdminRecipientMode');
        var phone = document.getElementById('externalOrderUnifiedPhone');
        var toggle = function () {
            var unified = mode.value === 'unified';
            document.getElementById('externalOrderAdminNotificationFields').classList.toggle('opacity-50', !enabled.checked);
            document.getElementById('externalOrderUnifiedPhoneWrapper').classList.toggle('d-none', !unified);
            phone.required = enabled.checked && unified;
        };
        enabled.addEventListener('change', toggle);
        mode.addEventListener('change', toggle);
        toggle();
    });
</script>
