@extends('admin.layout.indexs.index')

@section('page-title')
{{helperTrans("ingaz.about_us")}}
@endsection

@section('content')

@php
    $route ="";
    $method = "POST";
    if(isset($about)){
        $route = route('about-us.update', $about->id);
        $method = "PUT";
     }
    else
    {
        $route = route('about-us.store');

    }
@endphp

<form class="row from-submit-global" action="{{ $route}}" method="post" enctype="multipart/form-data">
 @csrf
    @if(isset($about))
        @method('PUT')
    @endif
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.title")}}
            ({{helperTrans('ingaz.'.$locale)}})
        </label>
        <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
    </div>
@endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.about")}}
            ({{helperTrans('ingaz.'.$locale)}})
        </label>
        <textarea name="description:{{ $locale }}" class="form-control mytextarea" rows="10" data-validation="required">
         </textarea>
    </div>

    @endforeach

    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.mission")}}
            ({{helperTrans('ingaz.'.$locale)}})
        </label>
        <textarea type="text" name="mission:{{ $locale }}" class="form-control mytextarea" data-validation="required"></textarea>
    </div>

    @endforeach

    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.vision")}}
            ({{helperTrans('ingaz.'.$locale)}})
        </label>
        <textarea type="text" name="vision:{{ $locale }}" class="form-control mytextarea" data-validation="required"></textarea>
    </div>

    @endforeach
    @foreach (config('translatable.locales') as $locale)
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.value")}}
            ({{helperTrans('ingaz.'.$locale)}})
        </label>
        <textarea type="text" name="value:{{ $locale }}" class="form-control mytextarea" data-validation="required"></textarea>
    </div>

    @endforeach

    <div class="mb-3 row">
        <div class="col-sm-12 text-center">
            <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
        </div>
    </div>
</form>


@endsection

@section('js')
@include('components.ckeditor')

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

@include('components.sweetalert')
