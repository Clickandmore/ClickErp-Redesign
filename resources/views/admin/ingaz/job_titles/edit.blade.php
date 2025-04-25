@if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("job-titles.update",$obj->id)}}">
    @csrf
    @method('Put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.image")}}   </label>
        <input type="file" name="image" class="dropify" data-default-file="{{getFile($obj->image)}}" >
    </div>
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_name")}} ({{helperTrans('ingaz.'.$locale)}}
                ) </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required"
                   value="{{$obj->translate($locale)->title}}">
        </div>
    @endforeach
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
@else
    @include('components.no_permissions')
@endif

