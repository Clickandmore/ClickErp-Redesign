@extends('admin.layout.indexs.index')
@section('page-title')
    {{ __('ingaz.select_workers_settings') }}
@endsection
@section('content')
    {{--    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))--}}
    <form class="row g-3 from-submit-global" method="post" action="
    {{isset($obj) ? route("select-workers-settings.update",$obj->id) :  route("select-workers-settings.store")}}"
          enctype="multipart/form-data">
        @csrf
        @if(isset($obj))
            @method('PUT')
        @endif
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.body")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <textarea type="text" name="body:{{ $locale }}" class="form-control"
                          data-validation="required">{{isset($obj) ? $obj->translate($locale)->body : ''}}</textarea>
            </div>
        @endforeach
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__("ingaz.link")}}
            </label>
            <input type="url" name="link" class="form-control" value="{{isset($obj) ? $obj->link : ''}}"
                   data-validation="required">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{__("ingaz.image")}}
            </label>
            <input type="file" @if (isset($obj) && $obj->image) data-default-file="{{ showFile($obj->image) }}" @endif  name="image"
                   class="dropify">
        </div>
        <div class="mb-3 row">
            <div class="col-sm-12 text-center">
                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
            </div>
        </div>
    </form>
    {{--    @endif--}}
    {{--    @else--}}
    {{--        @include('components.no_permissions')--}}
    {{--    @endif--}}
@endsection

@section('js')
    @include('components.ckeditor')
@endsection

@include('components.sweetalert')
