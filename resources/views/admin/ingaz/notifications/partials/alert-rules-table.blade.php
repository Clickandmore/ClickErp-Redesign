@php
    $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
    $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
    $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
    $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();

    $channelIcons = $channelIcons ?? [
        'sms' => 'fa-solid fa-comment-sms',
        'whatsapp' => 'fa-brands fa-whatsapp',
        'notification' => 'fa-solid fa-bell',
        'email' => 'fa-solid fa-envelope',
    ];

    $ruleIcons = [
        'recruitment_orders' => 'fa-solid fa-file-signature',
        'recruitment_contracts_completed' => 'fa-solid fa-file-circle-check',
        'recruitment_warranty' => 'fa-solid fa-shield-halved',
        'rent_contract_warranty' => 'fa-solid fa-shield',
        'rent_contract_expiry' => 'fa-solid fa-calendar-xmark',
        'recruitment_travel_arrival' => 'fa-solid fa-plane-arrival',
        'deportation_travel' => 'fa-solid fa-plane-departure',
        'rent_trial_period' => 'fa-solid fa-hourglass-half',
    ];
@endphp

<div class="alert-rules-list">
    @foreach ($rules as $alertKey => $definition)
        @php
            $rule = $alertRuleSettings[$alertKey] ?? [];
            $selectedChannels = (array) data_get($rule, 'channels', ['notification']);
            $rowIndex = $loop->index;
            $isEnabled = (bool) data_get($rule, 'enabled');
            $fieldId = $inputPrefix . 'Rule' . $rowIndex;
        @endphp
        <div class="rule-card {{ $isEnabled ? '' : 'is-disabled' }}" data-rule-card="{{ $alertKey }}">
            <div class="rule-card__header">
                <div class="rule-cell">
                    <span class="rule-icon"><i class="{{ $ruleIcons[$alertKey] ?? 'fa-solid fa-bell' }}"></i></span>
                    <div>
                        <div class="rule-title">{{ $definition['label'] }}</div>
                        @if (!empty($definition['description']))
                            <div class="rule-desc">{{ $definition['description'] }}</div>
                        @endif
                        @if ($alertKey === 'recruitment_orders' || !empty($definition['url']))
                            <div class="rule-stats">
                                @if ($alertKey === 'recruitment_orders')
                                    <span class="rule-stat rule-stat--info">
                                        <i class="fa-solid fa-bullseye"></i>
                                        المطابقة الآن: {{ data_get($alertRuntimeStats ?? [], 'recruitment_orders.matched', 0) }}
                                    </span>
                                    <span class="rule-stat rule-stat--danger">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        المتأخرة: {{ data_get($alertRuntimeStats ?? [], 'recruitment_orders.overdue', 0) }}
                                    </span>
                                @endif
                                @if (!empty($definition['url']))
                                    <a class="rule-link" href="{{ $definition['url'] }}" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        فتح الصفحة المرتبطة
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="rule-card__toggle">
                    <input type="hidden" name="{{ $inputPrefix }}[{{ $alertKey }}][enabled]" value="0">
                    <div class="form-check form-switch alerts-switch m-0">
                        <input class="form-check-input js-rule-enabled" type="checkbox" name="{{ $inputPrefix }}[{{ $alertKey }}][enabled]" value="1"
                            id="{{ $inputPrefix }}Enabled{{ $rowIndex }}"
                            {{ $isEnabled ? 'checked' : '' }}>
                        <label class="form-check-label js-rule-enabled-label" for="{{ $inputPrefix }}Enabled{{ $rowIndex }}"
                            data-on="مفعل" data-off="متوقف">{{ $isEnabled ? 'مفعل' : 'متوقف' }}</label>
                    </div>
                </div>
            </div>

            <div class="rule-card__body">
                @if ($alertKey === 'rent_contract_expiry')
                    <div class="rule-field rule-field--full" data-renewal-payments>
                        <span class="rule-field__label">طرق الدفع المتاحة للعميل في التمديد</span>
                        <div class="channel-pills">
                            @foreach (\App\Support\NotificationAlertSettings::renewalPaymentMethodOptions() as $method => $label)
                                <input class="btn-check js-renewal-payment" type="checkbox" id="{{ $fieldId }}Payment{{ $method }}"
                                    name="{{ $inputPrefix }}[{{ $alertKey }}][renewal_payment_methods][]" value="{{ $method }}"
                                    @checked(in_array($method, (array) data_get($rule, 'renewal_payment_methods', []), true))>
                                <label class="btn" for="{{ $fieldId }}Payment{{ $method }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <small class="text-muted">اختر طريقة واحدة على الأقل لتفعيل إرفاق الرابط. التحويل البنكي والنقدي يتطلبان تأكيد الموظف للسداد وتنفيذ التمديد.</small>
                        @error("{$inputPrefix}.{$alertKey}.renewal_payment_methods")
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="rule-field rule-field--full">
                        <input type="hidden" name="{{ $inputPrefix }}[{{ $alertKey }}][include_renewal_link]" value="0">
                        <label class="rule-option" for="{{ $fieldId }}RenewalLink">
                            <span class="rule-option__icon"><i class="fa-solid fa-link"></i></span>
                            <span class="rule-option__text">
                                <span class="rule-option__title">إرفاق رابط التمديد / طلب تسليم العاملة</span>
                                <span class="rule-option__desc">
                                    يُضاف للعميل رابط صفحة يختار منها تمديد العقد وطريقة الدفع، أو طلب تسليم العاملة.
                                    <br>يسجل اختيار التسليم طلبًا للموظف، ولا يطبق التمديد إلا بعد تأكيد الدفع.
                                </span>
                            </span>
                            <span class="form-check form-switch alerts-switch m-0 rule-option__switch">
                                <input type="checkbox" class="form-check-input js-renewal-link" id="{{ $fieldId }}RenewalLink" role="switch"
                                    @disabled(empty(data_get($rule, 'renewal_payment_methods', [])))
                                    name="{{ $inputPrefix }}[{{ $alertKey }}][include_renewal_link]" value="1" @checked(data_get($rule, 'include_renewal_link', false))>
                            </span>
                        </label>
                    </div>
                @endif
                <div class="rule-field rule-field--channels">
                    <span class="rule-field__label"><i class="fa-solid fa-paper-plane"></i> قنوات الإرسال</span>
                    <div class="channel-pills">
                        @foreach ($alertChannelOptions as $channelKey => $channelLabel)
                            @php $channelId = $inputPrefix . $rowIndex . 'Channel' . $loop->index; @endphp
                            <input class="btn-check" type="checkbox" autocomplete="off"
                                name="{{ $inputPrefix }}[{{ $alertKey }}][channels][]"
                                value="{{ $channelKey }}"
                                id="{{ $channelId }}"
                                {{ in_array($channelKey, $selectedChannels, true) ? 'checked' : '' }}>
                            <label class="btn" data-channel="{{ $channelKey }}" for="{{ $channelId }}">
                                <i class="{{ $channelIcons[$channelKey] ?? 'fa-solid fa-paper-plane' }}"></i>
                                {{ $channelLabel }}
                            </label>
                        @endforeach
                    </div>
                    @error("{$inputPrefix}.{$alertKey}.channels")
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="rule-field">
                    <label class="rule-field__label" for="{{ $fieldId }}Recipient"><i class="fa-solid fa-user"></i> المستلم</label>
                    <select id="{{ $fieldId }}Recipient" name="{{ $inputPrefix }}[{{ $alertKey }}][recipient]" class="form-select">
                        @foreach ($alertRecipientOptions as $recipientKey => $recipientLabel)
                            <option value="{{ $recipientKey }}" {{ data_get($rule, 'recipient', 'admin') === $recipientKey ? 'selected' : '' }}>
                                {{ $recipientLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="rule-field">
                    <label class="rule-field__label" for="{{ $fieldId }}Lead"><i class="fa-regular fa-clock"></i> التنبيه قبل</label>
                    <div class="input-group">
                        <input type="number" min="0" max="3650" class="form-control" id="{{ $fieldId }}Lead"
                            name="{{ $inputPrefix }}[{{ $alertKey }}][lead_time_value]"
                            value="{{ data_get($rule, 'lead_time_value', $definition['default_lead_time_value'] ?? 1) }}">
                        <select name="{{ $inputPrefix }}[{{ $alertKey }}][lead_time_unit]" class="form-select" aria-label="وحدة التنبيه قبل">
                            @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                                <option value="{{ $unitKey }}" {{ data_get($rule, 'lead_time_unit', $definition['default_lead_time_unit'] ?? 'days') === $unitKey ? 'selected' : '' }}>
                                    {{ $unitLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="rule-field">
                    <label class="rule-field__label" for="{{ $fieldId }}Repeat"><i class="fa-solid fa-rotate"></i> إعادة التذكير كل</label>
                    <div class="input-group">
                        <input type="number" min="0" max="3650" class="form-control" id="{{ $fieldId }}Repeat"
                            name="{{ $inputPrefix }}[{{ $alertKey }}][repeat_every_value]"
                            value="{{ data_get($rule, 'repeat_every_value', 0) }}">
                        <select name="{{ $inputPrefix }}[{{ $alertKey }}][repeat_every_unit]" class="form-select" aria-label="وحدة إعادة التذكير">
                            @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                                <option value="{{ $unitKey }}" {{ data_get($rule, 'repeat_every_unit', 'days') === $unitKey ? 'selected' : '' }}>
                                    {{ $unitLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-text">0 يعني بدون تكرار.</div>
                </div>

                <div class="rule-field rule-field--template">
                    <label class="rule-field__label" for="{{ $fieldId }}Template"><i class="fa-regular fa-file-lines"></i> قالب الرسالة</label>
                    <select id="{{ $fieldId }}Template" name="{{ $inputPrefix }}[{{ $alertKey }}][template_id]" class="form-select">
                        <option value="">{{ $alertKey === 'rent_contract_expiry' ? 'النص الافتراضي لتنبيه انتهاء التأجير' : 'بدون قالب محدد' }}</option>
                        @foreach ($alertTemplateOptions as $templateId => $templateLabel)
                            <option value="{{ $templateId }}" {{ (int) data_get($rule, 'template_id') === (int) $templateId ? 'selected' : '' }}>
                                {{ $templateLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error("{$inputPrefix}.{$alertKey}.template_id")
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    @endforeach
</div>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-rule-card="rent_contract_expiry"]').forEach(function (card) {
        const toggle = card.querySelector('.js-renewal-link');
        const methods = card.querySelectorAll('.js-renewal-payment');
        if (!toggle) return;
        function sync() {
            toggle.disabled = !Array.from(methods).some(method => method.checked);
            if (toggle.disabled) toggle.checked = false;
        }
        methods.forEach(method => method.addEventListener('change', sync));
        sync();
    });
});
</script>
@endonce
