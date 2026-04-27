@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.officialVacations')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_official_vacation-create')) --}}
{!! addButton(route("officialVacations.create") , __('hr::hr.officialVacations')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.name") }}</th>
            <th> {{ __("hr::hr.from_date") }}</th>
            <th> {{ __("hr::hr.to_date") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
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
                {"data": "name", orderable: false, searchable: false},
                {"data": "from_date", orderable: false, searchable: false},
                {"data": "to_date", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("officialVacations.index")}}", columns);
        });




    </script>
@endsection
