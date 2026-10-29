@php
    $value = fn (string $key, $default = null) => data_get($step, $key, $default);
    // مرحلة فرعية قديمة مربوطة بمرحلة لا تقبل مراحل فرعية: الحفظ سيرفضها حتى تُنقل أو تُحذف
    $stageNotAllowed = filled($value('stage')) && !\App\Support\RecruitmentStagePolicy::allowsSteps((string) $value('stage'));
    $stageError = $errors->first("steps.$index.stage");
@endphp
<tr class="{{ $stageNotAllowed || $stageError ? 'table-danger' : '' }}">
    <td>
        <input type="hidden" name="steps[{{ $index }}][id]" value="{{ $value('id') }}" data-step-id>
        <input type="hidden" name="steps[{{ $index }}][_delete]" value="0" data-delete-step>
        <select name="steps[{{ $index }}][stage]" class="form-select form-select-sm {{ $stageNotAllowed || $stageError ? 'is-invalid' : '' }}" required>
            <option value="">اختر المرحلة</option>
            @foreach ($labels as $status => $locales)
                @if (!\App\Support\RecruitmentStagePolicy::allowsSteps($status) && $value('stage') !== $status)
                    @continue
                @endif
                <option value="{{ $status }}" {{ (string) $value('stage') === (string) $status ? 'selected' : '' }}>
                    {{ $locales['ar'] ?? $status }}
                </option>
            @endforeach
        </select>
        @if ($stageNotAllowed || $stageError)
            <div class="invalid-feedback d-block">{{ $stageError ?: 'هذه المرحلة لا تقبل مراحل فرعية. انقلها إلى مرحلة أخرى أو احذفها.' }}</div>
        @endif
    </td>
    <td>
        <input type="text" name="steps[{{ $index }}][name_ar]" class="form-control form-control-sm"
            value="{{ $value('name_ar') }}" maxlength="150" required placeholder="الاسم بالعربية">
        <input type="text" name="steps[{{ $index }}][name_en]" class="form-control form-control-sm rcs-step-en"
            value="{{ $value('name_en') }}" maxlength="150" placeholder="English name (optional)">
    </td>
    <td>
        <select name="steps[{{ $index }}][nationality_id]" class="form-select form-select-sm">
            <option value="">جميع الجنسيات</option>
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}" {{ (string) $value('nationality_id') === (string) $nationality->id ? 'selected' : '' }}>
                    {{ $nationality->display_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" name="steps[{{ $index }}][sort_order]" class="form-control form-control-sm"
            value="{{ $value('sort_order', 0) }}" min="0" max="9999">
    </td>
    <td>
        <div class="d-flex flex-column gap-1">
            <div class="form-check form-switch m-0">
                <input type="hidden" name="steps[{{ $index }}][is_active]" value="0">
                <input type="checkbox" name="steps[{{ $index }}][is_active]" value="1" class="form-check-input"
                    id="step_{{ $index }}_active" {{ (bool) $value('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label small" for="step_{{ $index }}_active">مفعلة</label>
            </div>
            <div class="form-check form-switch m-0">
                <input type="hidden" name="steps[{{ $index }}][is_required]" value="0">
                <input type="checkbox" name="steps[{{ $index }}][is_required]" value="1" class="form-check-input"
                    id="step_{{ $index }}_required" {{ $value('is_required', true) ? 'checked' : '' }}>
                <label class="form-check-label small" for="step_{{ $index }}_required">إلزامية</label>
            </div>
        </div>
    </td>
    <td>
        <div class="rcs-checks mb-1">
            @foreach (['sms' => 'SMS', 'email' => 'البريد'] as $channel => $label)
                <div class="form-check">
                    <input type="hidden" name="steps[{{ $index }}][notify_{{ $channel }}]" value="0">
                    <input type="checkbox" name="steps[{{ $index }}][notify_{{ $channel }}]" value="1" class="form-check-input"
                        id="step_{{ $index }}_{{ $channel }}" {{ $value('notify_'.$channel, false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="step_{{ $index }}_{{ $channel }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        <textarea name="steps[{{ $index }}][notification_template]" class="form-control form-control-sm" rows="2" maxlength="2000"
            placeholder="الافتراضي: {{ str_replace('{step_name}', ' — تم إنجاز '.($value('name_ar') ?: '{اسم المرحلة الفرعية}'), str_replace('{stage_name}', $labels[$value('stage')]['ar'] ?? '{stage_name}', \App\Support\RecruitmentStageNotificationSettings::TEMPLATE)) }}">{{ $value('notification_template') }}</textarea>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-link text-danger p-1" data-remove-step title="حذف المرحلة الفرعية" aria-label="حذف المرحلة الفرعية">
            <i class="fas fa-trash-alt"></i>
        </button>
    </td>
</tr>
