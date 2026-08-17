@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')

    <div class="row">
        <div class="col-3 my-2">
            <label for="date" class="form-label"> {{ __("hr.Employee") }}</label>
            <select class="form-control show-tick ms" name="employee_id" id="employeeId" data-validation="required">
                <option value="">{{ __("hr.Choose") }} {{ __("hr.Employee") }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-2 my-2">
            <label for="date" class="form-label"> {{ __("hr.date") }}</label>
            <input type="text" autocomplete="false" id="dateRange" placeholder="{{__('hr.Select date range')}}" />

        </div>
    </div>

{{--    {!! addButton($createRoute , $addButtonText) !!}--}}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('hr.SL') }}</th>
            <th>{{ __("hr.Employee") }}</th>
            <th>{{ __("hr.title") }}</th>
            <th>{{ __("hr.date") }}</th>
            <th>{{ __("hr.file") }}</th>
            <th>{{ __('hr.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <!-- Include CSS and JS files for daterangepicker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        $(document).ready(function () {

            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "employee_id", orderable: false, searchable: true},
                {"data": "title", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "file", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            var url = "{{ $dataTableRoute }}";
            var table = $("#dataTableObject").DataTable({
                // dom: 'Bfrtip',
                responsive: 1,
                "ordering": true,
                "searching": true,
                'iDisplayLength': 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
                "processing": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": url,
                    "type": "get",
                    "data": function (d) {
                        d.employee_id = $('#employeeId').val();
                        var dateRange = $('#dateRange').val();
                        if (dateRange) {

                            var dates = dateRange.split(' - ');
                            d.from_date = dates[0]; // Pass the start date
                            d.to_date = dates[1];   // Pass the end date
                        }

                    }
                },
                "columns": columns,
                "language": {
                    "sProcessing": "<?php echo e(trans('dataTable.sProcessing')); ?>",
                    "sLengthMenu": "<?php echo e(trans('dataTable.sLengthMenu')); ?>",
                    "sZeroRecords": "<?php echo e(trans('dataTable.sZeroRecords')); ?>",
                    "sInfo": "<?php echo e(trans('dataTable.sInfo')); ?>",
                    "sInfoEmpty": "<?php echo e(trans('dataTable.sInfoEmpty')); ?>",
                    "sInfoFiltered": "<?php echo e(trans('dataTable.sInfoFiltered')); ?>",
                    "sInfoPostFix": "",
                    "sSearch": "<?php echo e(trans('dataTable.sSearch')); ?>:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "<?php echo e(trans('dataTable.sFirst')); ?>",
                        "sPrevious": "<?php echo e(trans('dataTable.sPrevious')); ?>",
                        "sNext": "<?php echo e(trans('dataTable.sNext')); ?>",
                        "sLast": "<?php echo e(trans('dataTable.sLast')); ?>"
                    }
                },
                order: [
                    [2, "desc"]
                ]
            });

            $('#employeeId').on('keyup change', function () {
                table.draw();
            });

            // $('#from_date, #to_date').on('keyup change', function () {
            //     table.draw();
            // });

            $('#dateRange').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: '{{__("hr.clear")}}',
                    applyLabel: '{{__("hr.apply")}}',
                    format: 'YYYY-MM-DD',
                    customRangeLabel: '{{ __("hr.Custom Range") }}',
                },
                ranges: {
                    '{{__('hr.Today')}}': [moment(), moment()],
                    '{{__('hr.Yesterday')}}': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '{{__('hr.Last 7 Days')}}': [moment().subtract(6, 'days'), moment()],
                    '{{__('hr.Last 30 Days')}}': [moment().subtract(29, 'days'), moment()],
                    '{{__('hr.This Month')}}': [moment().startOf('month'), moment().endOf('month')],
                    '{{__('hr.Last Month')}}': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            // Handle date range picker events
            $('#dateRange').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw(); // Trigger DataTable to filter with the selected range
            });

            $('#dateRange').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                table.draw(); // Clear the date range filter
            });

        });
    </script>

@endsection
