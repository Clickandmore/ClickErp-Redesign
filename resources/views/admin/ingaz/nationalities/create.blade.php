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
            تكلفة الاستقدام
        </label>
        <input type="number" name="price" class="form-control" placeholder="يترك فارغ في حالة تحديد سعر خاص لكل عاملة">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة نقل الخدمات
        </label>
        <input type="number" step="0.01" name="service_transportation_cost" class="form-control" placeholder="تكلفة نقل الخدمات">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة التأجير
        </label>
        <input type="number" step="0.01" name="rental_cost" class="form-control" placeholder="تكلفة التأجير">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            الراتب الشهري
        </label>
        <input type="number" step="0.01" name="monthly_salary" class="form-control" placeholder="الراتب الشهري">
    </div>

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
