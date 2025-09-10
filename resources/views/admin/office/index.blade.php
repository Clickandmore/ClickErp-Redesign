@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.offices")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_VIEW))
        @if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_CONTROL))
            <div class="d-flex mb-3 justify-content-end">
                <a href="{{route("offices.create")}}">
                    <button type="button" class="btn btn-success float-start  mx-1">
                        <i class="fa fa-plus-circle"></i> {{helperTrans("ingaz.add_office")}}
                    </button>
                </a>
            </div>

        @endif
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>#</th>
                <th>{{helperTrans("ingaz.title")}}</th>
                <th>{{helperTrans("ingaz.office_number")}}</th>
                <th>{{helperTrans("ingaz.license_number")}}</th>
                <th>{{helperTrans("ingaz.nationality")}}</th>
                <th>{{helperTrans("ingaz.actions")}}</th>
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "office_number", orderable: true, searchable: true},
                {"data": "license_number", orderable: true, searchable: true},
                {"data": "nationality_id", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("offices.index")}}", columns);
        });
    </script>
@endsection
