<div class="row g-2" >
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.department") }}    </label>
        {!! addUpButton(route('departments.create'),'deparment_id','' ,'title',true) !!}
        <i class="fa fa-asterisk text-danger fs-10"></i>
        <select class="form-select employee-native-select" name="deparment_id" id="deparment_id" data-validation="required" onchange="checkOther()">
            <option value=""> {{ __("hr::hr.Choose") }}</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}">{{ $department->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.jobs") }} </label>
        {!! addUpButton(route('jobs.create'),'job_id','' ,'title',true) !!}
        <i class="fa fa-asterisk text-danger fs-10"></i>
        <select class="form-select employee-native-select" name="job_id" id="job_id" data-validation="required" onchange="checkOther()">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            @foreach($jobs as $job)
                <option value="{{ $job->id }}">{{ $job->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.shifts") }}    </label>
        {!! addUpButton(route('shifts.create'),'shift_id','' ,'name',true) !!}
        <i class="fa fa-asterisk text-danger fs-10"></i>
        <select class="form-select employee-native-select" name="shift_id" id="shift_id" data-validation="required" onchange="checkOther()">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Date of hiring") }} <i class="fa fa-asterisk text-danger fs-10"></i></label>
        <input type="date" name="hired_date" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Contract expiration date") }}  <i class="fa fa-asterisk text-danger fs-10"></i></label>
        <input type="date" name="contract_end_date" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Responsible Employee Report") }}   </label>
        <select class="form-select employee-native-select" multiple size="4" name="responsible[]">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
        <tr>
            <th></th>
            <th>
                {{ __("hr::hr.vacations") }}
                <span>{!! addUpButton(route('vacations.create'),'allowancesSelect','' ,'title',true,'vacationsSelect') !!}</span>
            </th>
            <th>{{ __("hr::hr.Total days") }}</th>
            <th> {{ __("hr::hr.Consumer credit") }}</th>
            <th> {{ __("hr::hr.Days left") }} </th>
            <th>{{ __("hr::hr.actions") }}</th>
        </tr>
        </thead>
        <tbody id="json_array3" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
        <tr class="y-group">
            <td> </td>
            <td>
                <select class="form-select vacationsSelect" name="vacations[]" id="allowancesSelect">
                    <option value="">{{ __("hr::hr.Choose vacation") }} </option>
                    @foreach($vacations as $vacation)
                        <option data-max="{{ $vacation->number_of_days }}" value="{{ $vacation->id }}">{{ $vacation->title }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number"  readonly name="total_days[]" onkeyup="calculateTotal()" class="form-control total_days">
            </td>
            <td>
                <input type="number" name="token_days[]" onkeyup="calculateTotal()" class="form-control token_days">
            </td>
            <td>
                <input type="number" readonly name="remain_days[]" class="form-control remain_days">
            </td>
            <td>
                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                <i class="fa fa-trash-o btnRemove"></i>
            </td>
        </tr>
        </tbody>
    </table>

</div>
