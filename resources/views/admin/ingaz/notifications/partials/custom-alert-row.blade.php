@php
    $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
    $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
    $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
    $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();
    $selectedChannels = (array) data_get($rule, 'channels', ['notification']);

    $channelIcons = $channelIcons ?? [
        'sms' => 'fa-solid fa-comment-sms',
        'whatsapp' => 'fa-brands fa-whatsapp',
        'notification' => 'fa-solid fa-bell',
        'email' => 'fa-solid fa-envelope',
    ];

    $enabledId = 'customAlert' . $index . 'Enabled';
@endphp

<div class="custom-alert-card" data-custom-alert-row="{{ $index }}">
    <input type="hidden" name="custom_alerts[{{ $index }}][key]" value="{{ data_get($rule, 'key', 'custom_' . $index) }}">

    <div class="custom-alert-card__header">
        <div class="custom-alert-card__title">
            <span class="custom-alert-card__num" data-custom-alert-num>{{ $loop->iteration ?? ((int) $index + 1) }}</span>
            <span>{{ data_get($rule, 'label') ?: 'تنبيه مخصص' }}</span>
        </div>
        <div class="custom-alert-card__tools">
            <input type="hidden" name="custom_alerts[{{ $index }}][enabled]" value="0">
            <div class="form-check form-switch alerts-switch m-0">
                <input class="form-check-input" type="checkbox" name="custom_alerts[{{ $index }}][enabled]" value="1"
                    id="{{ $enabledId }}" {{ data_get($rule, 'enabled') ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $enabledId }}">مفعل</label>
            </div>
            <button type="button" class="btn-remove-alert js-remove-custom-alert">
                <i class="fa-solid fa-trash-can"></i>
                حذف
            </button>
        </div>
    </div>

    <div class="custom-alert-card__body">
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">اسم التنبيه</label>
                <input type="text" name="custom_alerts[{{ $index }}][label]" class="form-control"
                    value="{{ data_get($rule, 'label') }}" required>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label">كود الحدث</label>
                <input type="text" name="custom_alerts[{{ $index }}][event_key]" class="form-control"
                    value="{{ data_get($rule, 'event_key') }}" placeholder="مثال: custom_contract_review">
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label">رابط الصفحة</label>
                <input type="text" name="custom_alerts[{{ $index }}][source_url]" class="form-control"
                    value="{{ data_get($rule, 'source_url') }}" placeholder="/admin/...">
            </div>
            <div class="col-lg-8">
                <label class="form-label">الوصف</label>
                <input type="text" name="custom_alerts[{{ $index }}][description]" class="form-control"
                    value="{{ data_get($rule, 'description') }}">
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label">المستلم</label>
                <select name="custom_alerts[{{ $index }}][recipient]" class="form-select">
                    @foreach ($alertRecipientOptions as $recipientKey => $recipientLabel)
                        <option value="{{ $recipientKey }}" {{ data_get($rule, 'recipient', 'admin') === $recipientKey ? 'selected' : '' }}>
                            {{ $recipientLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label class="form-label">قنوات الإرسال</label>
                <div class="channel-pills">
                    @foreach ($alertChannelOptions as $channelKey => $channelLabel)
                        @php $channelId = 'customAlert' . $index . 'Channel' . $loop->index; @endphp
                        <input class="btn-check" type="checkbox" autocomplete="off"
                            name="custom_alerts[{{ $index }}][channels][]"
                            value="{{ $channelKey }}"
                            id="{{ $channelId }}"
                            {{ in_array($channelKey, $selectedChannels, true) ? 'checked' : '' }}>
                        <label class="btn" data-channel="{{ $channelKey }}" for="{{ $channelId }}">
                            <i class="{{ $channelIcons[$channelKey] ?? 'fa-solid fa-paper-plane' }}"></i>
                            {{ $channelLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">التنبيه قبل</label>
                <div class="input-group">
                    <input type="number" min="0" max="3650" class="form-control"
                        name="custom_alerts[{{ $index }}][lead_time_value]"
                        value="{{ data_get($rule, 'lead_time_value', 1) }}">
                    <select name="custom_alerts[{{ $index }}][lead_time_unit]" class="form-select">
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
                    <select name="custom_alerts[{{ $index }}][repeat_every_unit]" class="form-select">
                        @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                            <option value="{{ $unitKey }}" {{ data_get($rule, 'repeat_every_unit', 'days') === $unitKey ? 'selected' : '' }}>
                                {{ $unitLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-text">0 يعني بدون تكرار.</div>
            </div>
            <div class="col-lg-4">
                <label class="form-label">قالب الرسالة</label>
                <select name="custom_alerts[{{ $index }}][template_id]" class="form-select">
                    <option value="">بدون قالب محدد</option>
                    @foreach ($alertTemplateOptions as $templateId => $templateLabel)
                        <option value="{{ $templateId }}" {{ (int) data_get($rule, 'template_id') === (int) $templateId ? 'selected' : '' }}>
                            {{ $templateLabel }}
                        </option>
                    @endforeach
                </select>
                @error("custom_alerts.{$index}.template_id")
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
