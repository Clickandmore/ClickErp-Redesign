@if (@AdminGuardHasPerm(\App\PermKeys::REC_OFFIICES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("recruitment-offices.store")}}">
    @csrf
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}}
                ({{helperTrans('ingaz.'.$locale)}}) </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach

    <select name="nationality_id" class="form-select form-control">
        @foreach($countries as $country)
            <option value="{{$country->id}}">{{$country->title}}</option>
        @endforeach
    </select>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>

@else
    @include('components.no_permissions')
@endif
