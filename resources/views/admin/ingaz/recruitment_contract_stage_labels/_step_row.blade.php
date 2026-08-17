@php
    $value = fn (string $key, $default = null) => data_get($step, $key, $default);
@endphp
<tr>
    <td>
        <input type="hidden" name="steps[{{ $index }}][id]" value="{{ $value('id') }}" data-step-id>
        <input type="hidden" name="steps[{{ $index }}][_delete]" value="0" data-delete-step>
        <select name="steps[{{ $index }}][stage]" class="form-select" required>
            <option value="">اختر المرحلة</option>
            @foreach ($labels as $status => $locales)
                <option value="{{ $status }}" @selected($value('stage') === $status)>
                    {{ $locales['ar'] ?? $status }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" name="steps[{{ $index }}][name_ar]" class="form-control"
            value="{{ $value('name_ar') }}" maxlength="150" required>
    </td>
    <td>
        <input type="text" name="steps[{{ $index }}][name_en]" class="form-control"
            value="{{ $value('name_en') }}" maxlength="150">
    </td>
    <td>
        <select name="steps[{{ $index }}][nationality_id]" class="form-select">
            <option value="">جميع الجنسيات</option>
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}" @selected((string) $value('nationality_id') === (string) $nationality->id)>
                    {{ $nationality->display_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" name="steps[{{ $index }}][sort_order]" class="form-control"
            value="{{ $value('sort_order', 0) }}" min="0" max="9999">
    </td>
    <td class="text-center">
        <input type="hidden" name="steps[{{ $index }}][is_active]" value="0">
        <input type="checkbox" name="steps[{{ $index }}][is_active]" value="1"
            class="form-check-input" @checked((bool) $value('is_active', true))>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-step title="حذف الخطوة">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
