@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__("auth.users")}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

    @if(@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))

        @if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))
            {!! addButton(route("admins.create") , __("auth.admin")) !!}
        @endif
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>{{ __('auth.SL') }}</th>
                <th>{{__("auth.name")}}</th>
                <th>{{helperTrans("ingaz.admin_type")}}</th>
                <th>{{__("auth.phone")}}</th>
                <th>{{__("auth.email")}}</th>
                <th>{{helperTrans("ingaz.sex_type")}}</th>
                <th>{{__("auth.actions")}}</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

    @else
        @include('components.no_permissions')
    @endif

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "name", orderable: true, searchable: true},
                {"data": "admin_type", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "email", orderable: true, searchable: true},
                {"data": "sex_type", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("admins.index")}}", columns);
        });
    </script>

    <script>
        $(document).ajaxComplete(function () {
            $('.all').click(function () {
                $(this).closest('div').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
            });
        });
    </script>
@endsection
