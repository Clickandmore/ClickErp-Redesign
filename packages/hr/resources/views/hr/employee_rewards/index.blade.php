@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('content')

    {!! addButton($createRoute , $addButtonText) !!}
    <table class="table" id="dataTableObject">
        <thead class="thead-inverse">
            <tr>
                <th>{{ __('hr::hr.SL') }}</th>
                <th>{{ __("hr::hr.employees") }}</th>
                <th>{{ __("hr::hr.rewards") }}</th>
                <th>{{ __("hr::hr.salary") }}</th>
                <th>{{ __("hr::hr.net_value") }}</th>
                <th>{{ __("hr::hr.exchange_timing") }}</th>
                <th>{{ __("hr::hr.date") }}</th>
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "employee_id", orderable: false, searchable: true},
                {"data": "reward_id", orderable: false, searchable: true},
                {"data": "salary", orderable: false, searchable: true},
                {"data": "net_value", orderable: false, searchable: true},
                {"data": "exchange_timing", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
@endsection
