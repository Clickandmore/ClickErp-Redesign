@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_social'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_twitter_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-twitter"></i>
                        </div>
                        <input type="text" name="twitter" class="form-control"
                               value="{{ $settings->twitter ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_facebook_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-facebook"></i>
                        </div>
                        <input type="text" name="facebook" class="form-control"
                               value="{{ $settings->facebook ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_instagram_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-instagram"></i>
                        </div>
                        <input type="text" name="instagram" class="form-control"
                               value="{{ $settings->instagram ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_snapchat_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-qq"></i>
                        </div>
                        <input type="text" name="snapchat" class="form-control"
                               value="{{ $settings->snapchat ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_linkedin_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-linkedin-square"></i>
                        </div>
                        <input type="text" name="linkedin" class="form-control"
                               value="{{ $settings->linkedin ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_youtube_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-youtube"></i>
                        </div>
                        <input type="text" name="youtube" class="form-control"
                               value="{{ $settings->youtube ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_tiktok_url') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <input type="text" name="tiktok" class="form-control"
                               value="{{ $settings->tiktok ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_whatsapp_number') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-whatsapp"></i>
                        </div>
                        <input type="text" name="whatsapp" class="form-control"
                               value="{{ $settings->whatsapp ?? '' }}">
                    </fieldset>
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
