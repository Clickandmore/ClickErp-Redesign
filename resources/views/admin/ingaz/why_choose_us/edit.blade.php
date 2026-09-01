@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('why_choose_us.page_title') }}
@endsection

@section('style')
    <style>
        .why-choose-image {
            width: 150px;
            height: 110px;
            object-fit: cover;
            border-radius: 12px;
        }

        .why-icon-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(62px, 1fr));
            gap: 8px;
            max-height: 230px;
            overflow-y: auto;
        }

        .why-icon-option {
            border: 1px dashed var(--border-color, #ddd);
            background: var(--card-color, #fff);
            border-radius: 10px;
            padding: 14px 8px;
            font-size: 20px;
            cursor: pointer;
        }

        .why-icon-option:hover,
        .why-icon-option.is-selected {
            color: #fff;
            background: #00ac9a;
        }

        .feature-icon-preview {
            min-width: 44px;
            font-size: 20px;
        }
    </style>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $featureRows = $section && $section->features->isNotEmpty()
            ? $section->features
            : collect([(object) ['id' => null, 'icon' => 'fa fa-check-circle', 'sort_order' => 1]]);
        $highlightRows = $section ? $section->highlights : collect();
        $canEditWhyChooseUs = @AdminGuardHasAnyPerm([\App\PermKeys::WHY_CHOOSE_US_EDIT, \App\PermKeys::WHY_CHOOSE_US_CONTROL]);
        $canCreateWhyChooseUs = @AdminGuardHasAnyPerm([\App\PermKeys::WHY_CHOOSE_US_CREATE, \App\PermKeys::WHY_CHOOSE_US_CONTROL]);
        $canDeleteWhyChooseUs = @AdminGuardHasAnyPerm([\App\PermKeys::WHY_CHOOSE_US_DELETE, \App\PermKeys::WHY_CHOOSE_US_CONTROL]);
    @endphp

    <form method="post" action="{{ route('why-choose-us.update') }}"
        enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('why_choose_us.main_content') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">
                                    {{ helperTrans('ingaz.title') }}
                                    ({{ helperTrans('ingaz.' . $locale) }})
                                </label>
                                <input type="text" name="title:{{ $locale }}" class="form-control"
                                    value="{{ old('title:' . $locale, $section ? optional($section->translate($locale))->title : '') }}"
                                    required {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                            </div>
                        @endforeach

                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">
                                    {{ helperTrans('ingaz.description') }}
                                    ({{ helperTrans('ingaz.' . $locale) }})
                                </label>
                                <textarea name="description:{{ $locale }}" class="form-control"
                                    rows="5" maxlength="3000" required {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>{{ old('description:' . $locale, $section ? optional($section->translate($locale))->description : '') }}</textarea>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <small class="text-muted">{{ __('why_choose_us.title_hint') }}</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('why_choose_us.cta_label_ar') }}</label>
                            <input type="text" name="cta_label:ar" class="form-control" maxlength="120"
                                value="{{ old('cta_label:ar', $section ? optional($section->translate('ar'))->cta_label : '') }}"
                                {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('why_choose_us.cta_label_en') }}</label>
                            <input type="text" name="cta_label:en" class="form-control" maxlength="120"
                                value="{{ old('cta_label:en', $section ? optional($section->translate('en'))->cta_label : '') }}"
                                {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('why_choose_us.cta_url') }}</label>
                            <input type="text" name="cta_url" class="form-control" maxlength="255"
                                value="{{ old('cta_url', $section->cta_url ?? '') }}"
                                placeholder="/professional-workers"
                                {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                            <small class="text-muted">{{ __('why_choose_us.cta_url_hint') }}</small>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active"
                                    value="{{ $canEditWhyChooseUs ? 0 : (old('is_active', $section ? $section->is_active : true) ? 1 : 0) }}">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                    value="1" id="why-choose-active"
                                    {{ !$canEditWhyChooseUs ? 'disabled' : '' }}
                                    {{ old('is_active', $section ? $section->is_active : true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="why-choose-active">
                                    {{ __('why_choose_us.active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('why_choose_us.images') }}</h5>
                </div>
                <div class="card-body">
                    <input type="file" name="images[]" class="form-control"
                        accept=".jpg,.jpeg,.png,.webp" multiple {{ !$canCreateWhyChooseUs ? 'disabled' : '' }}>
                    <small class="text-muted">{{ __('why_choose_us.images_hint') }}</small>

                    @if ($section && $section->images->isNotEmpty())
                        <h6 class="mt-4">{{ __('why_choose_us.existing_images') }}</h6>
                        <div class="d-flex flex-wrap gap-3 mt-3">
                            @foreach ($section->images as $image)
                                <label class="border rounded p-2 text-center">
                                    <img src="{{ $image->url }}" class="why-choose-image d-block mb-2"
                                        alt="">
                                    <input type="checkbox" name="delete_image_ids[]"
                                        value="{{ $image->id }}" {{ !$canDeleteWhyChooseUs ? 'disabled' : '' }}>
                                    {{ __('why_choose_us.delete_image') }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('why_choose_us.sections') }}</h5>
                    @if ($canCreateWhyChooseUs)
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            id="add-why-feature">
                            <i class="fa fa-plus"></i>
                            {{ __('why_choose_us.add_section') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div id="why-feature-rows">
                        @foreach ($featureRows as $index => $feature)
                            <div class="why-feature-row border rounded p-3 mb-3">
                                <input type="hidden" name="features[{{ $index }}][id]"
                                    value="{{ $feature->id ?? '' }}">
                                <input type="hidden" class="feature-icon-input"
                                    name="features[{{ $index }}][icon]"
                                    value="{{ $feature->icon ?? 'fa fa-check-circle' }}">

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('why_choose_us.section_title_ar') }}</label>
                                        <input type="text" name="features[{{ $index }}][ar][title]"
                                            class="form-control" maxlength="255"
                                            value="{{ $feature->id ? optional($feature->translate('ar'))->title : '' }}"
                                            required {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('why_choose_us.section_title_en') }}</label>
                                        <input type="text" name="features[{{ $index }}][en][title]"
                                            class="form-control" maxlength="255"
                                            value="{{ $feature->id ? optional($feature->translate('en'))->title : '' }}"
                                            required {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('why_choose_us.order') }}</label>
                                        <input type="number" name="features[{{ $index }}][sort_order]"
                                            class="form-control" min="1" max="9999"
                                            value="{{ $feature->sort_order ?? ($index + 1) }}" required
                                            {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('why_choose_us.section_description_ar') }}</label>
                                        <textarea name="features[{{ $index }}][ar][description]"
                                            class="form-control" rows="3" maxlength="1000"
                                            required {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'readonly' : '' }}>{{ $feature->id ? optional($feature->translate('ar'))->description : '' }}</textarea>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('why_choose_us.section_description_en') }}</label>
                                        <textarea name="features[{{ $index }}][en][description]"
                                            class="form-control" rows="3" maxlength="1000"
                                            required {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'readonly' : '' }}>{{ $feature->id ? optional($feature->translate('en'))->description : '' }}</textarea>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="button"
                                            class="btn btn-outline-primary choose-feature-icon"
                                            data-bs-toggle="modal" data-bs-target="#whyIconModal"
                                            {{ ($feature->id && !$canEditWhyChooseUs) || (!$feature->id && !$canCreateWhyChooseUs) ? 'disabled' : '' }}>
                                            <i class="{{ $feature->icon ?? 'fa fa-check-circle' }} feature-icon-preview"></i>
                                            {{ __('why_choose_us.choose_icon') }}
                                        </button>
                                        @if ((!$feature->id && $canCreateWhyChooseUs) || ($feature->id && $canDeleteWhyChooseUs))
                                            <button type="button"
                                                class="btn btn-outline-danger remove-why-feature"
                                                title="{{ __('why_choose_us.remove_section') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('why_choose_us.highlights') }}</h5>
                    @if ($canCreateWhyChooseUs)
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-why-highlight">
                            <i class="fa fa-plus"></i>
                            {{ __('why_choose_us.add_highlight') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <small class="text-muted d-block mb-3">{{ __('why_choose_us.highlights_hint') }}</small>

                    <div id="why-highlight-rows">
                        @foreach ($highlightRows as $index => $highlight)
                            <div class="why-highlight-row border rounded p-3 mb-3">
                                <input type="hidden" name="highlights[{{ $index }}][id]"
                                    value="{{ $highlight->id }}">
                                <input type="hidden" class="highlight-icon-input"
                                    name="highlights[{{ $index }}][icon]"
                                    value="{{ $highlight->icon }}">

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('why_choose_us.highlight_title_ar') }}</label>
                                        <input type="text" name="highlights[{{ $index }}][ar][title]"
                                            class="form-control" maxlength="255"
                                            value="{{ optional($highlight->translate('ar'))->title }}"
                                            required {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('why_choose_us.highlight_title_en') }}</label>
                                        <input type="text" name="highlights[{{ $index }}][en][title]"
                                            class="form-control" maxlength="255"
                                            value="{{ optional($highlight->translate('en'))->title }}"
                                            required {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('why_choose_us.order') }}</label>
                                        <input type="number" name="highlights[{{ $index }}][sort_order]"
                                            class="form-control" min="1" max="9999"
                                            value="{{ $highlight->sort_order ?: $index + 1 }}" required
                                            {{ !$canEditWhyChooseUs ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-outline-primary choose-highlight-icon"
                                            data-bs-toggle="modal" data-bs-target="#whyIconModal"
                                            {{ !$canEditWhyChooseUs ? 'disabled' : '' }}>
                                            <i class="{{ $highlight->icon }} feature-icon-preview"></i>
                                        </button>
                                        @if ($canDeleteWhyChooseUs)
                                            <button type="button" class="btn btn-outline-danger remove-why-highlight"
                                                title="{{ __('why_choose_us.remove_highlight') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($canEditWhyChooseUs || $canCreateWhyChooseUs || $canDeleteWhyChooseUs)
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-5">
                    {{ __('buttons.save') }}
                </button>
            </div>
        @endif
    </form>

    <template id="why-feature-template">
        <div class="why-feature-row border rounded p-3 mb-3">
            <input type="hidden" class="feature-icon-input"
                name="features[__INDEX__][icon]" value="fa fa-check-circle">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">{{ __('why_choose_us.section_title_ar') }}</label>
                    <input type="text" name="features[__INDEX__][ar][title]"
                        class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('why_choose_us.section_title_en') }}</label>
                    <input type="text" name="features[__INDEX__][en][title]"
                        class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('why_choose_us.order') }}</label>
                    <input type="number" name="features[__INDEX__][sort_order]"
                        class="form-control" min="1" max="9999" value="__ORDER__" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('why_choose_us.section_description_ar') }}</label>
                    <textarea name="features[__INDEX__][ar][description]"
                        class="form-control" rows="3" maxlength="1000" required></textarea>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('why_choose_us.section_description_en') }}</label>
                    <textarea name="features[__INDEX__][en][description]"
                        class="form-control" rows="3" maxlength="1000" required></textarea>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-outline-primary choose-feature-icon"
                        data-bs-toggle="modal" data-bs-target="#whyIconModal">
                        <i class="fa fa-check-circle feature-icon-preview"></i>
                        {{ __('why_choose_us.choose_icon') }}
                    </button>
                    <button type="button" class="btn btn-outline-danger remove-why-feature">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template id="why-highlight-template">
        <div class="why-highlight-row border rounded p-3 mb-3">
            <input type="hidden" class="highlight-icon-input"
                name="highlights[__INDEX__][icon]" value="fa fa-check-circle">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('why_choose_us.highlight_title_ar') }}</label>
                    <input type="text" name="highlights[__INDEX__][ar][title]"
                        class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('why_choose_us.highlight_title_en') }}</label>
                    <input type="text" name="highlights[__INDEX__][en][title]"
                        class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('why_choose_us.order') }}</label>
                    <input type="number" name="highlights[__INDEX__][sort_order]"
                        class="form-control" min="1" max="9999" value="__ORDER__" required>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-outline-primary choose-highlight-icon"
                        data-bs-toggle="modal" data-bs-target="#whyIconModal">
                        <i class="fa fa-check-circle feature-icon-preview"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger remove-why-highlight">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <div class="modal fade" id="whyIconModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('why_choose_us.icon_list') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="why-icon-options">
                        @foreach ($icons as $icon)
                            <button type="button" class="why-icon-option"
                                data-icon="{{ $icon }}" data-bs-dismiss="modal">
                                <i class="{{ $icon }}"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let activeRow = null;

            // Both repeaters behave identically; only their selectors differ.
            const lists = [
                {
                    rowClass: 'why-feature-row',
                    container: document.getElementById('why-feature-rows'),
                    template: document.getElementById('why-feature-template'),
                    addButton: document.getElementById('add-why-feature'),
                    removeClass: 'remove-why-feature',
                    iconClass: 'choose-feature-icon',
                    keepOne: true,
                },
                {
                    rowClass: 'why-highlight-row',
                    container: document.getElementById('why-highlight-rows'),
                    template: document.getElementById('why-highlight-template'),
                    addButton: document.getElementById('add-why-highlight'),
                    removeClass: 'remove-why-highlight',
                    iconClass: 'choose-highlight-icon',
                    keepOne: false,
                },
            ];

            lists.forEach(list => {
                if (!list.container || !list.template) {
                    return;
                }

                let nextIndex = list.container.querySelectorAll('.' + list.rowClass).length;

                if (list.addButton) {
                    list.addButton.addEventListener('click', () => {
                        const order = list.container.querySelectorAll('.' + list.rowClass).length + 1;
                        list.container.insertAdjacentHTML('beforeend', list.template.innerHTML
                            .replaceAll('__INDEX__', nextIndex)
                            .replaceAll('__ORDER__', order));
                        nextIndex++;
                    });
                }

                list.container.addEventListener('click', event => {
                    const iconButton = event.target.closest('.' + list.iconClass);
                    if (iconButton) {
                        activeRow = iconButton.closest('.' + list.rowClass);
                        return;
                    }

                    const removeButton = event.target.closest('.' + list.removeClass);
                    if (!removeButton) {
                        return;
                    }

                    const rows = list.container.querySelectorAll('.' + list.rowClass);
                    if (!list.keepOne || rows.length > 1) {
                        removeButton.closest('.' + list.rowClass).remove();
                    }
                });
            });

            document.querySelectorAll('.why-icon-option').forEach(option => {
                option.addEventListener('click', () => {
                    if (!activeRow) {
                        return;
                    }

                    const icon = option.dataset.icon;
                    const input = activeRow.querySelector('.feature-icon-input, .highlight-icon-input');
                    const preview = activeRow.querySelector('.feature-icon-preview');

                    if (input) {
                        input.value = icon;
                    }

                    if (preview) {
                        preview.className = icon + ' feature-icon-preview';
                    }
                });
            });
        });
    </script>
@endsection
