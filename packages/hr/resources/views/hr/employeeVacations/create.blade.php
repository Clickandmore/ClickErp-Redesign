<form class="row g-3 from-submit-global" method="post" action="{{ route("employeeVacations.store") }}"
      enctype="multipart/form-data">
    @csrf
    <div class="col-4 form-group" id="employee_div">
        <label for="employee_id" class="form-label">{{ __("hr::hr.Employee") }}</label>
        <select class="form-control show-tick ms select2" name="employee_id" id="employee_id"
                data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4 form-group" id="vacation_div">
        <label for="vacation_id" class="form-label">{{ __("hr::hr.the kind of holiday") }}</label>
        {!! addUpButton(route('vacations.create'),'vacation_id','' ,'title',false) !!}
        <select class="form-control show-tick ms" name="vacation_id" id="vacation_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($vacations as $vacation)
                <option value="{{ $vacation->id }}">{{ $vacation->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4 form-group" id="balance_div">
        <label for="value" class="form-label">{{ __("hr::hr.Balance") }}</label>
        <input type="text" id="value" name="value" readonly class="form-control value" data-validation="required">
    </div>

    <div class="col-4 form-group" id="from_date_div">
        <label for="from_date" class="form-label">{{ __("hr::hr.From the date of") }}</label>
        <input type="date" id="from_date" name="from_date" class="form-control" data-validation="required"
               onchange="updateDays()">
    </div>

    <div class="col-4 form-group" id="to_date_div">
        <label for="to_date" class="form-label">{{ __("hr::hr.To date") }}</label>
        <input type="date" id="to_date" name="to_date" class="form-control" data-validation="required"
               onchange="updateDays()">
    </div>

    <div class="col-4 form-group" id="days_div">
        <label for="days" class="form-label">{{ __("hr::hr.Date difference") }}</label>
        <input type="text" id="days" name="days" readonly class="form-control days" data-validation="required,lessThan">
    </div>

    <div class="col-12 form-group" id="reason_div">
        <label for="reason" class="form-label">{{ __("hr::hr.comments") }}</label>
        <textarea rows="3" name="reason" class="form-control"></textarea>
    </div>

    <div class="col-12 form-group" id="file_div">
        <label for="vacation_file" class="form-label">{{ __("hr::hr.vacation_file") }}</label>
        <input type="file" name="vacation_file" class="dropify">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("employeeVacations.store") ) !!}
        {!! closeButton() !!}
    </div>
</form>

<script>
    $(document).ready(function () {
        // Hide form elements initially
        $('#from_date_div, #to_date_div, #days_div, #reason_div,#file_div, button[type="submit"]').hide();

        $('#employee_id').on('change', function () {
            var employee_id = $('#employee_id').val();
            if (employee_id) {
                $.ajax({
                    url: '{{ route("employeeVacationBalance.show", ["employeeVacationBalance" => 0]) }}' + '?employee_id=' + employee_id,
                    type: 'GET',
                    success: function (data) {
                        console.log(data);
                        // Check if vacation_types is present and is an array
                        if (data.vacation_types && Array.isArray(data.vacation_types)) {
                            // Get the vacation select element
                            var $vacationSelect = $('#vacation_id');

                            // Clear existing options except the default one
                            $vacationSelect.find('option:not(:first)').remove();

                            // Append new options dynamically
                            data.vacation_types.forEach(function (vacationType) {
                                if (vacationType.vacation && vacationType.vacation.title) {
                                    $vacationSelect.append(
                                        `<option value="${vacationType.vacation_id}">${vacationType.vacation.title}</option>`
                                    );
                                }
                            });

                            // Trigger a refresh for any styling libraries (optional)
                            $vacationSelect.trigger('change');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });
            }
        });

        $('#employee_id, #vacation_id').on('change', function () {
            var employee_id = $('#employee_id').val();
            var vacation_id = $('#vacation_id').val();

            if (employee_id && vacation_id) {
                $.ajax({
                    url: '{{ route("employeeVacationBalance.show", ["employeeVacationBalance" => 0]) }}' + '?employee_id=' + employee_id + '&vacation_id=' + vacation_id,
                    type: 'GET',
                    success: function (data) {
                        if (data.error) {
                            warningToster(data.error);
                            // Swal.fire({
                            //     title: 'تحذير',
                            //     text: data.error,
                            //     icon: 'warning',
                            //     showCancelButton: false,
                            //     confirmButtonColor: '#3085d6',
                            //     cancelButtonColor: '#d33',
                            //     confirmButtonText: 'فهمت',
                            // });
                            // Hide elements on error
                            $('#from_date_div, #to_date_div, #days_div, #reason_div, #file_div, button[type="submit"]').hide();
                            $('input[name="value"]').val(0);
                        } else {
                            var remain_days = data.remain_days;
                            $('input[name="value"]').val(remain_days);

                            // Show or hide elements based on remaining days
                            if (remain_days > 0) {
                                $('#from_date_div, #to_date_div, #days_div, #reason_div, #file_div, button[type="submit"]').show();
                            } else {
                                $('#from_date_div, #to_date_div, #days_div, #reason_div, #file_div, button[type="submit"]').hide();
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });
</script>
