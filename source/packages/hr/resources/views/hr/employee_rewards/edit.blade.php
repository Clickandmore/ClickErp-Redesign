<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('put')

    <div class="col-md-4">
        <label for="employee_id" class="form-label">{{ __("hr::hr.employees") }}</label>
        <select name="employee_id" class="form-control" id="employee_id" data-validation="required">
            @foreach ($employees as $item)
                <option value="{{ $item->id }}" data-salary="{{ $item->salary }}"
                    {{ $obj->employee_id == $item->id ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="salary" class="form-label">{{ __("hr::hr.salary") }}</label>
        <input type="text" name="salary" class="form-control" id="salary"
               value="{{ $obj->salary }}" data-validation="required" readonly>
    </div>

    <div class="col-md-4">
        <label for="reward_id" class="form-label">{{ __("hr::hr.rewards") }}</label>
        <select name="reward_id" class="form-control" id="reward_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($rewards as $item)
                <option value="{{ $item->id }}" data-value="{{ $item->value }}" data-type="{{ $item->type }}"
                    {{ $obj->reward_id == $item->id ? 'selected' : '' }}>
                    {{ $item->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="exchange_timing" class="form-label">{{ __("hr::hr.exchange_timing") }}</label>
        <select name="exchange_timing" class="form-control" id="exchange_timing" data-validation="required">
            {!! getEnumData($exchange_timing, $obj->exchange_timing->value) !!}
        </select>
    </div>

    <div class="col-md-6">
        <label for="date" class="form-label">{{ __("hr::hr.date") }}</label>
        <input type="date" class="form-control" id="date" name="date"
               value="{{ $obj->date }}" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="value" class="form-label" id="valueLabel">{{ __("hr::hr.value") }}</label>
        <input type="text" class="form-control" id="value" name="value"
               value="{{ $obj->value }}" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="net_value" class="form-label">{{ __("hr::hr.net_value") }}</label>
        <input type="text" class="form-control" id="net_value" name="net_value"
               value="{{ $obj->net_value }}" readonly data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
   </div>

</form>
<script>
     $(document).ajaxComplete(function() {
            $('#employee_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var salary = parseFloat(selectedOption.data('salary')) || 0;
                $('#salary').val(salary.toFixed(2));
                updateNetValue();
            });

            $('#reward_id').on('change', function() {
                updateNetValue();
            });

            $('#value').on('input', function() {
                updateNetValue(false);
            });

            function updateNetValue(resetValue = true) {
                var selectedOption = $('#reward_id').find('option:selected');
                var rewardValue = resetValue ? (parseFloat(selectedOption.data('value')) || 0) : parseFloat($('#value').val()) || 0;
                var rewardType = parseInt(selectedOption.data('type')) || 0;
                var salary = parseFloat($('#salary').val()) || 0;
                var netValue = 0;
                var labelText = "{{ __('hr::hr.value') }}";

                if (rewardType === 1) {
                    netValue = (salary / 30) * rewardValue;
                    labelText = "{{ __('hr::hr.day') }}";
                } else if (rewardType === 2) {
                    netValue = rewardValue;
                    labelText = "{{ __('hr::hr.value') }}";
                } else if (rewardType === 3) {
                    netValue = (salary * rewardValue) / 100;
                    labelText = "{{ __('hr::hr.percentage') }}";
                }

                if (resetValue) {
                    $('#value').val(rewardValue.toFixed(2));
                }
                $('#net_value').val((netValue).toFixed(2));
                $('#valueLabel').text(labelText);
            }

            $('#exchange_timing').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue === 'with_salary') {
                    $('#date').attr('type', 'month').val('');
                } else if (selectedValue === 'specific_day') {
                    $('#date').attr('type', 'date').val('');
                }
            });
        });
</script>
