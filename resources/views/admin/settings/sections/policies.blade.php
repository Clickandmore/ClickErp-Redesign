@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_policies'))

@push('style')
    <style>
        .tox-notification,
        .tox .tox-statusbar {
            display: none !important;
        }
    </style>
@endpush

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <label class="form-label">{{ __('admin.settings_rights_duties_link') }}</label>
            <input type="url" name="link_rights_and_duties" class="form-control"
                   value="{{ $settings->link_rights_and_duties ?? '' }}">
        </div>

        <div class="col-12">
            <hr>
        </div>

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_privacy_policy') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" rows="10"
                          name="privacy:{{ $locale }}">{{ $settings->translate($locale)->privacy ?? '' }}</textarea>
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_terms_conditions') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" rows="10"
                          name="terms_conditions:{{ $locale }}">{{ $settings->translate($locale)->terms_conditions ?? '' }}</textarea>
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_about_app') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" rows="10"
                          name="about_us_app:{{ $locale }}">{{ $settings->translate($locale)->about_us_app ?? '' }}</textarea>
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_employer_rights_duties') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" name="rights_users:{{ $locale }}">{{ $settings->translate($locale)->rights_users ?? '' }}</textarea>
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_worker_rights_duties') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" name="rights_maids:{{ $locale }}">{{ $settings->translate($locale)->rights_maids ?? '' }}</textarea>
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-12">
                <label class="form-label">{{ __('admin.settings_return_conditions') }} {{ getFieldLanguage($locale) }}</label>
                <textarea class="mytextarea" rows="10"
                          name="return_conditions:{{ $locale }}">{{ $settings->translate($locale)->return_conditions ?? '' }}</textarea>
            </div>
        @endforeach

        <div class="col-12 text-end">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_CONTROL, \App\PermKeys::WEBSITE_SETTINGS_CONTROL]))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection

@section('js')
    <script src="{{asset('admin')}}/assets/js/tinymce.min.js"></script>
    <script>
        $(document).ready(function () {
            tinymce.init({
                selector: '.mytextarea',
                plugins: 'ai tinycomments mentions anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed permanentpen footnotes advtemplate advtable advcode editimage tableofcontents mergetags powerpaste tinymcespellchecker autocorrect a11ychecker typography inlinecss textcolor colorpicker',
                toolbar: 'undo redo | formatselect | fontselect fontsizeselect | forecolor backcolor | bold italic underline strikethrough | align lineheight | tinycomments | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            });
        });
    </script>
@endsection
