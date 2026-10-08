<form class="row g-3 from-submit-global" method="post" action="{{route("fast-news.store")}}" enctype="multipart/form-data">
    @csrf
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.title")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>

    @endforeach
    <div class="col-12">
        <label class="form-label">
            {{helperTrans("ingaz.image")}}
            <small class="text-muted">({{ __('web.fast_news_image_hint') }})</small>
        </label>
        <input type="file" name="image" class="dropify" data-default-file=""
               data-allowed-file-extensions="jpg jpeg png webp gif svg" data-max-file-size="2M">
    </div>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
