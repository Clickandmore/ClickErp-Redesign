<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
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
            showDataTable("{{route("employeePermissions.index")}}", columns);
        });
    </script>
