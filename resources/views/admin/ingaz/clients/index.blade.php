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
    $(document).ready(function() {
        $('#dataTableObject').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('clients.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'phone', name: 'phone', orderable: false},
                {data: 'is_blocked', name: 'is_blocked', orderable: false},
                {data: 'created_at', name: 'created_at'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[1, 'asc']], // Order by name column by default
            language: {
                url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
            }
        });

        // Handle status update
        $(document).on('click', '.update-status', function() {
            var rowId = $(this).data('id');
            var url = "{{ route('clients.update', ':id') }}";
            url = url.replace(':id', rowId);

            $.ajax({
                type: 'PUT',
                url: url,
                success: function(response) {
                    if (response.success) {
                        $('#dataTableObject').DataTable().ajax.reload();
                        toastr.success('تم تحديث الحالة بنجاح');
                    } else {
                        toastr.error('حدث خطأ أثناء تحديث الحالة');
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء تحديث الحالة');
                }
            });
        });
    });
</script>
@endsection
