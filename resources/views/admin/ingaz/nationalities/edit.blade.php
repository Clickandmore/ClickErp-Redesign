@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
<form class="row g-3 from-submit-global nationality-edit-form" method="post" action="{{route("nationalities.update",$obj->id)}}">
    @csrf
    @method('Put')
    <div class="nationality-form-section"><i class="fas fa-id-card"></i>البيانات الأساسية</div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.image")}}   </label>
        <input type="file" data-default-file="{{getFile($obj->logo)}}" name="logo" class="dropify">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.country_code")}}
        </label>
        <input type="text" name="phone_code" class="form-control" data-validation="required" value="{{$obj->phone_code}}">
    </div>
    <div class="col-md-4 col-12">
        <label for="sort_order" class="form-label">{{ __('countries.sort_order') }}</label>
        <input type="number" id="sort_order" name="sort_order" class="form-control"
               min="1" step="1" value="{{ old('sort_order', $obj->sort_order) }}" data-validation="required">
    </div>
    <div class="nationality-form-section"><i class="fas fa-coins"></i>الأسعار والتكاليف</div>
    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label">
            تكلفة الاستقدام
        </label>
        <input type="number" name="price" class="form-control" value="{{$obj->price}}" placeholder="يترك فارغ في حالة تحديد سعر خاص لكل عاملة">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            الاستقدام المباشر
        </label>
        <input type="number" step="0.01" min="0" name="direct_recruitment_cost" class="form-control" value="{{$obj->direct_recruitment_cost}}" placeholder="تكلفة الاستقدام المباشر">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            الاستقدام الخارجي
        </label>
        <input type="number" step="0.01" min="0" name="external_recruitment_cost" class="form-control" value="{{$obj->external_recruitment_cost}}" placeholder="تكلفة الاستقدام الخارجي">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            التكاليف الحكومية
        </label>
        <input type="number" step="0.01" min="0" name="government_cost" class="form-control" value="{{$obj->government_cost}}" placeholder="التكاليف الحكومية">
    </div>
    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label">
            راتب العاملة سبق لها العمل
        </label>
        <input type="number" name="salary_with_experience"  value="{{$obj->salary_with_experience}}" class="form-control">
    </div>
    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label">
            راتب العاملة لم يسبق لها العمل
        </label>
        <input type="number" name="salary_with_no_experience"   value="{{$obj->salary_with_no_experience}}" class="form-control">
    </div>
    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label">
            تكلفة نقل الخدمات
        </label>
        <input type="number" step="0.01" name="service_transportation_cost" class="form-control" value="{{$obj->service_transportation_cost}}" placeholder="تكلفة نقل الخدمات">
    </div>
    <div class="col-md-6 col-12">
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
    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label">
           {{ helperTrans('ingaz.expected_duration') }}
        </label>
        <input type="number" name="expected_duration" class="form-control" value="{{$obj->expected_duration}}" placeholder="{{ helperTrans('ingaz.expected_duration') }}">
    </div>

    <div class="nationality-form-section"><i class="fas fa-language"></i>الاسم والوصف</div>
    @foreach (config('translatable.locales') as $locale)
        <div class="col-md-6 col-12">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.nationality_name")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="sub_title:{{ $locale }}" class="form-control" data-validation="required"
                   value="{{ old('sub_title:'.$locale, optional($obj->translate($locale))->sub_title) }}">
        </div>
    @endforeach

    @foreach (config('translatable.locales') as $locale)
        <div class="col-md-6 col-12">
            <label class="form-label">
                {{ helperTrans('ingaz.description') }}
                ({{ helperTrans('ingaz.'.$locale) }})
            </label>
            <textarea name="description:{{ $locale }}" class="form-control" rows="3" placeholder="{{ helperTrans('ingaz.description') }}">{{ old('description:'.$locale, optional($obj->translate($locale))->description) }}</textarea>
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
