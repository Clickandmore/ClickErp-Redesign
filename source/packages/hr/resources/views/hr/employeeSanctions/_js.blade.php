<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "employee_id", orderable: false, searchable: false},
                {"data": "sanction_id", orderable: false, searchable: false},
                {"data": "value", orderable: false, searchable: false},
                {"data": "date", orderable: false, searchable: false},
                {"data": "with_salary", orderable: false, searchable: false},
                {"data": "reason", orderable: false, searchable: false},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("employeeSanctions.index")}}", columns);
        });
    </script>
<script>
    $(document).ajaxComplete(function () {
        $(".accept-button1, .reject-button1").on("click", function (e) {
            e.preventDefault();
            var id = $(this).data("id");
            var table_name = $(this).data("table");
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
                        url: '{{ route("ToggleStatusEmployees.update", ["ToggleStatusEmployee" => ":id"]) }}'.replace(':id', id),
                        type: 'PUT',
                        data: {
                            status: status,
                            table_name: table_name,
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
