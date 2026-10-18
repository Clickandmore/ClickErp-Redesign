     <form class="row g-3 from-submit-global" method="post" action="{{route("fast-news.update",$obj->id)}}" enctype="multipart/form-data">
        @csrf
      @method('Put')
        @include('admin.ingaz.fast_news._visibility_switch', ['checked' => (bool) $obj->is_visible])
        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.title")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
                <input type="text" name="title:{{ $locale }}" value="{{$obj->translate($locale)->title}}" class="form-control" data-validation="required">
            </div>
        @endforeach
        <div class="col-12">
            <label class="form-label">
                {{helperTrans("ingaz.image")}}
                <small class="text-muted">({{ __('web.fast_news_image_hint') }})</small>
            </label>
            <input type="file" name="image" class="dropify" data-default-file="{{ showFile($obj->image) }}"
                   data-allowed-file-extensions="jpg jpeg png webp gif svg" data-max-file-size="2M">
            @if (filled($obj->image))
                <label class="form-check mt-2">
                    <input type="checkbox" name="remove_image" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('web.fast_news_remove_image') }}</span>
                </label>
            @endif
        </div>
                <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>
