@if($socketSettings['decryption_failed'] ?? false)
    <div class="alert alert-warning" role="alert">تعذر فك تشفير إعدادات WebSocket المحفوظة بالمفتاح الحالي. أعد إدخال بيانات الاتصال والمفتاح السري ثم احفظها لإعادة تفعيل الاتصال. الإعدادات المحفوظة لم تُحذف.</div>
@endif
@php($socketSettings = $socketSettings ?? \App\Support\HourlyWebsocketSettings::forForm())
<div class="hr-card" id="sec-websocket">
    <div class="hr-head">
        <div class="hr-head-icon" style="background:#dcfce7;color:#15803d"><i class="fa fa-satellite-dish"></i></div>
        <div><h5>الاتصال المباشر للتتبع — WebSocket</h5><p>اضبط خدمة Pusher أو خادمك الخاص من هنا. لا تحتاج إلى تعديل ملف البيئة.</p></div>
    </div>
    <div class="hr-body">
        <input type="hidden" name="websocket[enabled]" value="0">
        <div class="hr-switch form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="ws-enabled" name="websocket[enabled]" value="1" {{ old('websocket.enabled', $socketSettings['enabled']) ? 'checked' : '' }}>
            <div>
                <label for="ws-enabled">تفعيل WebSocket للتتبع المباشر</label>
                <small>عند تعذر الاتصال يستمر تحديث صفحة GPS كل 5 ثوانٍ.</small>
            </div>
        </div>
        <div class="row g-3">
            @foreach(['app_id'=>'معرّف التطبيق (App ID)', 'key'=>'المفتاح العام (App Key)', 'cluster'=>'منطقة الخدمة (Cluster)', 'host'=>'عنوان الخادم الخاص (Host) — اختياري'] as $field=>$label)
            <div class="col-md-6">
                <label class="form-label" for="ws-{{ $field }}">{{ $label }}</label>
                <input class="form-control" id="ws-{{ $field }}" name="websocket[{{ $field }}]" dir="ltr" autocomplete="off" value="{{ old('websocket.'.$field, $socketSettings[$field]) }}">
            </div>
            @endforeach
            <div class="col-md-6">
                <label class="form-label" for="ws-secret">المفتاح السري (App Secret)</label>
                <input class="form-control" type="password" id="ws-secret" name="websocket[secret]" dir="ltr" autocomplete="new-password" value="">
                <small class="form-text">{{ $socketSettings['has_secret'] ? 'يوجد مفتاح محفوظ. اترك الحقل فارغًا للاحتفاظ به، أو أدخل مفتاحًا جديدًا لاستبداله.' : 'أدخل المفتاح السري لتفعيل الاتصال.' }} يُحفظ مشفّرًا ولا يُعرض بعد الحفظ.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ws-port">المنفذ (Port)</label>
                <input class="form-control" type="number" id="ws-port" name="websocket[port]" min="1" max="65535" value="{{ old('websocket.port', $socketSettings['port']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ws-scheme">نوع الاتصال</label>
                <select class="form-select" id="ws-scheme" name="websocket[scheme]">
                    <option value="https" {{ old('websocket.scheme', $socketSettings['scheme']) === 'https' ? 'selected' : '' }}>HTTPS / WSS</option>
                    <option value="http" {{ old('websocket.scheme', $socketSettings['scheme']) === 'http' ? 'selected' : '' }}>HTTP / WS — محلي</option>
                </select>
            </div>
        </div>
        @foreach($errors->getMessages() as $field=>$messages)
            @if(str_starts_with($field, 'websocket'))<div class="text-danger mt-2">{{ implode(' ', $messages) }}</div>@endif
        @endforeach
        <div class="hr-note hr-note-info mt-3"><i class="fa fa-circle-info mt-1"></i><div>لخدمة Pusher اترك عنوان الخادم فارغًا واستخدم HTTPS والمنفذ 443. للخادم الخاص أدخل اسم المضيف فقط دون https:// أو مسار. بعد الحفظ أعد تحميل صفحة GPS.</div></div>
    </div>
</div>
