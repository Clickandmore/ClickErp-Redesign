@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('musaned_section.page_title') }}
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
        $canEdit = @AdminGuardHasAnyPerm([\App\PermKeys::MUSANED_SECTION_EDIT, \App\PermKeys::MUSANED_SECTION_CONTROL]);
        $safeTextValue = static function (string $key, $storedValue = '') {
            $oldValue = old($key);

            if ($oldValue !== null && trim((string) $oldValue) !== '1') {
                return $oldValue;
            }

            return $storedValue;
        };

        $metricItems = static function (string $inputName, string $type) use ($section) {
            $oldItems = old($inputName);

            if (is_array($oldItems)) {
                $existingMetrics = $section ? $section->metrics->keyBy('id') : collect();

                return collect(array_values($oldItems))->map(function ($item) use ($existingMetrics) {
                    $metric = !empty($item['id']) ? $existingMetrics->get((int) $item['id']) : null;
                    $item['icon'] = $metric?->icon;

                    return $item;
                })->all();
            }

            return $section
                ? $section->metrics->where('type', $type)->values()->map(function ($metric) {
                    $titles = [];

                    foreach (config('translatable.locales') as $locale) {
                        $titles[$locale] = optional($metric->translate($locale))->title;
                    }

                    return [
                        'id' => $metric->id,
                        'title' => $titles,
                        'value' => $metric->value,
                        'icon' => $metric->icon,
                        'is_visible' => $metric->is_visible,
                    ];
                })->all()
                : [];
        };
    @endphp

    <form method="post" action="{{ route('musaned-section.update') }}" enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('musaned_section.content') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">{{ __('musaned_section.title') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" class="form-control" name="title:{{ $locale }}"
                                    value="{{ $safeTextValue('title:' . $locale, $section ? optional($section->translate($locale))->title : '') }}"
                                    maxlength="255" required @readonly(!$canEdit)>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('musaned_section.overall_performance') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" class="form-control" name="overall_performance:{{ $locale }}"
                                    value="{{ $safeTextValue('overall_performance:' . $locale, $section ? optional($section->translate($locale))->overall_performance : '') }}"
                                    maxlength="255" placeholder="{{ __('musaned_section.performance_example') }}"
                                    required @readonly(!$canEdit)>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('musaned_section.subtitle') }} ({{ strtoupper($locale) }})</label>
                                <textarea class="form-control" name="subtitle:{{ $locale }}" rows="3"
                                    maxlength="2000" required @readonly(!$canEdit)>{{ $safeTextValue('subtitle:' . $locale, $section ? optional($section->translate($locale))->subtitle : '') }}</textarea>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <label class="form-label">{{ __('musaned_section.profile_url') }}</label>
                            <input type="url" class="form-control" name="profile_url"
                                value="{{ old('profile_url', $section->profile_url ?? '') }}"
                                maxlength="2048" placeholder="https://..." required @readonly(!$canEdit)>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active"
                                    value="{{ $canEdit ? 0 : (old('is_active', $section->is_active ?? true) ? 1 : 0) }}">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="musaned-active" @disabled(!$canEdit)
                                    {{ old('is_active', $section->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="musaned-active">{{ __('musaned_section.active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ([
            ['name' => 'ratios', 'type' => 'ratio', 'title' => __('musaned_section.ratios'), 'value_label' => __('musaned_section.percentage_value'), 'max' => 100, 'suffix' => '%'],
            ['name' => 'star_ratings', 'type' => 'rating', 'title' => __('musaned_section.star_ratings'), 'value_label' => __('musaned_section.rating_value'), 'max' => 5, 'suffix' => '★'],
        ] as $group)
            @php($items = $metricItems($group['name'], $group['type']))
            <div class="col-lg-6">
                <div class="card h-100 metric-group" data-metric-group="{{ $group['name'] }}">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h5 class="mb-0">{{ $group['title'] }}</h5>
                        @if ($canEdit)
                            <button type="button" class="btn btn-sm btn-outline-primary add-metric">
                                <i class="fa fa-plus me-1"></i> {{ __('musaned_section.add_metric') }}
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="metric-list d-grid gap-3">
                            @foreach ($items as $index => $item)
                                @include('admin.ingaz.musaned_section._metric', [
                                    'groupName' => $group['name'],
                                    'index' => $index,
                                    'item' => $item,
                                    'valueLabel' => $group['value_label'],
                                    'maxValue' => $group['max'],
                                    'suffix' => $group['suffix'],
                                    'canEdit' => $canEdit,
                                ])
                            @endforeach
                        </div>

                        @if ($canEdit)
                            <div class="metric-empty text-center text-muted py-4 {{ count($items) ? 'd-none' : '' }}">
                                {{ __('musaned_section.no_metrics') }}
                            </div>
                            <template class="metric-template">
                                @include('admin.ingaz.musaned_section._metric', [
                                    'groupName' => $group['name'],
                                    'index' => '__INDEX__',
                                    'item' => ['id' => null, 'title' => [], 'value' => 0, 'icon' => null, 'is_visible' => true],
                                    'valueLabel' => $group['value_label'],
                                    'maxValue' => $group['max'],
                                    'suffix' => $group['suffix'],
                                    'canEdit' => true,
                                ])
                            </template>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @if ($canEdit)
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-5">{{ __('buttons.save') }}</button>
            </div>
        @endif
    </form>
@endsection

@if ($canEdit)
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-metric-group]').forEach(function (group) {
                    const list = group.querySelector('.metric-list');
                    const empty = group.querySelector('.metric-empty');
                    const template = group.querySelector('.metric-template');
                    const groupName = group.dataset.metricGroup;

                    const refresh = function () {
                        const rows = Array.from(list.querySelectorAll('.metric-item'));

                        rows.forEach(function (row, index) {
                            row.querySelectorAll('[name]').forEach(function (field) {
                                field.name = field.name.replace(
                                    new RegExp('^' + groupName + '\\[[^\\]]+\\]'),
                                    groupName + '[' + index + ']'
                                );
                            });
                            row.querySelector('.metric-number').textContent = index + 1;
                            const visibility = row.querySelector('.form-check-input');
                            const visibilityLabel = row.querySelector('.form-check-label');
                            visibility.id = groupName + '-' + index + '-visible';
                            visibilityLabel.htmlFor = visibility.id;
                            row.querySelector('.move-up').disabled = index === 0;
                            row.querySelector('.move-down').disabled = index === rows.length - 1;
                        });

                        empty.classList.toggle('d-none', rows.length > 0);
                    };

                    group.querySelector('.add-metric').addEventListener('click', function () {
                        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', list.children.length));
                        refresh();
                    });

                    list.addEventListener('click', function (event) {
                        const button = event.target.closest('button');
                        const row = event.target.closest('.metric-item');

                        if (!button || !row) return;

                        if (button.classList.contains('remove-metric')) row.remove();
                        if (button.classList.contains('move-up') && row.previousElementSibling) {
                            list.insertBefore(row, row.previousElementSibling);
                        }
                        if (button.classList.contains('move-down') && row.nextElementSibling) {
                            list.insertBefore(row.nextElementSibling, row);
                        }

                        refresh();
                    });

                    refresh();
                });
            });
        </script>
    @endpush
@endif
