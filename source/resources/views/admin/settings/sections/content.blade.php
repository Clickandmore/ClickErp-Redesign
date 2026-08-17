@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_content'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_offers_notices') }}</h6>
            <div class="row g-3 align-items-start">
                <div class="col-md-4">
                    <label class="form-label">{{ __('admin.settings_has_special_offer') }}</label>
                    <select name="there_are_offer" id="there_are_offer" class="form-control">
                        <option value="">{{ __('admin.settings_choose') }}</option>
                        <option value="1" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 1 ? 'selected' : '' }}>{{ __('admin.settings_yes') }}</option>
                        <option value="0" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 0 ? 'selected' : '' }}>{{ __('admin.settings_no') }}</option>
                    </select>
                </div>

                <div class="col-12" id="offer_text" style="display: none;">
                    <div class="row g-3">
                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.settings_offer_text') }} {{ getFieldLanguage($locale) }}</label>
                                <textarea name="offer_text:{{ $locale }}" class="form-control" rows="3">{{ $settings->translate($locale)->offer_text ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.settings_notices_ar') }}</label>
                    <input type="text" name="note_ar" class="form-control" value="{{ $settings->note_ar ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.settings_notices_en') }}</label>
                    <input type="text" name="note_en" class="form-control" value="{{ $settings->note_en ?? '' }}">
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_home_texts') }}</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_second_section_text') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="about_us_sub_title:{{ $locale }}"
                               value="{{ $settings->translate($locale)->about_us_sub_title ?? '' }}"
                               class="form-control">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_footer_text') }} {{ getFieldLanguage($locale) }}</label>
                        <textarea rows="3" name="footer_text:{{ $locale }}" class="form-control">{{ $settings->translate($locale)->footer_text ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_copyright_text') }} {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="copyright:{{ $locale }}"
                               value="{{ $settings->translate($locale)->copyright ?? '' }}" class="form-control">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <div class="alert alert-info mb-0">
                {{ __('admin.settings_forms_contracts_moved_before') }}
                <a href="{{ route('form-settings.index') }}" class="alert-link">{{ __('admin.settings_forms_settings') }}</a>
                {{ __('admin.settings_forms_contracts_moved_after') }}
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#there_are_offer').on('change', function () {
                var value = $(this).val();
                if (value === "1") {
                    $("#offer_text").show();
                } else {
                    $("#offer_text").hide();
                }
            });
            $('#there_are_offer').trigger('change');
        });
    </script>
@endsection
