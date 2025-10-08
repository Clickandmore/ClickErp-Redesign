@extends('admin.layout.indexs.index')
@section("style")
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
            <th>{{helperTrans("ingaz.description")}}</th>
            <th>{{helperTrans("ingaz.recruitment_cost")}}</th>
            <th>{{helperTrans("ingaz.service_transportation_cost")}}</th>
            <th>{{helperTrans("ingaz.rental_cost")}}</th>
            <th>{{helperTrans("ingaz.monthly_salary")}}</th>
            <th>{{helperTrans("ingaz.is_show")}}</th>
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
            const currencyLabel = "{{ __('ingaz.currency_sar') }}";
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "logo", orderable: false, searchable: false},
                {"data": "sub_title",orderable: true,searchable: true},
                {"data": "phone_code",orderable: true,searchable: true},
                {"data": "description", orderable: false, searchable: true},
                {
                    "data": "price",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ' + currencyLabel : 'لم يتم توحيد سعر';
                    }
                },
                {
                    "data": "service_transportation_cost",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ' + currencyLabel : '-';
                    }
                },
                {
                    "data": "rental_cost",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ' + currencyLabel : '-';
                    }
                },
                {
                    "data": "monthly_salary",
                    orderable: true,
                    searchable: true,
                    render: function(data) {
                        return data ? parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' ' + currencyLabel : '-';
                    }
                },
                {"data": "is_show", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("nationalities.index")}}", columns);
        });

        $(document).on('click', '.update-status', function () {
            var rowId = $(this).data('id');
            var url = "{{ route('nationalities.show', ':id') }}";
            url = url.replace(':id', rowId);

            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: "{{ helperTrans('ingaz.done successfully') }}",
                            text: "{{ helperTrans('ingaz.status_updated_successfully') }}",
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    } else {
                        alert('فشل في تحديث الحالة.');
                    }
                },
                error: function () {
                    alert('فشل في تحديث الحالة.');
                }
            });
        });
    </script>
@endsection
