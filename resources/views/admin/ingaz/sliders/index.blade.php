@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.sliders")}}
@endsection

@section('content')

    @if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_VIEW))

        @if (@AdminGuardHasPerm(\App\PermKeys::SLIDERS_CONTROL))
            {!! addButton(route("sliders.create") ,helperTrans("ingaz.slider")) !!}
        @endif
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>#</th>
                <th>{{helperTrans("ingaz.image")}}</th>
                <th>{{helperTrans("ingaz.title")}}</th>
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
                {"data": "image", orderable: true, searchable: true},
                {"data": "title", orderable: true, searchable: true},
                // {"data": "description", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("sliders.index")}}", columns);
        });
</script>
@endsection
