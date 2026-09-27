@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('home_sections.page_title') }}
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
        $canEdit = @AdminGuardHasAnyPerm([\App\PermKeys::HOME_SECTIONS_EDIT, \App\PermKeys::HOME_SECTIONS_CONTROL]);
        $isConfigured = $availableKeys !== null;
    @endphp

    <div class="row g-4">
        {{-- تنويه دائم لكل المستخدمين --}}
        <div class="col-12">
            <div class="alert {{ $isConfigured ? 'alert-info' : 'alert-warning' }} d-flex gap-3 align-items-start mb-0">
                <i class="fa {{ $isConfigured ? 'fa-circle-info' : 'fa-triangle-exclamation' }} fs-4 mt-1"></i>
                <div>
                    <strong class="d-block mb-1">{{ __('home_sections.notice_title') }}</strong>
                    {{ $isConfigured ? __('home_sections.notice_configured') : __('home_sections.notice_not_configured') }}
                </div>
            </div>
        </div>

        {{-- إعداد المطور: الأقسام الموجودة في تصميم موقع العميل --}}
        @if ($isDevSuperAdmin)
            <div class="col-12">
                <form method="post" action="{{ route('dev-superadmin.home-sections.available') }}" class="card border-primary">
                    @csrf
                    @method('PUT')

                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h5 class="mb-0">
                            <i class="fa fa-code me-1"></i>
                            {{ __('home_sections.dev_title') }}
                            <span class="badge bg-primary ms-1">{{ __('home_sections.dev_only') }}</span>
                        </h5>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <select class="form-select form-select-sm w-auto" id="home-sections-preset">
                                <option value="">{{ __('home_sections.choose_preset') }}</option>
                                @foreach ($presets as $presetKey => $preset)
                                    <option value="{{ $presetKey }}" data-sections='@json($preset['sections'] ?? [])'>
                                        {{ $preset['label'] ?? $presetKey }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-success" data-available-all="1">
                                {{ __('home_sections.select_all') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-available-all="0">
                                {{ __('home_sections.clear_all') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">{{ __('home_sections.dev_intro') }}</p>

                        <div class="row g-2">
                            @foreach ($allDefinitions as $key => $definition)
                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                    <div class="form-check">
                                        <input class="form-check-input available-section-input" type="checkbox"
                                            name="available[]" value="{{ $key }}" id="available-section-{{ $key }}"
                                            {{ !$isConfigured || in_array($key, $availableKeys, true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="available-section-{{ $key }}">
                                            {{ __("home_sections.sections.$key.label") }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-center">
                        <button type="submit" class="btn btn-primary px-4">{{ __('home_sections.save_available') }}</button>
                        @if ($isConfigured)
                            <button type="submit" name="reset" value="1" class="btn btn-outline-danger"
                                onclick="return confirm('{{ __('home_sections.reset_confirm') }}')">
                                {{ __('home_sections.reset_available') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        @endif

        <div class="col-12">
            <form method="post" action="{{ route('home-sections.update') }}" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <h5 class="mb-0">{{ __('home_sections.page_title') }}</h5>
                            @if ($canEdit && count($definitions) > 0)
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-success" data-toggle-all="1">
                                        {{ __('home_sections.show_all') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-all="0">
                                        {{ __('home_sections.hide_all') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">{{ __('home_sections.intro') }}</p>

                            @if (count($definitions) === 0)
                                <div class="text-center text-muted py-4">{{ __('home_sections.no_available') }}</div>
                            @endif

                            <div class="row g-3">
                                @foreach ($definitions as $key => $definition)
                                    @php($checked = (bool) old("sections.$key", $visibility[$key] ?? true))
                                    <div class="col-md-6 col-xl-4">
                                        <div class="border rounded p-3 h-100">
                                            <div class="form-check form-switch mb-1">
                                                {{-- عند عدم وجود صلاحية التعديل نُرسل القيمة الحالية حتى لا تتغير --}}
                                                <input type="hidden" name="sections[{{ $key }}]"
                                                    value="{{ $canEdit ? 0 : ($checked ? 1 : 0) }}">
                                                <input class="form-check-input home-section-toggle" type="checkbox"
                                                    name="sections[{{ $key }}]" value="1" id="home-section-{{ $key }}"
                                                    {{ $canEdit ? '' : 'disabled' }} {{ $checked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="home-section-{{ $key }}">
                                                    @if (!empty($definition['icon']))
                                                        <i class="fa {{ $definition['icon'] }} me-1"></i>
                                                    @endif
                                                    {{ __("home_sections.sections.$key.label") }}
                                                </label>
                                            </div>
                                            <small class="text-muted d-block">{{ __("home_sections.sections.$key.hint") }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @if ($canEdit && count($definitions) > 0)
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary px-5">{{ __('buttons.save') }}</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.querySelectorAll('[data-toggle-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                const checked = button.getAttribute('data-toggle-all') === '1';
                document.querySelectorAll('.home-section-toggle:not(:disabled)').forEach(function (input) {
                    input.checked = checked;
                });
            });
        });

        document.querySelectorAll('[data-available-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                const checked = button.getAttribute('data-available-all') === '1';
                document.querySelectorAll('.available-section-input').forEach(function (input) {
                    input.checked = checked;
                });
            });
        });

        const presetSelect = document.getElementById('home-sections-preset');
        if (presetSelect) {
            presetSelect.addEventListener('change', function () {
                const option = presetSelect.options[presetSelect.selectedIndex];
                if (!option || !option.value) {
                    return;
                }

                let sections = [];
                try {
                    sections = JSON.parse(option.getAttribute('data-sections') || '[]');
                } catch (e) {
                    sections = [];
                }

                document.querySelectorAll('.available-section-input').forEach(function (input) {
                    input.checked = sections.indexOf(input.value) !== -1;
                });
            });
        }
    </script>
@endpush
