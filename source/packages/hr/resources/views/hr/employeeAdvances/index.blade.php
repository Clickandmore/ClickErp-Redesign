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

    {!! addButton($createRoute , $addButtonText, 'btn-success',false) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }}</th>
            <th>{{ __("hr::hr.payment method") }}</th>
            <th>{{ __("hr::hr.value") }} </th>
            <th> {{ __("hr::hr.date") }}</th>
            <th> {{ __("hr::hr.file") }}</th>
            <th class="notPrintable"> {{ __("hr::hr.Installments and payments") }}</th>
            <th class="notPrintable">{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    @include("hr::hr.employees.advances._js_allowance")
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "employee_id", orderable: false, searchable: false},
                {"data": "payment_method", orderable: false, searchable: true},
                {"data": "value", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "file", orderable: false, searchable: true},
                {"data": "payments", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);


        });
    </script>
@endsection
