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
              action="{{ route('settings.update', $settings->id) }}"
              enctype="multipart/form-data">
            @method('PUT')

            @foreach (config('translatable.locales') as $locale)
                <div class="col-3">
                    <label for="TextInput" class="form-label">اسم الموقع {{ getFieldLanguage($locale) }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-globe "></i>
                        </div>
                        <input type="text" name="website_name:{{ $locale }}"
                               value="{{ $settings->translate($locale)->website_name ?? '' }}" class="form-control">
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
                               value="{{ $settings->translate($locale)->location ?? '' }}" class="form-control">
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
                           value="{{ $settings->email ?? '' }}">
                </fieldset>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">رقم الهاتف </label>

                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-mobile"></i>
                    </div>
                    <input type="text" name="phone" class="form-control" value="{{ $settings->phone ?? '' }}">
                </fieldset>
            </div>

            <div class="col-4">
                <label for="TextInput" class="form-label">رقم هاتف اخر </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-phone"></i>
                    </div>
                    <input type="text" name="other_phone" class="form-control"
                           value="{{ $settings->other_phone ?? '' }}">
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
                           value="{{ $settings->twitter ?? '' }}">
                </fieldset>

            </div>

            <div class="col-3">
                <label for="TextInput" class="form-label">رابط فيسبوك </label>

                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-facebook"></i>
                    </div>
                    <input type="text" name="facebook" class="form-control"
                           value="{{ $settings->facebook ?? '' }}">
                </fieldset>

            </div>

            <div class="col-3">
                <label for="TextInput" class="form-label">رابط انستجرام </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-instagram"></i>
                    </div>
                    <input type="text" name="instagram" class="form-control"
                           value="{{ $settings->instagram ?? '' }}">
                </fieldset>
            </div>

            <div class="col-3">
                <label for="TextInput" class="form-label">رابط سناب شات </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-qq"></i>
                    </div>
                    <input type="text" name="snapchat" class="form-control"
                           value="{{ $settings->snapchat ?? '' }}">
                </fieldset>
            </div>


            <div class="col-3">
                <label for="TextInput" class="form-label">رابط لينكدان </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-linkedin-square"></i>
                    </div>
                    <input type="text" name="linkedin" class="form-control"
                           value="{{ $settings->linkedin ?? '' }}">
                </fieldset>

            </div>
            <div class="col-3">
                <label for="TextInput" class="form-label">رابط يوتيوب </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-youtube"></i>
                    </div>
                    <input type="text" name="youtube" class="form-control"
                           value="{{ $settings->youtube ?? '' }}">
                </fieldset>
            </div>
            <div class="col-3">
                <label for="TextInput" class="form-label">رقم واتساب </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-whatsapp"></i>
                    </div>
                    <input type="text" name="whatsapp" class="form-control"
                           value="{{ $settings->whatsapp ?? '' }}">
                </fieldset>
            </div>
            <div class="col-3">
                <label for="TextInput" class="form-label">رابط الخريطة </label>
                <fieldset class="form-icon-group right-icon position-relative">
                    <div class="form-icon position-absolute">
                        <i class="fa fa-link"></i>
                    </div>
                    <input type="text" name="map_link" class="form-control"
                           value="{{ $settings->map_link ?? '' }}">
                </fieldset>
            </div>
            <input type="hidden" name="show_customer_service_during_booking" value="0">

            <hr>
            <div class="row">
                <div class="col-4">
                    <label for="TextInput" class="form-label"> سنين الخبره </label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-link"></i>
                        </div>
                        <input type="number" name="years" class="form-control"
                               value="{{ $settings->years ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-4">
                    <label class="form-label d-block"> تفعيل خدمة العملاء</label>
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="show_customer_service_during_booking"
                            id="show_customer_service_during_booking"
                            class="form-check-input"
                            value="1"
                            {{ !empty($settings->show_customer_service_during_booking) ? 'checked' : '' }}>

                        <label class="form-check-label" for="show_customer_service_during_booking">
                            {{ !empty($settings->show_customer_service_during_booking) ? 'مفعل' : 'غير مفعل' }}
                        </label>
                    </div>
                </div>
                <div class="col-4">
                    <label for="TextInput" class="form-label"> رابط الحقوق والواجبات </label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-link"></i>
                        </div>
                        <input type="url" name="link_rights_and_duties" class="form-control"
                               value="{{ $settings->link_rights_and_duties ?? '' }}">
                    </fieldset>
                </div>

            </div>
            <div class="row">

                <div class="col-6">
                    <label for="TextInput" class="form-label"> التنويهات بالعربيه </label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-link"></i>
                        </div>
                        <input type="text" name="note_ar" class="form-control"
                               value="{{ $settings->note_ar ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-6">
                    <label for="TextInput" class="form-label"> التنويهات بالانجليزي </label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-link"></i>
                        </div>
                        <input type="text" name="note_en" class="form-control"
                               value="{{ $settings->note_en ?? '' }}">
                    </fieldset>
                </div>
                <div class="row align-items-start">
                    <div class="col-6">
                        <label for="there_are_offer" class="form-label"> هناك عرض خاص ؟</label>
                        <fieldset class="form-icon-group right-icon position-relative">
                            <select name="there_are_offer" id="there_are_offer" class="form-control">
                                <option value="">اختر</option>
                                <option
                                    value="1" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 1 ? 'selected' : '' }}>
                                    نعم
                                </option>
                                <option
                                    value="0" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 0 ? 'selected' : '' }}>
                                    لا
                                </option>
                            </select>
                        </fieldset>
                    </div>

                    <div class="col-6" id="offer_text" style="display: none;">
                        <div class="row">
                            @foreach (config('translatable.locales') as $locale)
                                <div class="col-6 mb-3">
                                    <label class="form-label">نص العرض {{ getFieldLanguage($locale) }}</label>
                                    <fieldset class="form-icon-group right-icon position-relative">
                                        <textarea name="offer_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->offer_text ?? '' }}</textarea>
                                    </fieldset>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6>لوجو الهدر</h6>
                            <input type="file" data-default-file="{{ showFile($settings->logo_header) ?? '' }}"
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
                            <input type="file" data-default-file="{{ showFile($settings->logo_footer) ?? '' }}"
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
                            <input type="file" data-default-file="{{ showFile($settings->favicon) ?? '' }}"
                                   name="favicon" class="dropify">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6>صورة المقطع الثاني</h6>
                            <input type="file" data-default-file="{{ showFile($settings->about_image) ?? '' }}"
                                   name="about_image" class="dropify">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6>صورة الازدهار والنمو</h6>
                            <input type="file" data-default-file="{{ showFile($settings->light_image) ?? '' }}"
                                   name="light_image" class="dropify">
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6>صورة QR </h6>
                        <input type="file" data-default-file="{{ showFile($settings->qr_image) ?? '' }}"
                               name="qr_image" class="dropify">
                    </div>
                </div>
            </div>

            {{--            <div class="col-3">--}}
            {{--                <div class="card">--}}
            {{--                    <div class="card-body">--}}
            {{--                        <h6>صورة qr الثانيه </h6>--}}
            {{--                        <input type="file" data-default-file="{{ showFile($settings->second_qr_image) ?? '' }}"--}}
            {{--                               name="second_qr_image" class="dropify">--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--            </div>--}}
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6>صورة الخبره</h6>
                        <input type="file" data-default-file="{{ showFile($settings->experience_image) ?? '' }}"
                               name="experience_image" class="dropify">
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h6>صورة غلاف صفحة السير الذاتية</h6>
                        <input type="file" data-default-file="{{ showFile($settings->cover_image) ?? '' }}"
                               name="cover_image" class="dropify">
                    </div>
                </div>
            </div>

            <hr>
            @foreach (config('translatable.locales') as $locale)
                <div class="col-6">
                    <label for="TextInput" class="form-label">نص حقوق النشر {{ getFieldLanguage($locale) }}</label>
                    <input type="text" name="copyright:{{ $locale }}"
                           value="{{ $settings->translate($locale)->copyright ?? '' }}" class="form-control">
                </div>
            @endforeach


            <hr>
            @foreach (config('translatable.locales') as $locale)
                <div class="col-6">
                    <label for="TextInput" class="form-label">نص صورة المقطع
                        الثاني {{ getFieldLanguage($locale) }}</label>
                    <input type="text" name="about_us_sub_title:{{ $locale }}"
                           value="{{ $settings->translate($locale)->about_us_sub_title ?? '' }}"
                           class="form-control">
                </div>
            @endforeach
            <hr>
            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">سياسة الخصوصية {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" rows="10"
                              name="privacy:{{ $locale }}">{{ $settings->translate($locale)->privacy ?? '' }}</textarea>
                </div>
            @endforeach
            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">الشروط والاحكام {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" rows="10"
                              name="terms_conditions:{{ $locale }}">{{ $settings->translate($locale)->terms_conditions ?? '' }}</textarea>
                </div>
            @endforeach
            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">عن التطبيق {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" rows="10"
                              name="about_us_app:{{ $locale }}">{{ $settings->translate($locale)->about_us_app ?? '' }}</textarea>
                </div>
            @endforeach


            <hr>
            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">
                        حقوق وواجبات صاحب العمل {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" name="rights_users:{{ $locale }}">
                    {{ $settings->translate($locale)->rights_users ?? '' }}
                </textarea>
                </div>
            @endforeach
            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">حقوق وواجبات
                        العمالة {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" name="rights_maids:{{ $locale }}">
                    {{ $settings->translate($locale)->rights_maids ?? '' }}
                </textarea>
                </div>
            @endforeach
            @foreach (config('translatable.locales') as $locale)
                <div class="col-6">
                    <label for="TextInput" class="form-label">نص الفوتر {{ getFieldLanguage($locale) }}</label>
                    <textarea rows="3" type="text" name="footer_text:{{ $locale }}"
                              class="form-control">{{ $settings->translate($locale)->footer_text ?? '' }}</textarea>
                </div>
            @endforeach


            @foreach (config('translatable.locales') as $locale)
                <div class="col-12">
                    <label for="TextInput" class="form-label">شروط الإسترجاع {{ getFieldLanguage($locale) }}</label>
                    <textarea class="mytextarea" rows="10"
                              name="return_conditions:{{ $locale }}">{{ $settings->translate($locale)->return_conditions ?? '' }}</textarea>
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
    {{--     <script src="{{asset(" admin")}}/assets/js/bundle/summernote.bundle.js"></script>--}}
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

            // تشغيل الحالة أول مرة عند تحميل الصفحة
            $('#there_are_offer').trigger('change');
        });

    </script>
@endsection
