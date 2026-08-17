@extends('admin.layout.indexs.index')
@section('title')
    {{helperTrans('ingaz.Roles')}}
@endsection
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.Roles")}}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
            </h4>
            {{--            @can('اضافة صلاحية')--}}
            {{--                {!! addButton(helperTrans('admin.role')) !!}--}}
            <div class="justify-content-end">
                <a href="{{route('roles.create')}}" class="btn btn-rounded btn-outline-info"
                   title="{{helperTrans('ingaz.role')}}">
                    <span class="btn-icon-start text-info"><i
                            class="fa fa-plus color-info"></i></span>{{helperTrans('ingaz.add role')}}
                </a>
            </div>
            {{--            @endcan--}}
        </div>
        <div class="card-body">
                        
                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{helperTrans('ingaz.name')}}</th>
                        <th>{{helperTrans('ingaz.permission display_name')}}</th>
                        <th>{{helperTrans('ingaz.permission description')}}</th>
                        <th>{{helperTrans('ingaz.Actions')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                    
        </div>
    </div>

@endsection
@section('js')
    <script>
        $(document).ready(function () {
            var columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name',orderable: false,searchable: true},
                {data: 'display_name', name: 'display_name',orderable: false,searchable: true},
                {data: 'description', name: 'description',orderable: false,searchable: true},
                {data: 'actions', name: 'actions',orderable: false,searchable: false},
            ];
            showDataTable("{{route("roles.index")}}", columns);
        });
    </script>
@endsection

