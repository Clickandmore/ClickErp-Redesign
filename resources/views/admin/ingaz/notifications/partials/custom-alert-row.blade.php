@php
    $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
    $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
    $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
    $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();
    $selectedChannels = (array) data_get($rule, 'channels', ['notification']);
@endphp

<div class="border rounded p-3 mb-3" data-custom-alert-row="{{ $index }}">
    <input type="hidden" name="custom_alerts[{{ $index }}][key]" value="{{ data_get($rule, 'key', 'custom_' . $index) }}">
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <label class="form-label">اسم التنبيه</label>
            <input type="text" name="custom_alerts[{{ $index }}][label]" class="form-control"
                value="{{ data_get($rule, 'label') }}" required>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label">كود الحدث</label>
            <input type="text" name="custom_alerts[{{ $index }}][event_key]" class="form-control"
                value="{{ data_get($rule, 'event_key') }}" placeholder="مثال: custom_contract_review">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label">رابط الصفحة</label>
            <input type="text" name="custom_alerts[{{ $index }}][source_url]" class="form-control"
                value="{{ data_get($rule, 'source_url') }}" placeholder="/admin/...">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">المستلم</label>
            <select name="custom_alerts[{{ $index }}][recipient]" class="form-control">
                @foreach ($alertRecipientOptions as $recipientKey => $recipientLabel)
                    <option value="{{ $recipientKey }}" {{ data_get($rule, 'recipient', 'admin') === $recipientKey ? 'selected' : '' }}>
                        {{ $recipientLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-6">
            <label class="form-label">مفعل</label>
            <input type="hidden" name="custom_alerts[{{ $index }}][enabled]" value="0">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="custom_alerts[{{ $index }}][enabled]" value="1"
                    {{ data_get($rule, 'enabled') ? 'checked' : '' }}>
            </div>
        </div>
        <div class="col-lg-4">
            <label class="form-label">الوصف</label>
            <input type="text" name="custom_alerts[{{ $index }}][description]" class="form-control"
                value="{{ data_get($rule, 'description') }}">
        </div>
        <div class="col-lg-4">
            <label class="form-label">قنوات الإرسال</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($alertChannelOptions as $channelKey => $channelLabel)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                            name="custom_alerts[{{ $index }}][channels][]"
                            value="{{ $channelKey }}"
                            id="customAlert{{ $index }}Channel{{ $loop->index }}"
                            {{ in_array($channelKey, $selectedChannels, true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="customAlert{{ $index }}Channel{{ $loop->index }}">
                            {{ $channelLabel }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">التنبيه قبل</label>
            <div class="input-group">
                <input type="number" min="0" max="3650" class="form-control"
                    name="custom_alerts[{{ $index }}][lead_time_value]"
                    value="{{ data_get($rule, 'lead_time_value', 1) }}">
                <select name="custom_alerts[{{ $index }}][lead_time_unit]" class="form-control">
                    @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                        <option value="{{ $unitKey }}" {{ data_get($rule, 'lead_time_unit', 'days') === $unitKey ? 'selected' : '' }}>
                            {{ $unitLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label">إعادة التذكير</label>
            <div class="input-group">
                <input type="number" min="0" max="3650" class="form-control"
                    name="custom_alerts[{{ $index }}][repeat_every_value]"
                    value="{{ data_get($rule, 'repeat_every_value', 0) }}">
                <select name="custom_alerts[{{ $index }}][repeat_every_unit]" class="form-control">
                    @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                        <option value="{{ $unitKey }}" {{ data_get($rule, 'repeat_every_unit', 'days') === $unitKey ? 'selected' : '' }}>
                            {{ $unitLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label class="form-label">قالب الرسالة</label>
            <select name="custom_alerts[{{ $index }}][template_id]" class="form-control">
                <option value="">بدون قالب محدد</option>
                @foreach ($alertTemplateOptions as $templateId => $templateLabel)
                    <option value="{{ $templateId }}" {{ (int) data_get($rule, 'template_id') === (int) $templateId ? 'selected' : '' }}>
                        {{ $templateLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-8 d-flex align-items-end justify-content-end">
            <button type="button" class="btn btn-outline-danger btn-sm js-remove-custom-alert">
                <i class="fa fa-trash"></i>
                حذف
            </button>
        </div>
    </div>
</div>
