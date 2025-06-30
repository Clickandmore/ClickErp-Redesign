@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))

    <form class="row g-3 from-submit-global-maid" method="post" action="{{route("maids.update",$obj->id)}}">
        @csrf
        @method('Put')
        <div class="col-6">
            <label for="TextInput" class="form-label">صورة العامله   </label>
            <input type="file" data-default-file="{{showFile($obj->image)}}" name="image" class="dropify" data-validation="required">
        </div>
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.cv_file")}}   </label>
            <input type="file" data-default-file="{{showFile($obj->cv_file)}}" name="cv_file" class="dropify">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.nationality")}} </label>
            <select name="nationality_id" id="Pnationality_id" class="form-control">
                <option value="">اختر </option>
                @foreach($nationalities as $nationality)
                    <option data-price="{{$nationality->price ?? 0}}" {{$obj->nationality_id == $nationality->id ? 'selected' : ''}} value="{{$nationality->id}}">{{$nationality->title}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.experience_years")}}
            </label>
            <input type="text" value="{{$obj->experience_years}}" name="experience_years" class="form-control" data-validation="required">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.price")}}
            </label>
            <input type="number" name="price" id="price" class="form-control" data-validation="required" value="{{$obj->price}}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.salary")}}
            </label>
            <input type="number" name="salary" class="form-control" data-validation="required" value="{{$obj->salary}}">
        </div>


        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.age")}}
            </label>
            <input type="number" min="18" max="90" name="age" class="form-control" data-validation="required" value="{{$obj->age}}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.height")}}
            </label>
            <input type="text" name="height" class="form-control" value="{{$obj->height}}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.weight")}}
            </label>
            <input type="text" name="weight" class="form-control" value="{{$obj->weight}}">
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.religion")}} </label>
            <select name="religion_id" class="form-control">
                {!! showSelectElement($religions , 'title',$obj->religion_id) !!}
            </select>
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.passport_num")}}
            </label>
            <input type="text" name="passport_num" class="form-control" data-validation="required" value="{{$obj->passport_num}}">
        </div>



        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_titles")}} </label>
            <select name="job_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                    class="form-control js-example-basic-multiple" multiple data-validation="required">
                @foreach($jobs as $job)
                    <option
                        {{ in_array($job->id, $obj->jobTitles->pluck('id')->toArray()) ? 'selected' : '' }}
                        value="{{$job->id}}">{{$job->title}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-4">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_types")}} </label>
            <select name="type_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                    class="form-control js-example-basic-multiple" multiple data-validation="required">
                @foreach($types as $type)
                    <option
                        {{ in_array($type->id, $obj->jobTypes->pluck('id')->toArray()) ? 'selected' : '' }}
                            value="{{$type->id}}">{{$type->title}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">{{helperTrans("ingaz.languages she talk")}}</label>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="languages[arabic]" id="arabic" value="نعم" {{ str_contains($obj->translate('ar')->languages, 'العربية') ? 'checked' : '' }}>
                        <label class="form-check-label" for="arabic">
                            اللغة العربية
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="languages[english]" id="english" value="نعم" {{ str_contains($obj->translate('ar')->languages, 'الإنجليزية') ? 'checked' : '' }}>
                        <label class="form-check-label" for="english">
                            اللغة الإنجليزية
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="languages[amharic]" id="amharic" value="نعم" {{ str_contains($obj->translate('ar')->languages, 'الأمهرية') ? 'checked' : '' }}>
                        <label class="form-check-label" for="amharic">
                            اللغة الأمهرية
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="languages[mother]" id="mother" value="نعم" {{ str_contains($obj->translate('ar')->languages, 'الأم') ? 'checked' : '' }}>
                        <label class="form-check-label" for="mother">
                            اللغة الأم
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.marital status")}} </label>
            <select name="marital_status:ar" class="form-control" data-validation="required">
                <option value="عازبة" {{($obj->translate('ar')->marital_status == 'عازبة') ? 'selected' : ''}}>عازبة</option>
                <option value="أعزب" {{($obj->translate('ar')->marital_status == 'أعزب') ? 'selected' : ''}}>أعزب</option>
                <option value="متزوجة" {{($obj->translate('ar')->marital_status == 'متزوجة') ? 'selected' : ''}}>متزوجة</option>
                <option value="متزوج" {{($obj->translate('ar')->marital_status == 'متزوج') ? 'selected' : ''}}>متزوج</option>
            </select>
        </div>

        <div class="col-12">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.recruitment office")}} </label>
            <select name="office_id" class="form-control" data-validation="required">
                {!! showSelectElement($offices , 'title',$obj->office_id) !!}
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
