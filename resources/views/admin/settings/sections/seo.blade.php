@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_seo'))

@section('settings-description', __('admin.settings_section_seo_hint'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_seo_meta_data') }}</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_seo_article_tags') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="seo_article_tags:{{ $locale }}"
                               value="{{ $settings->translate($locale)->seo_article_tags ?? '' }}"
                               class="form-control"
                               placeholder="{{ __('admin.settings_seo_article_tags_placeholder') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_title') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="meta_title:{{ $locale }}"
                               value="{{ $settings->translate($locale)->meta_title ?? '' }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_keywords') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="meta_keywords:{{ $locale }}"
                               value="{{ $settings->translate($locale)->meta_keywords ?? '' }}"
                               class="form-control"
                               placeholder="{{ __('admin.settings_meta_keywords_placeholder') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_meta_description') }} {{ getFieldLanguage($locale) }}</label>
                        <textarea name="meta_description:{{ $locale }}" class="form-control" rows="2">{{ $settings->translate($locale)->meta_description ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_tag_manager') }}</h6>
            <p class="text-muted small mb-3">{{ __('admin.settings_tag_manager_hint') }}</p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_tag_manager_head') }}</label>
                    <textarea name="tag_manager_head" class="form-control" rows="8" dir="ltr"
                              placeholder="<!-- Google Tag Manager head code -->">{{ old('tag_manager_head', $settings->tag_manager_head ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('admin.settings_tag_manager_footer') }}</label>
                    <textarea name="tag_manager_footer" class="form-control" rows="8" dir="ltr"
                              placeholder="<!-- Google Tag Manager body/footer code -->">{{ old('tag_manager_footer', $settings->tag_manager_footer ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
