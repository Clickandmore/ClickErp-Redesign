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
        $ratioFields = [
            'allowed_period_commitment_rate' => 'allowed_period_commitment_title',
            'workforce_arrival_rate' => 'workforce_arrival_title',
            'complaint_handling_rate' => 'complaint_handling_title',
            'arrival_duration_commitment_rate' => 'arrival_duration_commitment_title',
        ];
        $ratingFields = [
            'customer_recommendation_rating' => 'customer_recommendation_title',
            'inquiry_response_rating' => 'inquiry_response_title',
            'recruitment_duration_rating' => 'recruitment_duration_title',
            'complaint_response_rating' => 'complaint_response_title',
        ];
    @endphp

    <form method="post" action="{{ route('musaned-section.update') }}" class="row g-4">
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
                                    value="{{ old('title:' . $locale, $section ? optional($section->translate($locale))->title : '') }}"
                                    maxlength="255" required @readonly(!$canEdit)>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('musaned_section.overall_performance') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" class="form-control" name="overall_performance:{{ $locale }}"
                                    value="{{ old('overall_performance:' . $locale, $section ? optional($section->translate($locale))->overall_performance : '') }}"
                                    maxlength="255" placeholder="{{ __('musaned_section.performance_example') }}"
                                    required @readonly(!$canEdit)>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('musaned_section.subtitle') }} ({{ strtoupper($locale) }})</label>
                                <textarea class="form-control" name="subtitle:{{ $locale }}" rows="3"
                                    maxlength="2000" required @readonly(!$canEdit)>{{ old('subtitle:' . $locale, $section ? optional($section->translate($locale))->subtitle : '') }}</textarea>
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

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('musaned_section.ratios') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($ratioFields as $valueField => $titleField)
                            <div class="col-12 border rounded p-3">
                                <div class="row g-2">
                                    @foreach (config('translatable.locales') as $locale)
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                {{ __('musaned_section.metric_title') }} ({{ strtoupper($locale) }})
                                            </label>
                                            <input type="text" class="form-control"
                                                name="{{ $titleField }}:{{ $locale }}"
                                                value="{{ old($titleField . ':' . $locale, ($section && optional($section->translate($locale))->{$titleField}) ?: trans('musaned_section.' . $valueField, [], $locale)) }}"
                                                maxlength="255" required @readonly(!$canEdit)>
                                        </div>
                                    @endforeach
                                    <div class="col-12">
                                        <label class="form-label">{{ __('musaned_section.percentage_value') }}</label>
                                        <input type="number" class="form-control" name="{{ $valueField }}"
                                            value="{{ old($valueField, $section->{$valueField} ?? 0) }}"
                                            min="0" max="100" step="0.01" required @readonly(!$canEdit)>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('musaned_section.star_ratings') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($ratingFields as $valueField => $titleField)
                            <div class="col-12 border rounded p-3">
                                <div class="row g-2">
                                    @foreach (config('translatable.locales') as $locale)
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                {{ __('musaned_section.metric_title') }} ({{ strtoupper($locale) }})
                                            </label>
                                            <input type="text" class="form-control"
                                                name="{{ $titleField }}:{{ $locale }}"
                                                value="{{ old($titleField . ':' . $locale, ($section && optional($section->translate($locale))->{$titleField}) ?: trans('musaned_section.' . $valueField, [], $locale)) }}"
                                                maxlength="255" required @readonly(!$canEdit)>
                                        </div>
                                    @endforeach
                                    <div class="col-12">
                                        <label class="form-label">
                                            {{ __('musaned_section.rating_value') }} (0–5 <i class="fa fa-star text-warning"></i>)
                                        </label>
                                        <input type="number" class="form-control" name="{{ $valueField }}"
                                            value="{{ old($valueField, $section->{$valueField} ?? 0) }}"
                                            min="0" max="5" step="0.01" required @readonly(!$canEdit)>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($canEdit)
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary px-5">{{ __('buttons.save') }}</button>
            </div>
        @endif
    </form>
@endsection
