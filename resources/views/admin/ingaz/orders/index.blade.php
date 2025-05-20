@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
{{helperTrans("ingaz.orders")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
    <div class="row mb-3">
        <div class="col-sm-2">
            <a href="{{ route('orders.export.pdf') }}" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
            </a>
        </div>
        <div class="col-sm-2">
            <a href="{{ route('orders.export.excel') }}" class="btn btn-success">
                <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
            </a>
        </div>
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{helperTrans("ingaz.order_code")}}</th>
                <th>{{helperTrans("ingaz.client")}}</th>
                <th>{{helperTrans("ingaz.phone")}}</th>
                <th>{{helperTrans("ingaz.nationality")}}</th>
                <th>{{helperTrans("ingaz.passport_num")}}</th>
                <th>{{helperTrans("ingaz.maid")}}</th>
                <th>{{helperTrans("ingaz.deliver_status")}}</th>
                <th>{{helperTrans("ingaz.order job type")}}</th>
                <th>{{helperTrans("ingaz.status")}}</th>
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
            { "data": 'id', name: 'id', orderable: false, searchable: false },
            { "data": "order_code", orderable: true, searchable: true },
            { "data": "user_id", orderable: true, searchable: true },
            { "data": "phone", orderable: true, searchable: true },
            { "data": "nationality_name", orderable: true, searchable: true },
            { "data": "maid_passport_num", orderable: true, searchable: true },
            { "data": "maid_id", orderable: false, searchable: false },
            { "data": "deliver_status", orderable: false, searchable: false },
            { "data": "job_type_id", orderable: true, searchable: true },
            { "data": "status", orderable: true, searchable: true },
            { "data": "actions", orderable: false, searchable: false }
        ];
        @php
            $status = request()->input('status');
            $url = route('orders.index', ['status' => $status]);
        @endphp
        const url = "{{ $url }}";
        showDataTable(url, columns);
    });
</script>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $(document).ready(function () {
                // Event delegation using $('body').on
                $('body').on('click', '.addNewContract', function (e) {
                    e.preventDefault(); // Prevent the default action
                    var orderId = $(this).data('id'); // Get the data-id attribute

                    if (orderId) {
                        window.location.href = '{{ route('RecruitmentContract.create') }}?orderId=' + orderId;
                    } else {
                        console.error('data-id attribute is missing!'); // Log an error if data-id is missing
                    }
                });
            });
        });
    </script>
@endpush
