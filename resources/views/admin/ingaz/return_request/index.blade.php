@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.orders")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_VIEW))

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.client")}}</th>
            <th>{{helperTrans("ingaz.phone")}}</th>
            <th>{{helperTrans("ingaz.maid")}}</th>
            <th>{{helperTrans("web.file report")}}</th>
            <th>{{helperTrans("ingaz.status")}}</th>
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
                {"data": "user_id", orderable: true, searchable: true},
                {"data": "phone",orderable: true,searchable: true},
                {"data": "maid_id", orderable: false, searchable: false},
                {"data": "file", orderable: true, searchable: false},
                {"data": "status", orderable: false, searchable: false},
            ];
            @php
//                $status = request()->input('status');
                $url = route('return_requests.index');
            @endphp
            const url = "{{ $url }}";
            showDataTable(url, columns);
        });

        $(document).on('click', '.update-status', function (event) {
            var rowId = $(this).data('id');
            var status = $(this).data('value');
            var url = "{{ route('return_requests.show', ':id') }}";
            url = url.replace(':id', rowId);
            $.ajax({
                type: 'Get',
                url: url,
                data: {status:status},
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تغيير الحالة بنجاح!',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    }
                    else {
                        alert('فشل في تحديث الحالة.');
                    }
                }
            });
        });

    </script>
@endsection
