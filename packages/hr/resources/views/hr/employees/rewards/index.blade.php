@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('content')

    <table class="table" id="dataTableObject">
        <thead class="thead-inverse">
            <tr>
                <th>{{ __('hr.SL') }}</th>
                <th>{{ __("hr.rewards") }}</th>
                <th>{{ __("hr.net_value") }}</th>
                <th>{{ __("hr.exchange_timing") }}</th>
                <th>{{ __("hr.date") }}</th>
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
                {"data": "reward_id", orderable: false, searchable: true},
                {"data": "net_value", orderable: false, searchable: true},
                {"data": "exchange_timing", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
@endsection
