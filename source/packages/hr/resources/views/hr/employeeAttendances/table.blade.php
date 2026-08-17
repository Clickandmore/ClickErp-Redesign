@if ($employees->count() > 0)
    @foreach ($employees as $index => $item)
        <tr class="y-group">
            <td>
                <select class="form-control show-tick ms" name="employee_id[]" data-validation="required">
                    <option value=""> {{ __("hr.Choose") }} </option>
                    @foreach ($allEmployees as $allEmployee)
                        <option value="{{ $allEmployee->id }}" {{ $allEmployee->id == $item->id ? "selected" : "" }}>{{ $allEmployee->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="date" name="date[]" data-validation="required" class="form-control">
            </td>
            <td>
                <input type="time" name="attendance_time[]" data-validation="required" class="form-control">
            </td>
            <td>
                <input type="time" name="leave_time[]" data-validation="required" class="form-control">
            </td>
            <td>
                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
{{--                <i class="fa fa-trash btnRemove"></i>--}}
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="5" style="text-align: center">
            لا يوجد بيانات
        </td>
    </tr>
@endif
