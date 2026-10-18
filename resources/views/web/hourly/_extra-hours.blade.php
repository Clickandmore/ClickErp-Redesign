    @php
        $extraHoursConfig = ['csrf'=>csrf_token(), 'urls'=>[
            'extraHourQuote'=>route('web.hourly.visit.extra-hour.quote',['id'=>$order->id,'visit'=>'__VISIT__']),
            'extraHour'=>route('web.hourly.visit.extra-hour',['id'=>$order->id,'visit'=>'__VISIT__']),
        ]];
    @endphp
    <script type="application/json" id="hourly-extra-config">@json($extraHoursConfig)</script>
    <dialog id="hr-extra-dialog" class="hr-dialog" style="width:min(94vw,560px);max-height:90vh;overflow:auto;border:0;border-radius:16px;padding:0" dir="rtl" aria-labelledby="hr-extra-title">
        <form method="dialog" class="hr-card">
            <h2 id="hr-extra-title">طلب ساعات إضافية</h2>
            <p id="hr-extra-visit" class="hr-muted"></p>
            <label for="hr-extra-hours">عدد الساعات الإضافية</label>
            <select id="hr-extra-hours" class="form-select">@for($hours=1;$hours<=6;$hours++)<option value="{{ $hours }}">{{ $hours }}</option>@endfor</select>
            <p id="hr-extra-summary" class="hr-notice mt-3" aria-live="polite"></p>
            <p class="hr-muted">تُضاف التكلفة إلى طلبك وتُسدَّد حسب طريقة الدفع الخاصة به. يُعاد التحقق من التوفر عند التأكيد.</p>
            <p id="hr-extra-error" class="hr-error" role="alert" hidden></p>
            <div class="d-flex gap-2"><button type="button" id="hr-extra-confirm" class="hr-btn" disabled>تأكيد إضافة الساعات</button><button type="submit" class="hr-btn secondary">إغلاق</button></div>
        </form>
    </dialog>
@push('js')<script src="{{ asset('web/assets/js/hourly-extra-hours.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-extra-hours.js')) }}" defer></script>@endpush