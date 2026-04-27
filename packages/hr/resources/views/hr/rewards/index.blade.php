@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.rewards')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("hr::hr.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr_rewards-create')) --}}
{!! addButton(route("rewards.create") , __('hr::hr.reward')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.reward_name") }}</th>
            <th>{{ __("hr::hr.calculation method") }}</th>
            <th>{{ __("hr::hr.value") }}</th>
            <th>{{ __("hr::hr.status") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    @include("hr::hr.rewards._js_rewards")
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "title", orderable: false, searchable: true},
                {"data": "typy_name", orderable: false, searchable: false},
                {"data": "value", orderable: false, searchable: false},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("rewards.index")}}", columns);
        });
    </script>
@endsection
