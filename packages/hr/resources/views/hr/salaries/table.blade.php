@if ($data)
    @foreach ($data as $index => $item)
    <tr>
        <td>
            <div class="col-12">
                <input type="checkbox" name="checkbox[]" value="1">
            </div>
        </td>
        <td>{{$index + 1 }}</td>
        <td>{{ $item['employee']->name }}
            <input type="hidden" readonly name="employee_id[]" value="{{ $item['employee']->id }}" style="width:50px"></td>
        <td>{{ $item['employee']->departments->title }}</td>
        <td>{{ $item['employee']->salary }}</td>
        <td>{{ $item['attandance_days'] }}
            <input type="hidden" readonly name="attandance_days[]" value="10" style="width:50px">
        </td>
        <td>{{ $item['allowances'] }}
            <input type="hidden" readonly name="allowances[]" value="{{ $item['allowances'] }}" style="width:70px">
        </td>
        <td>{{ $item['sanctions'] }}
            <input type="hidden" readonly name="sanctions[]" value="{{ $item['sanctions'] }}" style="width:70px">
        </td>
        <td>{{ $item['insurances_sum'] }}
            <input type="hidden" readonly name="Insurances[]" value="{{ $item['insurances_sum'] }}" style="width:70px">
        </td>
        <td>{{ $item['taxs'] }}
            <input type="hidden" readonly name="tax[]" value="{{ $item['taxs'] }}" style="width:70px">
        </td>
        <td>{{ $item['delays'] }}
            <input type="hidden" readonly name="delays[]" value="{{ $item['delays'] }}" style="width:70px">
        </td>
        <td>{{ $item['net'] }}
            <input type="hidden" readonly name="final[]" value="{{ $item['net'] }}" style="width:70px">
        </td>
    </tr>
    @endforeach
@else
    <td style="text-align: center">
        {{ __("hr::hr.No Data") }}
    </td>
@endif

