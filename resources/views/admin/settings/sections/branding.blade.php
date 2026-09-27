@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_branding'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_logos') }}</h6>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_header_logo') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->logo_header) ?? '' }}"
                                   name="logo_header" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_footer_logo') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->logo_footer) ?? '' }}"
                                   name="logo_footer" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_favicon') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->favicon) ?? '' }}"
                                   name="favicon" class="dropify">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_site_images') }}</h6>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_second_section_image') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->about_image) ?? '' }}"
                                   name="about_image" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_growth_image') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->light_image) ?? '' }}"
                                   name="light_image" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_experience_image') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->experience_image) ?? '' }}"
                                   name="experience_image" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_cvs_cover_image') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->cover_image) ?? '' }}"
                                   name="cover_image" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_qr_image') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->qr_image) ?? '' }}"
                                   name="qr_image" class="dropify">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2">{{ __('admin.settings_zatca_logo') }}</h6>
                            <input type="file" data-default-file="{{ showFile($settings->zatch_logo) ?? '' }}"
                                   name="zatch_logo" class="dropify">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasAnyPerm([\App\PermKeys::SETTINGS_CONTROL, \App\PermKeys::WEBSITE_SETTINGS_CONTROL]))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
