@extends('nami.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{($bladeTitle)}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{($bladeTitle)}}</li>
@endsection
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">{{$bladeTitle}} &nbsp;
                </div>
                <div class="card-body tableResponsive">

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                        <thead class="thead-inverse">
                        <tr>
                            <th>{{ __('crm.SL') }}</th>
                            <th>{{ __("crm.name") }} </th>
                            <th>{{ __("crm.email") }} </th>
                            <th>{{__("crm.message")}}</th>
                            <th>{{__("hr.actions")}}</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
<script>
    $(document).ready(function () {
        let columns = [
            {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {"data": "name", orderable: false, searchable: false},
            {"data": "email", orderable: false, searchable: false},
            {"data": "message", orderable: false, searchable: false},
            {"data": "actions", orderable: false, searchable: false}
        ];
        showDataTable("{{route("crm.messages-reports.index")}}", columns);
    });
</script>
@endsection
