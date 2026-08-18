@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("nationalities.store")}}" enctype="multipart/form-data">
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
        <label for="sort_order" class="form-label">{{ __('countries.sort_order') }}</label>
        <input type="number" id="sort_order" name="sort_order" class="form-control"
               min="1" step="1" value="{{ old('sort_order', $nextSortOrder) }}" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">
            تكلفة الاستقدام
        </label>
        <input type="number" name="price" class="form-control" placeholder="يترك فارغ في حالة تحديد سعر خاص لكل عاملة">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            الاستقدام المباشر
        </label>
        <input type="number" step="0.01" min="0" name="direct_recruitment_cost" class="form-control" placeholder="تكلفة الاستقدام المباشر">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            الاستقدام الخارجي
        </label>
        <input type="number" step="0.01" min="0" name="external_recruitment_cost" class="form-control" placeholder="تكلفة الاستقدام الخارجي">
    </div>
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">
            التكاليف الحكومية
        </label>
        <input type="number" step="0.01" min="0" name="government_cost" class="form-control" placeholder="التكاليف الحكومية">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">
            راتب العاملة سبق لها العمل
        </label>
        <input type="number" name="salary_with_experience" class="form-control">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">
            راتب العاملة لم يسبق لها العمل
        </label>
        <input type="number" name="salary_with_no_experience" class="form-control">
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
    <div class="col-12">
        <label for="expected_duration" class="form-label">
            {{ helperTrans('ingaz.expected_duration') }}
        </label>
        <input type="number" id="expected_duration" name="expected_duration" class="form-control" min="45" value="{{ old('expected_duration', 45) }}" placeholder="{{ helperTrans('ingaz.expected_duration') }}" data-validation="required">
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

    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label class="form-label">
                {{ helperTrans('ingaz.description') }}
                ({{ helperTrans('ingaz.'.$locale) }})
            </label>
            <textarea name="description:{{ $locale }}" class="form-control" rows="4" placeholder="{{ helperTrans('ingaz.description') }}">{{ old('description:'.$locale) }}</textarea>
        </div>
    @endforeach

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_show" name="is_show" value="1" {{ old('is_show', 1) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_show">{{ helperTrans('ingaz.show_on_home') }}</label>
        </div>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        @if (request()->ajax())
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        @else
            <a class="btn btn-outline-secondary" href="{{ route('nationalities.index') }}">{{ helperTrans('ingaz.back') }}</a>
        @endif
    </div>
</form>

@else
    @include('components.no_permissions')
@endif
