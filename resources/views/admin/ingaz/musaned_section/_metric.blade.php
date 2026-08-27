<div class="metric-item border rounded p-3">
    @if (data_get($item, 'id'))
        <input type="hidden" name="{{ $groupName }}[{{ $index }}][id]" value="{{ data_get($item, 'id') }}">
    @endif
    <div class="d-flex align-items-center justify-content-between mb-3">
        <strong>{{ __('musaned_section.metric') }} <span class="metric-number">{{ is_numeric($index) ? $index + 1 : '' }}</span></strong>
        @if ($canEdit)
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary move-up" title="{{ __('musaned_section.move_up') }}">
                    <i class="fa fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary move-down" title="{{ __('musaned_section.move_down') }}">
                    <i class="fa fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-outline-danger remove-metric" title="{{ __('musaned_section.remove_metric') }}">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        @endif
    </div>

    <div class="row g-2">
        @foreach (config('translatable.locales') as $locale)
            <div class="col-md-6">
                <label class="form-label">{{ __('musaned_section.metric_title') }} ({{ strtoupper($locale) }})</label>
                <input type="text" class="form-control"
                    name="{{ $groupName }}[{{ $index }}][title][{{ $locale }}]"
                    value="{{ data_get($item, 'title.' . $locale) }}" maxlength="255" required @readonly(!$canEdit)>
            </div>
        @endforeach

        <div class="col-md-7">
            <label class="form-label">{{ $valueLabel }} ({{ $suffix }})</label>
            <input type="number" class="form-control" name="{{ $groupName }}[{{ $index }}][value]"
                value="{{ data_get($item, 'value', 0) }}" min="0" max="{{ $maxValue }}" step="0.01"
                required @readonly(!$canEdit)>
        </div>

        <div class="col-12">
            <label class="form-label">{{ __('musaned_section.metric_icon') }}</label>
            @if (data_get($item, 'icon'))
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="{{ showFile(data_get($item, 'icon')) }}" alt=""
                        style="width: 52px; height: 52px; object-fit: contain;" class="rounded border p-1">
                    @if ($canEdit)
                        <div class="form-check">
                            <input type="hidden" name="{{ $groupName }}[{{ $index }}][remove_icon]" value="0">
                            <input class="form-check-input" type="checkbox"
                                name="{{ $groupName }}[{{ $index }}][remove_icon]" value="1"
                                id="{{ $groupName }}-{{ $index }}-remove-icon"
                                {{ data_get($item, 'remove_icon') ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $groupName }}-{{ $index }}-remove-icon">
                                {{ __('musaned_section.remove_icon') }}
                            </label>
                        </div>
                    @endif
                </div>
            @else
                <input type="hidden" name="{{ $groupName }}[{{ $index }}][remove_icon]" value="0">
            @endif
            <input type="file" class="form-control" name="{{ $groupName }}[{{ $index }}][icon]"
                accept="image/jpeg,image/png,image/webp,image/svg+xml" @disabled(!$canEdit)>
            <small class="text-muted">{{ __('musaned_section.metric_icon_hint') }}</small>
        </div>

        <div class="col-md-5 d-flex align-items-end pb-2">
            <div class="form-check form-switch">
                <input type="hidden" name="{{ $groupName }}[{{ $index }}][is_visible]" value="0">
                <input class="form-check-input" type="checkbox"
                    name="{{ $groupName }}[{{ $index }}][is_visible]" value="1"
                    id="{{ $groupName }}-{{ $index }}-visible" @disabled(!$canEdit)
                    {{ data_get($item, 'is_visible', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $groupName }}-{{ $index }}-visible">
                    {{ __('musaned_section.show_metric') }}
                </label>
            </div>
        </div>
    </div>
</div>
