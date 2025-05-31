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

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{helperTrans("ingaz.from_date")}}</label>
                    <input type="date" class="form-control" name="from_date" id="from_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{helperTrans("ingaz.to_date")}}</label>
                    <input type="date" class="form-control" name="to_date" id="to_date">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.status")}}</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">{{helperTrans("ingaz.all")}}</option>
                        @foreach(\App\Enums\ingaz\OrderEnum::cases() as $status)
                            <option value="{{ $status->value }}">{{ getEnumValue('orders_statuses', $status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.job_type")}}</label>
                    <select class="form-select" name="job_type" id="job_type">
                        <option value="">{{helperTrans("ingaz.all")}}</option>
                        @foreach(\App\Models\ingaz\JobType::all() as $jobType)
                            <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.responsible_employee")}}</label>
                    <select class="form-select" name="admin_id" id="admin_id">
                        <option value="">{{helperTrans("ingaz.all")}}</option>
                        @foreach(\App\Models\auth\Admin::all() as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Search Fields -->
                <div class="col-md-4">
                    <label class="form-label">{{helperTrans("ingaz.client_name")}}</label>
                    <input type="text" class="form-control" name="client_name" id="client_name" placeholder="{{helperTrans('ingaz.search_by_client_name')}}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{helperTrans("ingaz.phone")}}</label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="{{helperTrans('ingaz.search_by_phone')}}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{helperTrans("ingaz.passport_num")}}</label>
                    <input type="text" class="form-control" name="passport_num" id="passport_num" placeholder="{{helperTrans('ingaz.search_by_passport')}}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{{helperTrans("ingaz.filter")}}</button>
                    <button type="button" class="btn btn-secondary" id="resetFilter">{{helperTrans("ingaz.reset")}}</button>
                </div>
            </form>
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
                <th>{{helperTrans("ingaz.order_job_type")}}</th>
                <th>{{helperTrans("ingaz.status")}}</th>
                <th>{{helperTrans("ingaz.contract_status")}}</th>
                <th>{{helperTrans("ingaz.order_date")}}</th>
                <th>{{helperTrans("ingaz.responsible_employee")}}</th>
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
            { "data": "contract_status", orderable: true, searchable: true },
            { "data": "created_at", orderable: true, searchable: true },
            { "data": "admin_name", orderable: true, searchable: true },
            { "data": "actions", orderable: false, searchable: false }
        ];

        let dataTable = $('#dataTableObject').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('orders.index') }}",
                data: function(d) {
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.status = $('#status').val();
                    d.job_type = $('#job_type').val();
                    d.admin_id = $('#admin_id').val();
                    d.client_name = $('#client_name').val();
                    d.phone = $('#phone').val();
                    d.passport_num = $('#passport_num').val();
                }
            },
            columns: columns,
            language: {
                url: "{{ asset('admin/assets/js/dataTables.arabic.json') }}",
                search: "{{helperTrans('ingaz.search')}}",
                lengthMenu: "{{helperTrans('ingaz.show')}} _MENU_ {{helperTrans('ingaz.records')}}",
                info: "{{helperTrans('ingaz.showing')}} _START_ {{helperTrans('ingaz.to')}} _END_ {{helperTrans('ingaz.of')}} _TOTAL_ {{helperTrans('ingaz.records')}}",
                infoEmpty: "{{helperTrans('ingaz.no_records')}}",
                infoFiltered: "({{helperTrans('ingaz.filtered_from')}} _MAX_ {{helperTrans('ingaz.records')}})",
                zeroRecords: "{{helperTrans('ingaz.no_matching_records')}}",
                paginate: {
                    first: "{{helperTrans('ingaz.first')}}",
                    last: "{{helperTrans('ingaz.last')}}",
                    next: "{{helperTrans('ingaz.next')}}",
                    previous: "{{helperTrans('ingaz.previous')}}"
                }
            }
        });

        // Handle filter form submission
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });

        // Handle reset button
        $('#resetFilter').on('click', function() {
            $('#filterForm')[0].reset();
            dataTable.ajax.reload();
        });
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
