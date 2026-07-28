@php
    $editing = isset($obj) && $obj;
    $pointRows = $editing && $obj->items->isNotEmpty()
        ? $obj->items
        : collect([(object) ['id' => null, 'sort_order' => 1]]);
@endphp

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.title') }} ({{ helperTrans('ingaz.ar') }})</label>
    <input type="text" name="title:ar" class="form-control" data-validation="required"
        value="{{ old('title:ar', $editing ? optional($obj->translate('ar'))->title : '') }}">
</div>

<div class="col-md-6">
    <label class="form-label">{{ helperTrans('ingaz.title') }} ({{ helperTrans('ingaz.en') }})</label>
    <input type="text" name="title:en" class="form-control" data-validation="required"
        value="{{ old('title:en', $editing ? optional($obj->translate('en'))->title : '') }}">
</div>

<div class="col-md-5">
    <label class="form-label">{{ helperTrans('ingaz.requirement_type') }}</label>
    <select name="type" class="form-control" data-validation="required">
        @foreach ($enums as $enum)
            <option value="{{ $enum->value }}"
                {{ old('type', $editing ? $obj->type : '') === $enum->value ? 'selected' : '' }}>
                {{ helperTrans('ingaz.' . $enum->name) }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-4">
    <label class="form-label">{{ __('recruitment_requirements.sort_order') }}</label>
    <input type="number" name="sort_order" class="form-control" min="1" max="9999"
        value="{{ old('sort_order', $editing ? $obj->sort_order : $nextSortOrder) }}"
        data-validation="required">
</div>

<div class="col-md-3 d-flex align-items-end">
    <div class="form-check form-switch mb-2">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" name="is_active" value="1"
            id="requirement-active"
            {{ old('is_active', $editing ? $obj->is_active : true) ? 'checked' : '' }}>
        <label class="form-check-label" for="requirement-active">
            {{ __('recruitment_requirements.active') }}
        </label>
    </div>
</div>

<div class="col-12 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ __('recruitment_requirements.points') }}</h5>
        <button type="button" class="btn btn-sm btn-outline-primary"
            id="add-requirement-point">
            <i class="fa fa-plus"></i>
            {{ __('recruitment_requirements.add_point') }}
        </button>
    </div>

    <div id="requirement-points">
        @foreach ($pointRows as $index => $point)
            <div class="requirement-point-row border rounded p-3 mb-3"
                data-index="{{ $index }}">
                <input type="hidden" name="points[{{ $index }}][id]"
                    value="{{ $point->id ?? '' }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">
                            {{ __('recruitment_requirements.point_ar') }}
                        </label>
                        <input type="text" name="points[{{ $index }}][ar][text]"
                            class="form-control" maxlength="500" data-validation="required"
                            value="{{ $editing && $point->id ? optional($point->translate('ar'))->text : '' }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">
                            {{ __('recruitment_requirements.point_en') }}
                        </label>
                        <input type="text" name="points[{{ $index }}][en][text]"
                            class="form-control" maxlength="500" data-validation="required"
                            value="{{ $editing && $point->id ? optional($point->translate('en'))->text : '' }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">
                            {{ __('recruitment_requirements.point_order') }}
                        </label>
                        <input type="number" name="points[{{ $index }}][sort_order]"
                            class="form-control" min="1" max="9999"
                            value="{{ $point->sort_order ?? ($index + 1) }}"
                            data-validation="required">
                    </div>
                    <div class="col-md-1">
                        <button type="button"
                            class="btn btn-outline-danger w-100 remove-requirement-point"
                            title="{{ __('recruitment_requirements.remove_point') }}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<template id="requirement-point-template">
    <div class="requirement-point-row border rounded p-3 mb-3" data-index="__INDEX__">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">{{ __('recruitment_requirements.point_ar') }}</label>
                <input type="text" name="points[__INDEX__][ar][text]"
                    class="form-control" maxlength="500" data-validation="required">
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('recruitment_requirements.point_en') }}</label>
                <input type="text" name="points[__INDEX__][en][text]"
                    class="form-control" maxlength="500" data-validation="required">
            </div>
            <div class="col-md-1">
                <label class="form-label">{{ __('recruitment_requirements.point_order') }}</label>
                <input type="number" name="points[__INDEX__][sort_order]"
                    class="form-control" min="1" max="9999" value="__ORDER__"
                    data-validation="required">
            </div>
            <div class="col-md-1">
                <button type="button"
                    class="btn btn-outline-danger w-100 remove-requirement-point"
                    title="{{ __('recruitment_requirements.remove_point') }}">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    (() => {
        const container = document.getElementById('requirement-points');
        const template = document.getElementById('requirement-point-template');
        const addButton = document.getElementById('add-requirement-point');

        if (!container || !template || !addButton) {
            return;
        }

        let nextIndex = container.querySelectorAll('.requirement-point-row').length;

        addButton.addEventListener('click', () => {
            const order = container.querySelectorAll('.requirement-point-row').length + 1;
            const html = template.innerHTML
                .replaceAll('__INDEX__', nextIndex)
                .replaceAll('__ORDER__', order);

            container.insertAdjacentHTML('beforeend', html);
            nextIndex++;
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-requirement-point');
            if (!button) {
                return;
            }

            const rows = container.querySelectorAll('.requirement-point-row');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input[type="text"]')
                    .forEach(input => input.value = '');
                return;
            }

            button.closest('.requirement-point-row').remove();
        });
    })();
</script>
