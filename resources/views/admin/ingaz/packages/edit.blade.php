@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("packages.update",$obj->id)}}">
    @csrf
    @method('Put')
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}} ({{helperTrans('ingaz.'.$locale)}})</label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required" value="{{$obj->translate($locale)->title}}">
        </div>
    @endforeach
    <div class="col-6">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.nationality")}} </label>
        <select name="nationality_id" class="form-control">
            {{-- {!! showSelectElement($nationalities , 'title',$obj->nationality_id) !!} --}}
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}" {{ $obj->nationality_id == $nationality->id ? 'selected' : '' }}>{{ $nationality->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.original_price")}}</label>
        <input type="number" min="0" name="original_price" class="form-control" data-validation="required" value="{{$obj->original_price}}">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.price_after_discount")}}</label>
        <input type="number" min="0" name="price_after_discount" class="form-control" data-validation="required" value="{{$obj->price_after_discount}}">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.price_with_tax")}}</label>
        <input type="number" min="0" name="price_with_tax" class="form-control" data-validation="required" value="{{$obj->price_with_tax}}">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.days_count")}}</label>
        <input type="number" min="1" name="days_count" class="form-control" data-validation="required" value="{{$obj->days_count}}">
    </div>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
@else
    @include('components.no_permissions')
@endif

