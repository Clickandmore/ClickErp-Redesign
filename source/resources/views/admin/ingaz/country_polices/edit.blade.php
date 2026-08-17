<form class="row g-3 from-submit-global" method="post" action="{{route("country-polices.update",$obj->id)}}">
    @csrf
    @method('Put')
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.name")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="name:{{ $locale }}"  value="{{$obj->translate($locale)->name}}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.text_file_1")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="text_file_1:{{ $locale }}"   value="{{$obj->translate($locale)->text_file_1}}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.text_file_2")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="text_file_2:{{ $locale }}"  value="{{$obj->translate($locale)->text_file_2}}"  class="form-control" data-validation="required">
        </div>
    @endforeach
    <div class="col-4">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.flag")}}   </label>
        <input type="file" data-default-file="{{showFile($obj->flag)}}" name="flag" class="dropify" >
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.file_1")}}   </label>
        <input type="file" data-default-file="{{showFile($obj->file_1)}}" name="file_1" class="dropify">
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.file_2")}}   </label>
        <input type="file" data-default-file="{{showFile($obj->file_2)}}" name="file_2" class="dropify" >
    </div>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>

