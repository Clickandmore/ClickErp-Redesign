<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script>
    $(document).ready(function () {
        let columns = [
            {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {"data": "employee_id", orderable: false, searchable: false},
            {"data": "vacation_id", orderable: false, searchable: false},
            {"data": "value", orderable: false, searchable: false},
            {"data": "from_date", orderable: false, searchable: false},
            {"data": "to_date", orderable: false, searchable: false},
            {"data": "days", orderable: false, searchable: false},
            {"data": "reason", orderable: false, searchable: false},
            {"data": "status", orderable: false, searchable: false},
            {"data": "vacation_file", orderable: false, searchable: false},
            {"data": "actions", orderable: false, searchable: false}
        ];
        showDataTable("{{route("employeeVacations.index")}}", columns);
    });
</script>

{{--    <script>--}}
{{--        $(document).ajaxComplete(function() {--}}
{{--            $('#employee_id, #vacation_id').on('change', function() {--}}
{{--                var employee_id = $('#employee_id').val();--}}
{{--                var vacation_id = $('#vacation_id').val();--}}

{{--                if (employee_id && vacation_id) {--}}
{{--                    $.ajax({--}}
{{--                        url: '{{ route("employeeVacationBalance.show", ["employeeVacationBalance" => 0]) }}' + '?employee_id=' + employee_id + '&vacation_id=' + vacation_id,--}}
{{--                        type: 'GET',--}}
{{--                        success: function(data) {--}}
{{--                            var remain_days = data.remain_days;--}}
{{--                            $('input[name="value"]').val(remain_days);--}}
{{--                        },--}}
{{--                        error: function(xhr, status, error) {--}}
{{--                            console.log(xhr.responseText);--}}
{{--                        }--}}
{{--                    });--}}
{{--                }--}}
{{--            });--}}
{{--        });--}}
{{--    </script>--}}

<script>
    $(document).ajaxComplete(function () {
        $(".accept-button1, .reject-button1").on("click", function (e) {
            e.preventDefault();
            var id = $(this).data("id");
            var status = $(this).hasClass("accept-button1") ? 1 : 2;

            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: "هل تريد تغيير حالة الموظف؟",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم!',
                cancelButtonText: 'لا'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("ToggleStatusEmployeeVacation.update", ["ToggleStatusEmployeeVacation" => ":id"]) }}'.replace(':id', id),
                        type: 'PUT',
                        data: {
                            status: status,
                        },
                        success: function (response) {
                            console.log(200);
                            handleSuccessResponce(response);
                            if ($('#dataTableObject').length > 0) {
                                $('#dataTableObject').DataTable().ajax.reload();
                            }
                        },
                        error: function (errorObj, errorText, errorThrown) {
                            console.log(500);
                            handleErrorResponce(errorObj);
                        }
                    });
                }
            });
        });
    });
</script>

<script>
    function updateDays() {
        const fromDateInput = document.getElementById("from_date").value;
        const toDateInput = document.getElementById("to_date").value;

        if (fromDateInput && toDateInput) {
            const fromDate = new Date(fromDateInput);
            const toDate = new Date(toDateInput);

            if (toDate >= fromDate) {
                const diffTime = toDate.getTime() - fromDate.getTime();
                const diffDays = Math.ceil(diffTime / (1000 * 3600 * 24)); // Round up to avoid partial days
                document.getElementById("days").value = diffDays + 1;
            }
            else{
                warningToster('يرجي ادخال تواريخ صحيحة');
                document.getElementById("days").value = "";
            }
        }
    }

    $.formUtils.addValidator({
        name: 'lessThan',
        validatorFunction: function (value, $el, config, language, $form) {
            const remainDays = parseFloat($('#value').val());
            const days = parseFloat($('#days').val());
            return !isNaN(remainDays) && !isNaN(days) && Math.abs(days) <= remainDays;
        },
        errorMessage: 'يجب أن يكون الفرق بين التواريخ أقل من الرصيد',
        errorMessageKey: 'badLessThan'
    });
</script>
