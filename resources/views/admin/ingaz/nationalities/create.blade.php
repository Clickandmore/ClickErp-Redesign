@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("nationalities.store")}}">
    @csrf
    <div class="col-12">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.image")}}   </label>
        <input type="file" data-default-file="" name="logo" class="dropify" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.country_code")}}
        </label>
        <input type="text" name="phone_code" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            سعر موحد للخادمات من تلك الجنسية
        </label>
        <input type="number" name="price" class="form-control" placeholder="يترك فارغ في حالة تحديد سعر خاص لكل عاملة">
    </div>

@foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.country_name")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.nationality_name")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="sub_title:{{ $locale }}" class="form-control" data-validation="required">
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
