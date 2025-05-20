<div class="row">
    <div class="col-12" id="maid-selection">
        <label for="TextInput" class="form-label">{{helperTrans("ingaz.maid")}}</label>
        <select name="maid_id" id="maid_id" class="form-select form-control select2">
            <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
            @foreach($all_available_maids as $maid)
                <option value="{{$maid->id}}"> {{$maid->passport_num }} </option>
            @endforeach
        </select>
    </div>

    <div id="new-maid-section" class="row">
        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.maid_passport")}}</label>
            <input type="text" id="maid_passport" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.religion")}}</label>
            <input type="text" id="maid_religion" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.maid_job")}}</label>
            <input type="text" id="maid_job" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.nationality")}}</label>
            <input type="text" id="maid_nationality" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.salary")}}</label>
            <input type="text" id="maid_salary" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract duration")}}</label>
            <input type="number" id="contract_duration" name="contract_duration" class="form-control">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.experience_years")}}</label>
            <input type="text" id="experience_years" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.age")}}</label>
            <input type="text" id="age" class="form-control" disabled>
        </div>
    </div>
</div>
