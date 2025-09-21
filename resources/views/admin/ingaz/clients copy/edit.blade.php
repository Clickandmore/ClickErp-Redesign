     <form class="row g-3 from-submit-global" method="post" action="{{route("fast-news.update",$obj->id)}}">
        @csrf
      @method('Put')
        
        @foreach (config('translatable.locales') as $locale)
             <div class="col-6">
                <label for="TextInput" class="form-label">
                    {{helperTrans("ingaz.title")}}
                    ({{helperTrans('ingaz.'.$locale)}})
                </label>
               <input type="text" name="title:{{ $locale }}" class="form-control"
    value="{{ old("title:$locale", $obj->translate($locale)->title) }}"
    data-validation="required">

                          </div>
        
@endforeach
 
                <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>
 
