@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_content'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">العروض والتنويهات</h6>
            <div class="row g-3 align-items-start">
                <div class="col-md-4">
                    <label class="form-label">هناك عرض خاص ؟</label>
                    <select name="there_are_offer" id="there_are_offer" class="form-control">
                        <option value="">اختر</option>
                        <option value="1" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 1 ? 'selected' : '' }}>نعم</option>
                        <option value="0" {{ old('there_are_offer', $settings->there_are_offer ?? '') == 0 ? 'selected' : '' }}>لا</option>
                    </select>
                </div>

                <div class="col-12" id="offer_text" style="display: none;">
                    <div class="row g-3">
                        @foreach (config('translatable.locales') as $locale)
                            <div class="col-md-6">
                                <label class="form-label">نص العرض {{ getFieldLanguage($locale) }}</label>
                                <textarea name="offer_text:{{ $locale }}" class="form-control" rows="3">{{ $settings->translate($locale)->offer_text ?? '' }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">التنويهات بالعربيه</label>
                    <input type="text" name="note_ar" class="form-control" value="{{ $settings->note_ar ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">التنويهات بالانجليزي</label>
                    <input type="text" name="note_en" class="form-control" value="{{ $settings->note_en ?? '' }}">
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">نصوص الصفحة الرئيسية</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">نص صورة المقطع الثاني {{ getFieldLanguage($locale) }}</label>
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
                        <label class="form-label">نص الفوتر {{ getFieldLanguage($locale) }}</label>
                        <textarea rows="3" name="footer_text:{{ $locale }}" class="form-control">{{ $settings->translate($locale)->footer_text ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">نص حقوق النشر {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="copyright:{{ $locale }}"
                               value="{{ $settings->translate($locale)->copyright ?? '' }}" class="form-control">
                    </div>
                @endforeach
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
