@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="row mb-3">
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.from_date") }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.to_date") }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.code") }}</label>
            <input type="text" name="code" id="code" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="">{{ __("finance::finance.from_stock") }}</label>
            <select name="from_stock_id" id="from_stock_id" class="form-control">
                {!! showSelectElement($stocks,"name") !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __("finance::finance.to_stock") }}</label>
            <select name="to_stock_id" id="to_stock_id" class="form-control">
                {!! showSelectElement($stocks,"name") !!}
            </select>
        </div>
    </div>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{__("finance::finance.code")}}</th>
            <th>{{__("finance::finance.date")}}</th>
            <th>{{__("finance::finance.from_stock")}}</th>
            <th>{{__("finance::finance.to_stock")}}</th>
            <th>{{__("finance::finance.conversion_details")}}</th>
            <th>{{__("finance::finance.sarf-movement-details")}}</th>
            <th>{{__("finance::finance.transfer")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "الكل"] ],
                "processing": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function (d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.code = $('#code').val();
                        d.from_stock_id = $('#from_stock_id').val();
                        d.to_stock_id = $('#to_stock_id').val();
                    }
                },
                "columns": [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {"data": "code", orderable: false, searchable: false},
                    {"data": "date", orderable: false, searchable: false},
                    {"data": "from_stock_id", orderable: false, searchable: false},
                    {"data": "to_stock_id", orderable: false, searchable: false},
                    {"data": "conversion_details", orderable: false, searchable: false},
                    {"data": "sarf_movement_details", orderable: false, searchable: false},
                    {"data": "transfer", orderable: false, searchable: false}
                ],
            });

            $('#from_date, #to_date, #from_stock_id, #to_stock_id').change(function () {
                table.draw();
            });
            $('#code').keyup(function () {
                table.draw();
            });
        });

        $(document).ready(function () {
            $(document).on('click', '.transferButton', function (e) {
                e.preventDefault();

                let buttonObj = $(this);
                let modelRoute = buttonObj.attr("model-route");
                let modelTitle = buttonObj.attr("model-title");
                let modalResult = $("#createModal");

                Swal.fire({
                    title: '{{ __("finance::finance.are_you_sure") }}',
                    text: "{{ __("finance::finance.transfer_to_sarf_warning") }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ __("finance::finance.yes_proceed") }}',
                    cancelButtonText: '{{ __("finance::finance.cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#createModal').modal('show');

                        $('.modal-body').html(loader);
                        $(".modal-body", modalResult).html();

                        setTimeout(function () {
                            $.ajax({
                                url: modelRoute,
                                type: 'GET',
                                beforeSend: function () {
                                },
                                complete: function () {
                                },
                                success: function (result) {
                                    $("#exampleModalLgLabel").text(modelTitle);
                                    $(".modal-body", modalResult).html(result.data.html);
                                    loadOptionsOnAjax();
                                },
                                error: function (errorObj, errorText, errorThrown) {
                                    handleErrorResponce(errorObj);
                                }
                            });
                        }, 700);
                    }
                });
            });
        });
    </script>
@endsection
