@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.packages")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
        {!! addButton(route("packages.create") ,helperTrans("ingaz.packages")) !!}
   @endif
{{--    <p>{{helperTrans('ingaz.click in the show button to change appearance status')}}</p>--}}
    <table id="dataTableObject" class="table display dataTable table-hover table-style" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.nationality")}}</th>
            <th>الترتيب</th>
            <th>نوع الإيجار</th>
            <th>المدة</th>
            <th>{{helperTrans("ingaz.original_price")}}</th>
            <th>{{helperTrans("ingaz.price_after_discount")}}</th>
            <th>{{helperTrans("ingaz.price_with_tax")}}</th>
            <th>{{helperTrans("ingaz.days_count")}}</th>
            <th>الظهور</th>
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
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "nationality", orderable: false, searchable: true},
                {"data": "sort_order", orderable: true, searchable: true},
                {"data": "rental_type", orderable: true, searchable: true},
                {"data": "duration", orderable: true, searchable: true},
                {"data": "original_price", orderable: true, searchable: true},
                {"data": "price_after_discount", orderable: true, searchable: true},
                {"data": "price_with_tax", orderable: true, searchable: true},
                {"data": "days_count", orderable: true, searchable: true},
                {"data": "is_show", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("packages.index")}}", columns);
        });

    </script>
@endsection
