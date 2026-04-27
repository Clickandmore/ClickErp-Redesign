<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('put')

    <input type="hidden" name="employee_id" value="{{@$employee_id}}">

    <div class="col-md-3">
        <label for="TextInput" class="form-label"> {{ __("hr.installment_value") }}  </label>
        <input type="text" name="value" value="{{ $obj->value }}" id="total_amount" class="form-control" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="TextInput" class="form-label"> {{ __("hr.payment_sol_date") }}  </label>
        <input type="date" name="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="TextInput" class="form-label"> {{ __("hr.notes") }}  </label>
        <textarea name="notes" class="form-control" cols="15" rows="4">{{ $obj->notes }}</textarea>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.payment method") }} </label>
        <select class="form-control show-tick ms" name="payment_method"
                data-validation="required" id="payment_method" >
                {!! getEnumData($advance_method , $obj->payment_method->value) !!}
        </select>
    </div>
    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.delivery_method") }} </label>
        <select class="form-control show-tick ms" name="delivery_method"
                data-validation="required" id="delivery_method" >
            {!! getEnumData($delivery_methods , $obj->delivery_method->value) !!}

        </select>
    </div>
    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr.file") }}</label>
        <input type="file"  name="file" data class="dropify"  data-default-file="{{showFile($obj->file)}}">
    </div>
    <div id="table" class="col-12" @if ($obj->payment_method->value == "debt_payments") style="display: none;" @endif >
        @if ($obj->payment_method->value == "debt_payments")
            <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
                <thead>
                    <tr>
                        <th>{{ __("hr.date") }}</th>
                        <th>{{ __("hr.installment") }} </th>
                        <th>{{ __("hr.actions") }}</th>
                    </tr>
                </thead>
                <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                    @foreach ($obj->advanceValues as $advanceValues)
                        <tr class="y-group">
                            <td>
                                <input type="date" value="{{ $advanceValues->date }}" name="date_value[]" class="form-control">
                            </td>
                            <td>
                                <input type="number" value="{{ $advanceValues->amount }}" oninput="" name="amount_value[]" class="form-control amount">
                            </td>
                            <td>
                                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                                <i class="fa fa-trash btnRemove"></i>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="col-12 mb-2" id="field" @if ($obj->payment_method->value == "monthly_installment") style="display: none;" @endif>
        <div class="row">
            <div class="col-md-4">
                <label for="TextInput" class="form-label"> {{ __("hr.from_date") }}  </label>
                <input type="month" name="from_date" id="from_date" value="{{ \Carbon\Carbon::parse($obj->from_date)->format('Y-m') }}" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="TextInput" class="form-label"> {{ __("hr.to_date") }}  </label>
                <input type="month" name="to_date" oninput="calculateOneValue()" id="to_date" value="{{ \Carbon\Carbon::parse($obj->to_date)->format('Y-m') }}" class="form-control" data-validation="required">
            </div>

            <div class="col-md-4">
                <label for="TextInput" class="form-label"> {{ __("hr.installment") }}  </label>
                <input type="text" name="amount" oninput="calculateOneValue()" id="amount" value="{{ @$obj->advanceValues->first()->amount }}" class="form-control" data-validation="required">
            </div>
        </div>
    </div>

    <div class="col-12 modal-footer">

        {!! submitButton( $updateRoute) !!}
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

            amount.value = total / months;
        } else {
            amount.value = total;
        }
    }

</script>
