@extends('admin.layout.indexs.index')
@section("style")

{{--
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/summernote.min.css" /> --}}
<style>
    .tox-notification,
    .tox .tox-statusbar {
        display: none !important;
    }
</style>
@endsection
@section('page-title')
{{helperTrans('ingaz.settings')}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))

{{-- {!! addButton(route("settings.create") , __('skaeeb.settings')) !!} --}}
<form class="row g-3 from-submit-global" method="post"
    action="{{ isset($settings) ? route('settings.update', $settings->id) : route('settings.store') }}"
    enctype="multipart/form-data">
    @if(isset($settings))
    @method('PUT')
    @endif

    @foreach (config('translatable.locales') as $locale)
    <div class="col-3">
        <label for="TextInput" class="form-label">اسم الموقع {{ getFieldLanguage($locale) }}</label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-globe "></i>
            </div>
            <input type="text" name="website_name:{{ $locale }}"
                value="{{ isset($settings) ? $settings->translate($locale)->website_name : '' }}" class="form-control">
        </fieldset>
    </div>
    @endforeach


    @foreach (config('translatable.locales') as $locale)
    <div class="col-3">
        <label for="TextInput" class="form-label">العنوان {{ getFieldLanguage($locale) }}</label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-map-marker"></i>
            </div>
            <input type="text" name="location:{{ $locale }}"
                value="{{ isset($settings) ? $settings->translate($locale)->location : '' }}" class="form-control">
        </fieldset>
    </div>
    @endforeach

    <hr>
    <div class="col-4">
        <label for="TextInput" class="form-label">البريد الالكتروني </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-envelope"></i>
            </div>
            <input type="email" name="email" class="form-control"
                value="{{ isset($settings) ? $settings->email : '' }}">
        </fieldset>
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">رقم الهاتف </label>

        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-mobile"></i>
            </div>
            <input type="text" name="phone" class="form-control" value="{{ isset($settings) ? $settings->phone : '' }}">
        </fieldset>
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">رقم هاتف اخر </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-phone"></i>
            </div>
            <input type="text" name="other_phone" class="form-control"
                value="{{ isset($settings) ? $settings->other_phone : '' }}">
        </fieldset>
    </div>

    <hr>
    <div class="col-3">
        <label for="TextInput" class="form-label">رابط تويتر </label>

        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-twitter"></i>
            </div>
            <input type="text" name="twitter" class="form-control"
                value="{{ isset($settings) ? $settings->twitter : '' }}">
        </fieldset>

    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">رابط فيسبوك </label>

        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-facebook"></i>
            </div>
            <input type="text" name="facebook" class="form-control"
                value="{{ isset($settings) ? $settings->facebook : '' }}">
        </fieldset>

    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">رابط انستجرام </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-instagram"></i>
            </div>
            <input type="text" name="instagram" class="form-control"
                value="{{ isset($settings) ? $settings->instagram : '' }}">
        </fieldset>
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">رابط سناب شات </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-qq"></i>
            </div>
            <input type="text" name="snapchat" class="form-control"
                value="{{ isset($settings) ? $settings->snapchat : '' }}">
        </fieldset>
    </div>


    <div class="col-3">
        <label for="TextInput" class="form-label">رابط لينكدان </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-linkedin-square"></i>
            </div>
            <input type="text" name="linkedin" class="form-control"
                value="{{ isset($settings) ? $settings->linkedin : '' }}">
        </fieldset>

    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">رابط يوتيوب </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-youtube"></i>
            </div>
            <input type="text" name="youtube" class="form-control"
                value="{{ isset($settings) ? $settings->youtube : '' }}">
        </fieldset>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">رقم واتساب </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-whatsapp"></i>
            </div>
            <input type="text" name="whatsapp" class="form-control"
                value="{{ isset($settings) ? $settings->whatsapp : '' }}">
        </fieldset>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">رابط الخريطة </label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-link"></i>
            </div>
            <input type="text" name="map_link" class="form-control"
                value="{{ isset($settings) ? $settings->map_link : '' }}">
        </fieldset>
    </div>

    <hr>
    <div class="col-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>لوجو الهدر</h6>
                    <input type="file" data-default-file="{{ isset($settings) ? showFile($settings->logo_header): '' }}"
                        name="logo_header" class="dropify">
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>لوجو الفوتر</h6>
                    <input type="file" data-default-file="{{ isset($settings) ? showFile($settings->logo_footer): '' }}"
                        name="logo_footer" class="dropify">
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>الفيف أيكون</h6>
                    <input type="file" data-default-file="{{ isset($settings) ? showFile($settings->favicon): '' }}"
                        name="favicon" class="dropify">
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6>صورة المقطع الثاني</h6>
                    <input type="file" data-default-file="{{ isset($settings) ? showFile($settings->about_image): '' }}"
                        name="about_image" class="dropify">
                </div>
            </div>
        </div>
    </div>

    <hr>
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">نص حقوق النشر {{ getFieldLanguage($locale) }}</label>
        <input type="text" name="copyright:{{ $locale }}"
            value="{{ isset($settings) ? $settings->translate($locale)->copyright : '' }}" class="form-control">
    </div>
    @endforeach


    <hr>
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">نص صورة المقطع الثاني {{ getFieldLanguage($locale) }}</label>
        <input type="text" name="about_us_sub_title:{{ $locale }}"
            value="{{ isset($settings) ? $settings->translate($locale)->about_us_sub_title : '' }}"
            class="form-control">
    </div>
    @endforeach
    <hr>
    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">سياسة الخصوصية {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" rows="10"
            name="privacy:{{ $locale }}">{{ isset($settings) ? $settings->translate($locale)->privacy : '' }}</textarea>
    </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">الشروط والاحكام {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" rows="10"
            name="terms_conditions:{{ $locale }}">{{ isset($settings) ? $settings->translate($locale)->terms_conditions : '' }}</textarea>
    </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">عن التطبيق {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" rows="10"
            name="about_us_app:{{ $locale }}">{{ isset($settings) ? $settings->translate($locale)->about_us_app : '' }}</textarea>
    </div>
    @endforeach


    <hr>
    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">
            حقوق وواجبات صاحب العمل {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" name="rights_users:{{ $locale }}">
                    {{ isset($settings) ? $settings->translate($locale)->rights_users : '' }}
                </textarea>
    </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">حقوق وواجبات العمالة {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" name="rights_maids:{{ $locale }}">
                    {{ isset($settings) ? $settings->translate($locale)->rights_maids : '' }}
                </textarea>
    </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">نص الفوتر {{ getFieldLanguage($locale) }}</label>
        <textarea rows="3" type="text" name="footer_text:{{ $locale }}"
            class="form-control">{{ isset($settings) ? $settings->translate($locale)->footer_text : '' }}</textarea>
    </div>
    @endforeach


    @foreach (config('translatable.locales') as $locale)
    <div class="col-12">
        <label for="TextInput" class="form-label">شروط الإسترجاع {{ getFieldLanguage($locale) }}</label>
        <textarea class="mytextarea" rows="10"
            name="return_conditions:{{ $locale }}">{{ isset($settings) ? $settings->translate($locale)->return_conditions : '' }}</textarea>
    </div>
    @endforeach




    <div class="col-12 modal-footer">
        @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        @endif
    </div>

</form>

@else
    @include('components.no_permissions')
@endif

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
{{-- <script src="{{asset(" admin")}}/assets/js/bundle/summernote.bundle.js"></script>
<script>
    $(document).ready(function () {
            $('.summernote').summernote();
        });
</script> --}}
@endsection
