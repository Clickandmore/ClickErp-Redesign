@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')
    <div class="row">

        <div class="col-4 my-2">
            <label for="TextInput" class="form-label">{{ __("hr::hr._date"). ' ' .__("hr::hr.report") }}</label>
            <input class="form-control" type="date" name="date" id="date">
        </div>

    </div>


    {!! addButton($createRoute , $addButtonText) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.title") }}</th>
            <th>{{ __("hr::hr.date") }}</th>
            <th>{{ __("hr::hr.file") }}</th>
            <th>{{ __('hr::hr.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "file", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "الكل"]],
                "processing": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function (d) {
                        d.date = $('#date').val();
                    }
                },
                "columns": columns,
            });

            $('#employeeId').on('change', function () {
                table.column(1).search($('#employeeId').val()).draw();
            });
            $('#date').on('change', function () {
                var searchValue = $(this).val();
                table.column(2).search(searchValue).draw();
            });
        });
    </script>

@endsection
