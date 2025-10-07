@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
{{helperTrans("ingaz.clients")}}
@endsection
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
    <div class="mb-3">
        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE))
        <a href="{{ route('clients.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> {{helperTrans("ingaz.add_client")}}
        </a>
        @endif
        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF))
        <a href="{{ route('clients.export.pdf') }}" class="btn btn-danger" target="_blank">
            <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
        </a>
        @endif
        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL))
        <a href="{{ route('clients.export.excel') }}" class="btn btn-success">
            <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
        </a>
        @endif


    </div>

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
    });
</script>
@endsection
