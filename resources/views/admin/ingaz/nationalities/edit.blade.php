@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("nationalities.update",$obj->id)}}">
    @csrf
    @method('Put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.image")}}   </label>
        <input type="file" data-default-file="{{getFile($obj->logo)}}" name="logo" class="dropify">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.country_code")}}
        </label>
        <input type="text" name="phone_code" class="form-control" data-validation="required" value="{{$obj->phone_code}}">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة الاستقدام
        </label>
        <input type="number" name="price" class="form-control" value="{{$obj->price}}" placeholder="يترك فارغ في حالة تحديد سعر خاص لكل عاملة">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">
            راتب العاملة سبق لها العمل
        </label>
        <input type="number" name="salary_with_experience"  value="{{$obj->salary_with_experience}}" class="form-control">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">
            راتب العاملة لم يسبق لها العمل
        </label>
        <input type="number" name="salary_with_no_experience"   value="{{$obj->salary_with_no_experience}}" class="form-control">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة نقل الخدمات
        </label>
        <input type="number" step="0.01" name="service_transportation_cost" class="form-control" value="{{$obj->service_transportation_cost}}" placeholder="تكلفة نقل الخدمات">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة التأجير
        </label>
        <input type="number" step="0.01" name="rental_cost" class="form-control" value="{{$obj->rental_cost}}" placeholder="تكلفة التأجير">
    </div>
    {{-- <div class="col-12">
        <label for="TextInput" class="form-label">
            الراتب الشهري
        </label>
        <input type="number" step="0.01" name="monthly_salary" class="form-control" value="{{$obj->monthly_salary}}" placeholder="الراتب الشهري">
    </div> --}}
    <div class="col-12">
        <label for="TextInput" class="form-label">
           {{ helperTrans('ingaz.expected_duration') }}
        </label>
        <input type="number" name="expected_duration" class="form-control" value="{{$obj->expected_duration}}" placeholder="{{ helperTrans('ingaz.expected_duration') }}">
    </div>

    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.nationality_name")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="sub_title:{{ $locale }}" class="form-control" data-validation="required"
                   value="{{$obj->translate($locale)->sub_title}}">
        </div>
    @endforeach

    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label class="form-label">
                {{ helperTrans('ingaz.description') }}
                ({{ helperTrans('ingaz.'.$locale) }})
            </label>
            <textarea name="description:{{ $locale }}" class="form-control" rows="4" placeholder="{{ helperTrans('ingaz.description') }}">{!! old('description:'.$locale, $obj->translate($locale)->description ?? '') !!}</textarea>
        </div>
    @endforeach

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_show" name="is_show" value="1" {{ old('is_show', $obj->is_show) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_show">{{ helperTrans('ingaz.show_on_home') }}</label>
        </div>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>

@else
    @include('components.no_permissions')
@endif
