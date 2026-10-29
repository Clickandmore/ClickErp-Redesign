<div class="rcs-master d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <div class="form-check form-switch m-0">
            <input type="hidden" name="notifications[enabled]" value="0">
            <input type="checkbox" name="notifications[enabled]" value="1" class="form-check-input" id="notificationsEnabled"
                {{ old('notifications.enabled', $notificationSettings['enabled'] ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="notificationsEnabled">إرسال إشعارات تلقائية للعميل</label>
        </div>
        <div class="text-muted small mt-1" style="max-width: 640px;">
            تُرسل فور حفظ تحديث العقد، وإذا تعذر الإرسال لحظتها يعيد النظام المحاولة تلقائيًا خلال دقيقة.
            البريد والـ SMS يُرسلان كلٌّ على حدة حسب بيانات العميل المتوفرة،
            ولا يتكرر إرسال الحدث نفسه إذا رجع العقد لمرحلة سابقة ثم تقدم مجددًا.
            تفعيل SMS لمرحلة يُلغي خيار الإرسال اليدوي القديم لها.
        </div>
    </div>
</div>

@php
    // الرسالة الافتراضية لمرحلة: نفس ما يرسله RecruitmentStageNotifier عند ترك القالب فارغًا
    $stageDefaultTemplate = str_replace('{step_name}', '', \App\Support\RecruitmentStageNotificationSettings::TEMPLATE);
@endphp
<div class="rcs-vars small mb-3">
    <span class="text-muted me-1">المتغيرات المتاحة:</span>
    <code>{client_name}</code><code>{contract_number}</code><code>{stage_name}</code><code>{step_name}</code>
    <div class="text-muted mt-1">اترك نص الرسالة فارغًا لإرسال الرسالة الافتراضية الظاهرة تحت كل مرحلة.</div>
</div>

<div class="table-responsive border rounded" id="stageNotificationsTable">
    <table class="table rcs-table">
        <thead>
            <tr>
                <th style="min-width: 180px;">عند دخول المرحلة</th>
                <th class="text-center" style="width: 80px;">SMS</th>
                <th class="text-center" style="width: 80px;">البريد</th>
                <th>نص الرسالة <span class="fw-normal text-muted">(اختياري)</span></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($labels as $status => $locales)
            @if (!isset(\App\Support\RecruitmentStagePolicy::RANK[$status])) @continue @endif
            <tr>
                <td>{{ $locales['ar'] }}</td>
                @foreach (['sms', 'email'] as $channel)
                    <td class="text-center">
                        <input type="hidden" name="notifications[stages][{{ $status }}][{{ $channel }}]" value="0">
                        <input type="checkbox" class="form-check-input m-0" name="notifications[stages][{{ $status }}][{{ $channel }}]" value="1"
                            aria-label="{{ $locales['ar'] }} {{ $channel }}"
                            {{ old("notifications.stages.$status.$channel", data_get($notificationSettings, "stages.$status.$channel", false)) ? 'checked' : '' }}>
                    </td>
                @endforeach
                <td>
                    <textarea class="form-control form-control-sm" rows="1" maxlength="2000" placeholder="الرسالة الافتراضية"
                        name="notifications[stages][{{ $status }}][template]">{{ old("notifications.stages.$status.template", data_get($notificationSettings, "stages.$status.template")) }}</textarea>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 small">
                        <div class="rcs-default">
                            <span class="text-muted">الافتراضي:</span>
                            {{ str_replace('{stage_name}', $locales['ar'], $stageDefaultTemplate) }}
                        </div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" data-use-default="{{ $stageDefaultTemplate }}">تعديل عليه</button>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
