@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.nationalities")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL))
        {!! addButton(route("nationalities.create") ,helperTrans("ingaz.nationality")) !!}
    @endif
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.logo")}}</th>
            <th>{{helperTrans("ingaz.nationality_name")}}</th>
            <th>{{helperTrans("ingaz.phone_code")}}</th>
            <th>{{helperTrans("ingaz.price")}}</th>
            <th>تكلفة نقل الخدمات</th>
            <th>تكلفة التأجير</th>
            <th>الراتب الشهري</th>
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
                {"data": "logo", orderable: false, searchable: false},
                {"data": "sub_title",orderable: true,searchable: true},
                {"data": "phone_code",orderable: true,searchable: true},
                {
                    "data": "price",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' SAR' : 'لم يتم توحيد سعر';
                    }
                },
                {
                    "data": "service_transportation_cost",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' SAR' : '-';
                    }
                },
                {
                    "data": "rental_cost",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' SAR' : '-';
                    }
                },
                {
                    "data": "monthly_salary",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' SAR' : '-';
                    }
                },
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("nationalities.index")}}", columns);
        });
    </script>
@endsection
