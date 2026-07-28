     <form class="row g-3 from-submit-global" method="post" action="{{route("partenrs.update",$obj->id)}}">
        @csrf
        @method('Put')

        <div class="col-12">
            <label for="partner-title" class="form-label">
                {{ __('ingaz.title') }} <span class="text-muted">({{ __('ingaz.optional') }})</span>
            </label>
            <input id="partner-title" type="text" name="title" value="{{ old('title', $obj->title) }}"
                   class="form-control" maxlength="255">
        </div>

        <div class="col-12">
            <label for="partner-image" class="form-label">{{ __('ingaz.image') }}</label>
            <input id="partner-image" type="file" data-default-file="{{ showFile($obj->image) }}"
                   name="image" class="dropify">
        </div>

        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>
 

