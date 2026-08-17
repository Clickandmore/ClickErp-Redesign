<form class="row g-3 from-submit-global" method="post" action="{{route("features.update",$obj->id)}}" enctype="multipart/form-data">
    @csrf
    @method('Put')
    <label for="TextInput" class="form-label">
        {{helperTrans("ingaz.counter icon")}}
    </label>
    @include('admin.ingaz.statistics.icons')
    <div class="col-12">
        <label class="form-label">{{ helperTrans('ingaz.image') }} ({{ helperTrans('ingaz.optional') }})</label>
        <input type="file" name="image_file" class="dropify" accept="image/jpeg,image/png,image/webp,image/svg+xml"
               @if($obj->image_url) data-default-file="{{ $obj->image_url }}" @endif>
    </div>
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.title")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="title:{{ $locale }}" value="{{$obj->translate($locale)->title}}"
                   class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.description")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <textarea type="text" name="description:{{ $locale }}"
                      class="form-control"
                      data-validation="required">{{$obj->translate($locale)->description}}</textarea>
        </div>
    @endforeach

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
