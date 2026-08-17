<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
{{--    <div class="col-md-3">--}}
{{--        <label for="TextInput" class="form-label"> {{ __("hr.employees") }}  </label>--}}
{{--        <select class="form-control show-tick ms" name="employee_id" data-validation="required">--}}
{{--            <option value=""> {{ __("hr.Choose") }}</option>--}}
{{--            @foreach ($employees as $employee)--}}
{{--                <option value="{{ $employee->id }}">{{ $employee->name }}</option>--}}
{{--            @endforeach--}}
{{--        </select>--}}
{{--    </div>--}}

    <input type="hidden" name="employee_id" value="{{@$employee_id}}">

    <div class="col-md-3">
        <label for="total_amount" class="form-label"> {{ __("hr.installment_value") }}  </label>
        <input type="text"  name="value" class="form-control " id="total_amount" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="date" class="form-label"> {{ __("hr.payment_sol_date") }}  </label>
        <input type="date" id="date" name="date" value="{{date('Y-m-d')}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="notes" class="form-label"> {{ __("hr.notes") }}  </label>
        <textarea name="notes" id="notes" class="form-control" cols="15" rows="3"></textarea>
    </div>

    <div class="col-md-6">
        <label for="payment_method" class="form-label"> {{ __("hr.payment method") }} </label>
        <select class="form-control show-tick ms" name="payment_method"
                data-validation="required" id="payment_method" >
                {!! getEnumData($advance_method , "advance_method") !!}
        </select>
    </div>
    <div class="col-md-6">
        <label for="delivery_method" class="form-label"> {{ __("hr.delivery_method") }} </label>
        <select class="form-control show-tick ms" name="delivery_method"
                data-validation="required" id="delivery_method" >
            {!! getEnumData($delivery_methods , "delivery_methods") !!}

        </select>
    </div>

    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr.file") }}</label>
        <input type="file"  name="file" class="dropify">
    </div>

    <div id="table" class="col-12" style="display: none;">
        <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th>{{ __("hr.date") }}</th>
                <th>{{ __("hr.installment") }} </th>
                <th>{{ __("hr.actions") }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                <tr class="y-group">
                    <td>
                        <input type="date" min="{{date('Y-m-d', strtotime('+1 day'))}}" name="date_value[]" class="form-control">
                    </td>
                    <td>
                        <input type="number"  name="amount_value[]" oninput="" class="form-control amount "  >
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash btnRemove"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="col-12 mb-2" id="field" style="display: none;">
        <div class="row">
            <div class="col-md-4">
                <label for="TextInput"  class="form-label"> {{ __("hr.from_date") }}  </label>
                <input type="month" min="{{date('Y-m')}}" oninput="calculateOneValue()" name="from_date" id="from_date" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="TextInput" class="form-label"> {{ __("hr.to_date") }}  </label>
                <input type="month" min="{{date('Y-m')}}" oninput="calculateOneValue()" name="to_date"  id="to_date" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="TextInput" class="form-label"> {{ __("hr.installment") }}  </label>
                <input type="text" name="amount" id="amount" class="form-control" data-validation="required">
            </div>
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( $storeRoute) !!}
        {!! closeButton() !!}

    </div>

</form>


<script>
    function calculateSum() {
        let sum = 0;
        $('.amount').each(function () {
            let val = parseFloat($(this).val());
            if (!isNaN(val)) {
                sum += val;
            }
        });
        $('#total_amount').val(sum);
    }
    function calculateOneValue() {
        const fromDate = document.getElementById('from_date').value;
        const toDate = document.getElementById('to_date').value;
        const total = document.getElementById('total_amount').value;
        const amount = document.getElementById('amount');

        if (fromDate && toDate) {
            const from = new Date(fromDate);
            const to = new Date(toDate);

            let months;
            months = (to.getFullYear() - from.getFullYear()) * 12;
            months -= from.getMonth();
            months += to.getMonth();
            // alert(months);
            ++months;

            // Ensure that the difference is not negative
            if (months < 0) {
                months = 0;
            }

            amount.value = Math.round((total / months) * 100) / 100 ;
        } else {
            amount.value = total;
        }
    }
</script>
