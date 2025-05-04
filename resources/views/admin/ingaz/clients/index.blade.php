@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
{{helperTrans("ingaz.clients")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
    <div class="mb-3">
        <a href="{{ route('clients.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> {{helperTrans("ingaz.add_client")}}
        </a>
        <a href="{{ route('clients.export.pdf') }}" class="btn btn-danger" target="_blank">
            <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
        </a>
        <a href="{{ route('clients.export.excel') }}" class="btn btn-success">
            <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
        </a>
    </div>

    <p>{{helperTrans('ingaz.Toggle account status by clicking on an active or blocked button')}}</p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>{{helperTrans("ingaz.name")}}</th>
                <th>{{helperTrans("ingaz.phone")}}</th>
                <th>{{helperTrans("ingaz.is_blocked")}}</th>
                <th>{{helperTrans("ingaz.created_at")}}</th>
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: true, searchable: false},
                {"data": "name", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "is_blocked", orderable: false, searchable: false},
                {"data": "created_at", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("clients.index")}}", columns);
        });
        // ِChange Status Using Ajax
        $(document).on('click', '.update-status', function (event) {
            var rowId = $(this).data('id');
            var url = "{{ route('clients.update', ':id') }}";
            url = url.replace(':id', rowId);
            $.ajax({
                type: 'PUT',
                url: url,
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
