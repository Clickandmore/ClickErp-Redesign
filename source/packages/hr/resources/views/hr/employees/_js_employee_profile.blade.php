    @php
        if(isset($employee)){
            $id = $employee->id;
        }else {
            $id = null;
        }
    @endphp
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    {{-- vacations --}}
    <script>
        $(document).ready(function () {
            let userId = {{ $id }}; // Ensure $id is passed from the Blade template
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
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route('employeeVacations.index') }}" + "?user_id=" + userId, columns, '_vactions', 5, false, false);
        });
    </script>

    {{-- advances --}}
     @include("hr.employees.advances._js_allowance")
     <script>
         $(document).ready(function () {
            let userId = {{ $id }}; // Ensure $id is passed from the Blade template
             let columns = [
                 {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                 {"data": "payment_method", orderable: false, searchable: true},
                 {"data": "value", orderable: false, searchable: true},
                 {"data": "date", orderable: false, searchable: true},
                 {"data": "file", orderable: false, searchable: true},
                 {"data": "payments", orderable: false, searchable: true},
                 {"data": "actions", orderable: false, searchable: false}
             ];
             showDataTable("{{ route('employee-advances.index') }}" + "?user_id=" + userId, columns, '_advances', 5, false, false);


         });
     </script>

    {{-- permission --}}
    <script>
        $(document).ready(function () {
            let userId = {{ $id }}; // Ensure $id is passed from the Blade template
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "employee_id", orderable: false, searchable: false},
                {"data": "permission_id", orderable: false, searchable: false},
                {"data": "date", orderable: false, searchable: false},
                {"data": "time", orderable: false, searchable: false},
                {"data": "reason", orderable: false, searchable: false},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route('employeePermissions.index')}}" + "?user_id=" + userId, columns, '_permissions', 5, false, false);
        });
    </script>

    {{-- daliy work --}}
    <script>
        $(document).ready(function () {
            let userId = {{ $id }};
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: false, searchable: false},
                {"data": "date", orderable: false, searchable: false},
                {"data": "file", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route('daily-work-reports.index')}}" + "?user_id=" + userId, columns, '_daily_work_reports', 5, false, false);
        });
    </script>

    {{-- tasks --}}
    <script>
        $(document).ready(function () {
            let userId = {{ $id }};
            let columns = [{
                "data": 'DT_RowIndex', name: 'DT_RowIndex',orderable: false,searchable: false},
                {"data": "title",orderable: false,searchable: true},
                {"data": "end_date",orderable: false,searchable: true},
                {"data": "file",orderable: false,searchable: true},
                {"data": "replies",orderable: false,searchable: false},
                {"data": "actions",orderable: false,searchable: false}
            ];
            showDataTable("{{ route('employee.my-tasks.index') }}" + "?user_id=" + userId, columns, '_tasks', 5, false, false);
        });

    </script>

    {{-- rewards --}}
    <script>
        $(document).ready(function () {
            let userId = {{ $id }};
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "reward_id", orderable: false, searchable: true},
                {"data": "salary", orderable: false, searchable: true},
                {"data": "net_value", orderable: false, searchable: true},
                {"data": "exchange_timing", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ route('employee-rewards.index') }}" + "?user_id=" + userId, columns, '_rewards', 5, false, false);
        });
    </script>

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
                                if ($('#dataTableObject').length > 0 ) {
                                    $('#dataTableObject').DataTable().ajax.reload();
                                }
                            },
                            error: function (errorObj, errorText, errorThrown) {
                                console.log(errorObj);
                                validToster('', errorObj.responseJSON.data)
                                // handleErrorResponce(errorObj);
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script>
        function updateDays() {
            const from_date = new Date(document.getElementById("from_date").value);
            const to_date = new Date(document.getElementById("to_date").value);
            if(document.getElementById("from_date").value != "" && document.getElementById("to_date").value != ""){
                console.log(document.getElementById("from_date").value)
                console.log(document.getElementById("to_date").value)
                const diff_time = to_date.getTime() - from_date.getTime();
                const diff_days = diff_time / (1000 * 3600 * 24);
                if(diff_days + 1 > $('#value').val()){
                    Swal.fire({
                        title: 'خطأ',
                        text: "رصيد الاجازات غير كافي لعدد أيام الاجازة",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'فهمت',
                    });
                }
                if(diff_days + 1 > 0){
                    document.getElementById("days").value = diff_days + 1;
                }
                else{
                    Swal.fire({
                        title: 'خطأ',
                        text: "يجب ان يكون تاريخ نهاية الاجازة بعد تاريخ البدء",
                        icon: 'warning',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'فهمت',
                    });
                    document.getElementById("days").value = 0;
                }
            }
        }

        $.formUtils.addValidator({
            name: 'lessThan',
            validatorFunction: function(value, $el, config, language, $form) {
                const remainDays = parseFloat($('#value').val());
                const days = parseFloat($('#days').val());
                return !isNaN(remainDays) && !isNaN(days) && Math.abs(days) <= remainDays;
            },
            errorMessage: 'يجب أن يكون الفرق بين التواريخ أقل من الرصيد',
            errorMessageKey: 'badLessThan'
        });
    </script>
