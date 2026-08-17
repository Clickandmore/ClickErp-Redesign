     <form class="row g-3 from-submit-global" method="post" action="{{route("partenrs.store")}}">
        @csrf

        <div class="col-12">
            <label for="partner-title" class="form-label">
                {{ __('ingaz.title') }} <span class="text-muted">({{ __('ingaz.optional') }})</span>
            </label>
            <input id="partner-title" type="text" name="title" value="{{ old('title') }}"
                   class="form-control" maxlength="255">
        </div>

        <div class="col-12">
            <label for="partner-image" class="form-label">{{ __('ingaz.image') }}</label>
            <input id="partner-image" type="file" data-default-file="" name="image" class="dropify" required>
        </div>
        


        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>

 
