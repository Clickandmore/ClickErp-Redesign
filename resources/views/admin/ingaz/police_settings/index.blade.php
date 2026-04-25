@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.police_settings') }}
@endsection

@section('content')
    @php
        $route = '';
        $method = 'POST';
        if (isset($policeSetting)) {
            $route = route('police-settings.update', $policeSetting->id);
            $method = 'PUT';
        } else {
            $route = route('police-settings.store');
        }
    @endphp

    <form class="row from-submit-global" action="{{ $route }}" method="post" enctype="multipart/form-data">
        @csrf
        @if (isset($policeSetting))
            @method('PUT')
        @endif
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{ helperTrans('ingaz.title_1') }}
                    ({{ helperTrans('ingaz.' . $locale) }})
                </label>
                <input type="text" name="title_1:{{ $locale }}"
                    value="{{ isset($policeSetting) ? $policeSetting->title_1 : 'سياسات وقوانين الاستخدام داخل المملكة' }}"
                    class="form-control" data-validation="required">
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{ helperTrans('ingaz.title_2') }}
                    ({{ helperTrans('ingaz.' . $locale) }})
                </label>
                <input type="text" name="title_2:{{ $locale }}"
                    value="{{ isset($policeSetting) ? $policeSetting->title_2 : 'التوجيهات والإرشادات حسب الدول' }}"
                    class="form-control" data-validation="required">
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.description_1') }}
                    ({{ helperTrans('ingaz.' . $locale) }})
                </label>
                <textarea name="description_1:{{ $locale }}" class="form-control mytextarea" data-validation="required">{!! isset($policeSetting)
                    ? $policeSetting->description_1
                    : 'الدستور والقانون السعودي يقوم على أهم المعايير والمفاهيم العامة بالقوانين المتوازنة التي تضمن حقوق وواجبات كلاً من العامل وصاحب العمل، ولذلك هذه أهم سياسات وقوانين الاستخدام داخل المملكة.' !!}</textarea>
            </div>
        @endforeach
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label class="form-label">
                    {{ helperTrans('ingaz.description_1') }}
                    ({{ helperTrans('ingaz.' . $locale) }})
                </label>
                <textarea name="description_2:{{ $locale }}" class="form-control mytextarea" data-validation="required">{!! isset($policeSetting)
                    ? $policeSetting->description_2
                    : 'تحرص شركة بلاتينيوم الشرقية على توفير بيئة عمل آمنة وواضحة لكلٍ من العملاء والعاملات. لذلك قمنا بإعداد كتيبات التوجيهات والإرشادات بشكل مبسط ومترجم إلى لغات الجنسيات المختلفة، حتى يتمكن الجميع من الاطلاع عليها وفهم حقوقهم وواجباتهم بسهولة.
                يمكنكم تحميل الملفات الخاصة بكل دولة من خلال البطاقات أدناه.' !!}</textarea>
            </div>
        @endforeach
        <div class="col-6">
            <label for="TextInput" class="form-label">{{ __('ingaz.file_workers_rights') }} </label>
            <input type="file"
                data-default-file="{{ isset($policeSetting) ? showFile($policeSetting->file_workers_rights) : '' }}"
                name="file_workers_rights" class="dropify">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{ __('ingaz.file_employee_rights') }} </label>
            <input type="file"
                data-default-file="{{ isset($policeSetting) ? showFile($policeSetting->file_employee_rights) : '' }}"
                name="file_employee_rights" class="dropify">
        </div>
        <div class="mb-3 row">
            <div class="col-sm-12 text-center">
                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
            </div>
        </div>
    </form>
@endsection

@section('js')
    @include('components.ckeditor')

    <script src="{{ asset('admin') }}/assets/js/tinymce.min.js"></script>
    <script>
        $(document).ready(function() {
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

@include('components.sweetalert')
