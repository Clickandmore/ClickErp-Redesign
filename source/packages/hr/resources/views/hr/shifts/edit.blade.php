<form class="row g-3 from-submit-global" method="post" action="{{ route('shifts.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.shift_name") }} </label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
        <input type="hidden" name="shift_id" value="{{ $obj->id }}" class="form-control" data-validation="required">
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.From the hour") }} </label>
        <input type="time" name="from_hour" value="{{ $obj->from_hour }}" class="form-control" data-validation="required">
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Until now") }} </label>
        <input type="time" name="to_hour" value="{{ $obj->to_hour }}" class="form-control" data-validation="required">
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Number of days in a month") }}</label>
        <input type="number" name="month_days" value="{{ $obj->month_days }}" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.The penalty for absence") }} :
            {!! addUpButton(route('sanctions.create'),'absence_sanctions_id' ,false, 'title') !!}
        </label>
        <select class="form-control show-tick ms" name="absence_sanctions_id" id="absence_sanctions_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($allSanction as $oneSanction)
                <option value="{{ $oneSanction->id }}" {{ $oneSanction->id == $obj->absence_sanctions_id ? 'selected' : '' }}>{{ $oneSanction->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <div class="row">
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'SUN') ? 'checked' : '' }}  name="days[]" value="SUN"> الأحد</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'MON') ? 'checked' : '' }}  name="days[]" value="MON"> الاثنين</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'TUE') ? 'checked' : '' }}  name="days[]" value="TUE"> الثلاثاء</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'WED') ? 'checked' : '' }}  name="days[]" value="WED"> الأربعاء</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'THU') ? 'checked' : '' }}  name="days[]" value="THU"> الخميس</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'FRI') ? 'checked' : '' }}  name="days[]" value="FRI"> الجمعة</label>
            <label class="col-3 my-2"><input type="checkbox" {{ $obj->shiftDays->contains('days', 'SAT') ? 'checked' : '' }}  name="days[]" value="SAT"> السبت</label>
        </div>
    </div>

    <h5 class="py-2 px-4 bg-primary text-white rounded shadow-sm text-center">
        <i class="fa fa-clock-o me-2"></i> {{ __("hr::hr.Delay rules") }}
    </h5>
    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
            <tr>
                <th></th>
                <th> {{ __("hr::hr.Number of minutes of absence") }} </th>
                <th>{{ __("hr::hr.penalty") }}</th>
                <th>{{ __("hr::hr.actions") }}</th>
            </tr>
        </thead>
        <tbody id="json_array2" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
        @foreach ($obj->shiftRoles as $item)
            <tr class="y-group">
                <td> </td>
                <td>
                    <input type="number" name="minutes[]" value="{{ $item->minutes }}" data-validation="required" class="form-control">
                </td>
                <td>
                    <select class="form-control show-tick ms" name="sanctions_id[]" data-validation="required">
                        <option value="">اختر جزاء الغياب </option>
                        @foreach ($sanctions as $sanction)
                            <option value="{{ $sanction->id }}" {{ $item->sanctions_id == $sanction->id ? 'selected' : '' }}>{{ $sanction->title }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                    <i class="fa fa-trash-o btnRemove"></i>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="col-12 modal-footer">
        {!! submitButton( route('shifts.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
