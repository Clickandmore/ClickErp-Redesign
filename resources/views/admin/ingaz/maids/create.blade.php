@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL))

    <form class="row g-3 from-submit-global-maid" method="post" action="{{route("maids.store")}}">
        @csrf
        <div class="col-6">
            <label for="TextInput" class="form-label">صورة العامله   </label>
            <input type="file" data-default-file="" name="image" class="dropify" data-validation="required">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.cv_file")}}   </label>
            <input type="file" data-default-file="" name="cv_file" class="dropify">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.nationality")}} </label>
            <select name="nationality_id" id="Pnationality_id" class="form-control">
                <option value="">اختر </option>
                @foreach($nationalities as $nationality)
                    <option data-price="{{$nationality->price ?? 0}}" value="{{$nationality->id}}">{{$nationality->title}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.experience_years")}}
            </label>
            <input type="text" name="experience_years" class="form-control" data-validation="required">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.price")}}
            </label>
            <input type="number" name="price" id="price" class="form-control" data-validation="required">
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.salary")}}
            </label>
            <input type="number" name="salary" class="form-control" data-validation="required">
        </div>


        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.age")}}
            </label>
            <input type="number" min="18" max="90" name="age" class="form-control" data-validation="required">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.height")}}
            </label>
            <input type="text" name="height" class="form-control">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.weight")}}
            </label>
            <input type="text" name="weight" class="form-control">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.religion")}} </label>
            <select name="religion_id" class="form-control">
                {!! showSelectElement($religions , 'title') !!}
            </select>
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.passport_num")}}
            </label>
            <input type="text" name="passport_num" class="form-control" data-validation="required">
        </div>



        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_2")}} </label>
            <select name="job_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                    class="form-control js-example-basic-multiple" multiple data-validation="required">
                @foreach($jobs as $job)
                    <option value="{{$job->id}}">{{$job->title}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_types_2")}} </label>
            <select name="type_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                    class="form-control js-example-basic-multiple" multiple data-validation="required">
                @foreach($types as $type)
                    <option value="{{$type->id}}">{{$type->title}}</option>
                @endforeach
            </select>
        </div>

        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.languages she talk")}} ({{helperTrans('ingaz.'.$locale)}}) </label>
                <input type="text" name="languages:{{ $locale }}" class="form-control" data-validation="required">
            </div>
        @endforeach

        @foreach (config('translatable.locales') as $locale)
            <div class="col-6">
                <label for="TextInput" class="form-label">{{helperTrans("ingaz.marital status")}} ({{helperTrans('ingaz.'.$locale)}}) </label>
                <input type="text" name="marital_status:{{ $locale }}" class="form-control" data-validation="required">
            </div>
        @endforeach

        <div class="col-12">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.recruitment office")}} </label>
            <select name="office_id" class="form-control" data-validation="required">
                {!! showSelectElement($offices , 'title') !!}
            </select>
        </div>


        <div class="col-12 modal-footer">
            <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
        </div>
    </form>
    <style>
        .select2-container {
            z-index: 9999 !important;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('.js-example-basic-multiple').select2();
        });
        $(document).ready(function(){
            // Listen for change event on the select element
            $('#Pnationality_id').change(function(){
                var selectedOption = $(this).find(':selected');
                var price = selectedOption.data('price');
                $('#price').val(price);
            });
        });
    </script>

@else
    @include('components.no_permissions')
@endif
