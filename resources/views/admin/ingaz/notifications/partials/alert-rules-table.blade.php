@php
    $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
    $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
    $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
    $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();
@endphp

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th style="min-width: 230px;">التنبيه</th>
                <th style="min-width: 90px;">مفعل</th>
                <th style="min-width: 220px;">قنوات الإرسال</th>
                <th style="min-width: 160px;">المستلم</th>
                <th style="min-width: 190px;">التنبيه قبل</th>
                <th style="min-width: 190px;">إعادة التذكير</th>
                <th style="min-width: 220px;">قالب الرسالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rules as $alertKey => $definition)
                @php
                    $rule = $settings[$alertKey] ?? [];
                    $selectedChannels = (array) data_get($rule, 'channels', ['notification']);
                    $rowIndex = $loop->index;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $definition['label'] }}</div>
                        <div class="text-muted small mb-1">{{ $definition['description'] ?? '' }}</div>
                        @if (!empty($definition['url']))
                            <a class="small" href="{{ $definition['url'] }}" target="_blank" rel="noopener">
                                فتح الصفحة المرتبطة
                            </a>
                        @endif
                    </td>
                    <td>
                        <input type="hidden" name="{{ $inputPrefix }}[{{ $alertKey }}][enabled]" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="{{ $inputPrefix }}[{{ $alertKey }}][enabled]" value="1"
                                id="{{ $inputPrefix }}Enabled{{ $rowIndex }}"
                                {{ data_get($rule, 'enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $inputPrefix }}Enabled{{ $rowIndex }}">تشغيل</label>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($alertChannelOptions as $channelKey => $channelLabel)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        name="{{ $inputPrefix }}[{{ $alertKey }}][channels][]"
                                        value="{{ $channelKey }}"
                                        id="{{ $inputPrefix }}{{ $rowIndex }}Channel{{ $loop->index }}"
                                        {{ in_array($channelKey, $selectedChannels, true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="{{ $inputPrefix }}{{ $rowIndex }}Channel{{ $loop->index }}">
                                        {{ $channelLabel }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <select name="{{ $inputPrefix }}[{{ $alertKey }}][recipient]" class="form-control">
                            @foreach ($alertRecipientOptions as $recipientKey => $recipientLabel)
                                <option value="{{ $recipientKey }}" {{ data_get($rule, 'recipient', 'admin') === $recipientKey ? 'selected' : '' }}>
                                    {{ $recipientLabel }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" min="0" max="3650" class="form-control"
                                name="{{ $inputPrefix }}[{{ $alertKey }}][lead_time_value]"
                                value="{{ data_get($rule, 'lead_time_value', $definition['default_lead_time_value'] ?? 1) }}">
                            <select name="{{ $inputPrefix }}[{{ $alertKey }}][lead_time_unit]" class="form-control">
                                @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                                    <option value="{{ $unitKey }}" {{ data_get($rule, 'lead_time_unit', $definition['default_lead_time_unit'] ?? 'days') === $unitKey ? 'selected' : '' }}>
                                        {{ $unitLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" min="0" max="3650" class="form-control"
                                name="{{ $inputPrefix }}[{{ $alertKey }}][repeat_every_value]"
                                value="{{ data_get($rule, 'repeat_every_value', 0) }}">
                            <select name="{{ $inputPrefix }}[{{ $alertKey }}][repeat_every_unit]" class="form-control">
                                @foreach ($alertUnitOptions as $unitKey => $unitLabel)
                                    <option value="{{ $unitKey }}" {{ data_get($rule, 'repeat_every_unit', 'days') === $unitKey ? 'selected' : '' }}>
                                        {{ $unitLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text">0 يعني بدون تكرار.</div>
                    </td>
                    <td>
                        <select name="{{ $inputPrefix }}[{{ $alertKey }}][template_id]" class="form-control">
                            <option value="">بدون قالب محدد</option>
                            @foreach ($alertTemplateOptions as $templateId => $templateLabel)
                                <option value="{{ $templateId }}" {{ (int) data_get($rule, 'template_id') === (int) $templateId ? 'selected' : '' }}>
                                    {{ $templateLabel }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
