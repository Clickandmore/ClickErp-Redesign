@extends('admin.layout.indexs.index')

@section('page-title')
    المطارات
@endsection

@section('content')
    {!! addButton(route('airports.create'), 'مطار') !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>اسم المطار</th>
            <th>الدولة</th>
            <th>المدينة</th>
            <th>{{ helperTrans('ingaz.created_at') }}</th>
            <th>{{ helperTrans('ingaz.actions') }}</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "name", orderable: true, searchable: true},
                {"data": "country_name", orderable: false, searchable: false},
                {"data": "city_name", orderable: false, searchable: false},
                {"data": "created_at", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];

            showDataTable("{{ route('airports.index') }}", columns);
        });
    </script>
@endsection
