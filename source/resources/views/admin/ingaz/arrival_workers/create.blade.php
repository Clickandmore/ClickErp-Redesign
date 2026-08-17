{{--@if (@AdminGuardHasPerm(\App\PermKeys::FAQ_CONTROL))--}}
<form class="row g-3 from-submit-global" method="post" action="{{route("arrival-workers.store")}}">
    @csrf
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}}
                ({{helperTrans('ingaz.'.$locale)}}
                ) </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.sub_title")}}
                ({{helperTrans('ingaz.'.$locale)}}) </label>
            <input type="text" name="sub_title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-12">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.body")}}
                ({{helperTrans('ingaz.'.$locale)}}) </label>
            <textarea type="text" name="body:{{ $locale }}" class="form-control mytextarea"
                      data-validation="required"></textarea>
        </div>
    @endforeach
    <div class="col-12">
        <label for="TextInput" class="form-label">{{__('ingaz.image')}} </label>
        <input type="file" data-default-file="" name="image" class="dropify" required>
    </div>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button"
                data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
{{--@else--}}
{{--    @include('components.no_permissions')--}}
{{--@endif--}}

