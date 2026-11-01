@php
    $contactData = \App\Support\ContactNotificationSettings::all();
    if (in_array(old('section'), ['alerts', 'contact'], true)) $contactData = array_replace($contactData, old('contact', []));
@endphp
<section class="alerts-card" id="contact-notification-settings" style="scroll-margin-top:90px">
    <input type="hidden" name="contact[enabled]" value="0">
    <div class="alerts-card__header">
        <div class="alerts-card__heading">
            <span class="alerts-card__icon alerts-card__icon--teal"><i class="fa-solid fa-comments"></i></span>
            <div><h5>إشعارات الشكاوى وتواصل معنا</h5><p>إشعار الشركة تلقائيًا برسائل العملاء الواردة من الموقع.</p></div>
        </div>
        <div class="form-check form-switch alerts-switch m-0">
            <input class="form-check-input" type="checkbox" id="contactNotificationEnabled" name="contact[enabled]" value="1" @checked($contactData['enabled'])>
            <label class="form-check-label" for="contactNotificationEnabled">تفعيل الإرسال التلقائي</label>
        </div>
    </div>
    <div class="alerts-card__body">
        <p class="text-muted">يُرسل الإشعار إلى الرقم الموحد للشركة ببيانات العميل ونص الرسالة، باستخدام إعدادات ربط القناة المختارة.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="contactNotificationPhone">الرقم الموحد لاستقبال الإشعارات</label>
                <input class="form-control" id="contactNotificationPhone" type="tel" dir="ltr" name="contact[phone]" value="{{ $contactData['phone'] }}" placeholder="966..." maxlength="30">
                @error('phone')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="contactNotificationChannel">قناة الإرسال</label>
                <div class="channel-pills" id="contactNotificationChannel">
                    <input type="radio" class="btn-check" id="contactChannelWhatsapp" name="contact[channel]" value="whatsapp" @checked($contactData['channel'] === 'whatsapp')>
                    <label class="btn" for="contactChannelWhatsapp" data-channel="whatsapp"><i class="fa-brands fa-whatsapp"></i> واتساب</label>
                    <input type="radio" class="btn-check" id="contactChannelSms" name="contact[channel]" value="sms" @checked($contactData['channel'] === 'sms')>
                    <label class="btn" for="contactChannelSms" data-channel="sms"><i class="fa-solid fa-comment-sms"></i> رسالة نصية SMS</label>
                </div>
                @error('channel')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</section>
