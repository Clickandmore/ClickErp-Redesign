@php
    $checked = (bool) ($checked ?? false);
    $percentage = $percentage ?? '';
    $level = $level ?? '';
    $mode = $mode ?? ($level !== '' ? 'level' : 'percentage');
    $inactive = (bool) ($inactive ?? false);
@endphp

<div class="col-md-3">
    <div class="form-check">
        <input class="form-check-input lang-check" type="checkbox"
            name="languages[{{ $language }}]" id="{{ $language }}" value="{{ $language }}"
            {{ $checked ? 'checked' : '' }}>
        <label class="form-check-label" for="{{ $language }}">
            {{ $label }}
            @if ($inactive)
                <span class="badge bg-secondary ms-1">مخفية</span>
            @endif
        </label>
    </div>

    <div class="lang-extra mt-2 {{ $checked ? '' : 'd-none' }}">
        <input type="number" min="0" max="100"
            class="form-control language-percentage {{ $mode === 'percentage' ? '' : 'd-none' }}"
            name="percentage[{{ $language }}]" value="{{ $percentage }}"
            placeholder="{{ helperTrans('ingaz.Percentage') }}"
            {{ $checked && $mode === 'percentage' ? '' : 'disabled' }}>

        <select class="form-control language-level {{ $mode === 'level' ? '' : 'd-none' }}"
            name="language_levels[{{ $language }}]"
            {{ $checked && $mode === 'level' ? '' : 'disabled' }}>
            <option value="">{{ helperTrans('ingaz.choose') }}</option>
            @foreach (\App\Models\ingaz\Maid::LANGUAGE_LEVEL_PERCENTAGES as $levelValue => $mappedPercentage)
                <option value="{{ $levelValue }}" {{ $level === $levelValue ? 'selected' : '' }}>
                    {{ helperTrans('ingaz.language_level_' . $levelValue) }}
                </option>
            @endforeach
        </select>
    </div>
</div>
