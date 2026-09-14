@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle ?? __('hr::hr.employees') }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle ?? __('hr::hr.employees') }}</li>
@endsection
@section('content')

    @if (!empty($isMaidListing))
        <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="fa fa-info-circle mt-1" aria-hidden="true"></i>
            <div>
                <strong>من تظهر في هذه الصفحة؟</strong>
                تعرض هذه القائمة عاملات التأجير اللاتي ما زلن على كفالة الشركة فقط.
                تُستبعد العاملة بعد اكتمال نقل خدماتها أو ترحيلها، بينما تظل ظاهرة إذا فشل طلب النقل لأنها ما زالت على كفالة الشركة.
            </div>
        </div>
    @endif

    <div class="row mb-2">
        <div class="col-3">
            <div class="form-group">
                <label for="department_id" class="form-label">{{__('hr::hr.department')}}</label>
                <select class="form-select select2" id="department_id" name="department_id">
                    {!! showSelectElement($departments, 'title') !!}
                </select>
            </div>
        </div>
        <div class="col-3">
            <div class="form-group">
                <label for="job_id" class="form-label">{{__('hr::hr.job')}}</label>
                <select class="form-select select2" id="job_id" name="job_id">
                    {!! showSelectElement($jobs, 'title') !!}
                </select>
            </div>
        </div>
        @include('admin.partials.branch-filter', [
            'id' => 'branch_id',
            'name' => 'branch_id',
            'label' => __('financial_requests.branch'),
            'containerClass' => 'col-3',
        ])
        <div class="col-3">
            <br>
            @if (!empty($createRoute))
                {!! addEmplyeeButton($createRoute , $addButtonText) !!}
            @endif
        </div>
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.image") }}</th>
            <th>{{ __("hr::hr.name") }}</th>
            <th>{{ $identityColumnLabel ?? __("hr::hr.Employee ID number") }}</th>
            <th>{{ __("hr::hr.Date of hiring") }}</th>
            <th>{{ __("hr::hr.job") }}</th>
            <th>{{ __("hr::hr.department") }}</th>
            <th>{{ __('financial_requests.branch') }}</th>
            <th>{{ __("hr::hr.Status") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/datatable.buttons.min.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/buttons.html5.min.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/buttons.print.min.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/jszip.min.js"></script>

    <script>
        $(document).ready(function () {
            let table = $("#dataTableObject").DataTable({
                responsive: 1,
                "processing": true,
                "serverSide": true,
                "ordering": true,
                "searching": true,
                'iDisplayLength': 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, @json(__('hr::hr.all'))]],
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "data": function (d) {
                        d.department_id = $('#department_id').val();
                        d.job_id = $('#job_id').val();
                        d.branch_id = $('[name="branch_id"]').val();
                    }
                },
                "columns": [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {"data": "image", orderable: false, searchable: false},
                    {"data": "name", orderable: false, searchable: true},
                    {"data": "national_num", orderable: false, searchable: true},
                    {"data": "hired_date", orderable: false, searchable: true},
                    {"data": "job_id", orderable: false, searchable: true},
                    {"data": "deparment_id", orderable: false, searchable: true},
                    {"data": "branch", orderable: false, searchable: true},
                    {"data": "status", orderable: false, searchable: false},
                    {"data": "actions", orderable: false, searchable: false}
                ],
                "language": {
                    "sProcessing": "{{trans('dataTable.sProcessing')}}",
                    "sLengthMenu": "{{trans('dataTable.sLengthMenu')}}",
                    "sZeroRecords": "{{trans('dataTable.sZeroRecords')}}",
                    "sInfo": "{{trans('dataTable.sInfo')}}",
                    "sInfoEmpty": "{{trans('dataTable.sInfoEmpty')}}",
                    "sInfoFiltered": "{{trans('dataTable.sInfoFiltered')}}",
                    "sSearch": "{{trans('dataTable.sSearch')}}:",
                    "oPaginate": {
                        "sFirst": "{{trans('dataTable.sFirst')}}",
                        "sPrevious": "{{trans('dataTable.sPrevious')}}",
                        "sNext": "{{trans('dataTable.sNext')}}",
                        "sLast": "{{trans('dataTable.sLast')}}"
                    }
                },
                order: [[2, "desc"]],
                dom: 'Bflrtip', // Add the Buttons extension to the DataTable
                buttons: [
                    {
                        extend: 'excel',
                        className: 'btn btn-outline-success mb-2',
                        text: '{{trans('dataTable.ex_excel')}}',
                    }
                ]
            });

            $('#department_id, #job_id, [name="branch_id"]').change(function () {
                table.draw();
            });
        });
    </script>
@endsection
