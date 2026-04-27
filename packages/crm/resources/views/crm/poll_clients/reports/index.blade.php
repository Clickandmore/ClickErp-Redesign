@extends('nami.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>

    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">{{$bladeTitle}} </h3> &nbsp;
{{--                    {!! addButton($createRoute , $addButtonText) !!}--}}
            </div>
            <div class="card-body tableResponsive">
                <div class="row alert-success p-2">

                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm.from_date") }}</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" required>
                    </div>
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm.to_date") }}</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" required>
                    </div>
                </div>
                <hr>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead class="thead-inverse">
                    <tr>
                        <th>{{ __('crm.SL') }}</th>
                        <th>{{ __("crm.name") }} </th>
                        <th>{{ __("crm.price_offer_count") }} </th>
                        <th>{{ __("crm.phone") }} </th>
                        <th>{{ __("crm.email") }} </th>
                        <th>{{ __("crm.status") }}</th>
{{--                        <th>{{ __('crm.actions') }}</th>--}}
                    </tr>
                </thead>
                <tbody>

                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('crm.SL') }}</th>
                        <th>{{ __("crm.name") }} </th>
                        <th>{{ __("crm.price_offer_count") }} </th>
                        <th>{{ __("crm.phone") }} </th>
                        <th>{{ __("crm.email") }} </th>
                        <th> {{ __("crm.status") }} </th>
{{--                        <th>{{ __('crm.actions') }}</th>--}}
                    </tr>
                </tfoot>
                </table>
            </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {

            var table = $('#dataTableObject').DataTable({
            responsive: true,
           'iDisplayLength': 10,
            "lengthMenu": [ [5, 10, 25, 50, 100, 200, -1], [5, 10, 25, 50, 100, 200, "الكل"] ],
            "language": {
                "url": '{{ asset("assets/datatables/")."/".__("fleet.datatable_lang") }}',
            },
            processing: true,
            serverSide: false,
            dom: 'Bflrtip', // Add the Buttons extension to the DataTable
            buttons: [
                {
                    extend: 'print',
                    className: 'btn btn-outline-success mb-2',
                    text: '{{__('dataTable.print')}}',
                    titleAttr: 'طباعة الجدول',
                    customize: function (win) {
                        if (currentLocale === 'Arabic-ar') {
                            $(win.document.body)
                                .css('direction', 'rtl')
                                .css('text-align', 'right')
                                .prepend(
                                    '<div style="text-align: center;"><h1>طباعة الجدول</h1></div>'
                                );
                        } else {
                            $(win.document.body)
                                .css('direction', 'ltr')
                                .css('text-align', 'left')
                                .prepend(
                                    '<div style="text-align: center;"><h1>Table Print</h1></div>'
                                );
                        }
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn btn-outline-success mb-2',
                    text: '{{trans('dataTable.ex_excel')}}',
                }
            ],
            ajax: {
                url: "{{ $dataTableRoute }}",
                data: function (d) {
                    d.client_id = $('#client_id').val()
                    d.contract_type_id = $('#contract_type_id').val()
                    d.user_id = $('#user_id').val()
                    d.from_date = $('#from_date').val()
                    d.to_date = $('#to_date').val()
                    d.orderBy = $('#orderBy').val()
                    d.maintenance_category_id = $('#maintenance_category_id').val()
                    d.maintenance_type_id = $('#maintenance_type_id').val()
                    d.maintenance_place_id = $('#maintenance_place_id').val()
                    d.vehicle_id = $('#vehicle_id').val()
                    d.city_id = $('#city_id').val()
                    d.area_id = $('#area_id').val()
                    d.social_contact_id = $('#social_contact_id').val()
                    d.category_id = $('#category_id').val()
                    d.status = $('#status').val()
                    d.technician_id = $('#technician_id').val()
                    d.contract_id = $('#contract_id').val()
                    d.driver_id = $('#driver_id').val()
                    d.vendor_id = $('#vendor_id').val()
                    d.status = $('#status').val()
                    d.bill_sale_type = $('#bill_sale_type').val()
                    d.cost_center_id = $('#cost_center_id').val()
                    d.search = $('input[type="search"]').val()
                }
            },
            columns: [{
                    "data": 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "name",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "price_offer_count",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "phone",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "email",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "status",
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[1, 'desc']],
            "initComplete": function () {
                table.columns().every(function () {
                    var that = this;
                    $('input', this.footer()).on('keyup change', function () {
                        // console.log($(this).subscriber().index());
                        that.search(this.value).draw();
                    });
                });
            }
        });

        $('#client_id').change(function () {
            table.draw();
        });
        $('#bill_sale_type').change(function () {
            table.draw();
        });
        $('#cost_center_id').change(function () {
            table.draw();
        });
        $('#contract_type_id').change(function () {
            table.draw();
        });
        $('#user_id').change(function () {
            table.draw();
        });
        $('#vendor_id').change(function () {
            table.draw();
        });
        $('#from_date').change(function () {
            table.draw();
        });
        $('#to_date').change(function () {
            table.draw();
        });
        $('#orderBy').change(function () {
            table.draw();
        });
        $('#maintenance_category_id').change(function () {
            table.draw();
        });
        $('#maintenance_type_id').change(function () {
            table.draw();
        });
        $('#maintenance_place_id').change(function () {
            table.draw();
        });
        $('#technician_id').change(function () {
            table.draw();
        });
        $('#vehicle_id').change(function () {
            table.draw();
        });
        $('#city_id').change(function () {
            table.draw();
        });
        $('#area_id').change(function () {
            table.draw();
        });

        $('#social_contact_id').change(function () {
            table.draw();
        });

        $('#category_id').change(function () {
            table.draw();
        });
        $('#status').change(function () {
            table.draw();
        });
        $('#contract_id').change(function () {
            table.draw();
        });
        $('#driver_id').change(function () {
            table.draw();
        });
        $('#status').change(function () {
            table.draw();
        });

            // let columns = [{
            //         "data": 'DT_RowIndex',
            //         name: 'DT_RowIndex',
            //         orderable: false,
            //         searchable: false
            //     },
            //     {
            //         "data": "name",
            //         orderable: false,
            //         searchable: false
            //     },
            //     {
            //         "data": "phone",
            //         orderable: false,
            //         searchable: false
            //     },
            //     {
            //         "data": "status",
            //         orderable: false,
            //         searchable: false
            //     }
            // ];
            // showDataTable("{{ $dataTableRoute }}", columns);
        });


    </script>
 <script>
    $(document).on('click','.update-status',function (e) {
            var purchaseId = $(this).data('id');
            var url = "{{ route('crm.clients.show', ':id') }}";
            url = url.replace(':id', purchaseId);
            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    if (data.success) {
                        $.toast({
                            heading: '{{ __("crm.success") }}',
                            text: '{{ __("crm.successfuly done") }}',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });

                        setTimeout(function(){
                            $('#dataTableObject').DataTable().ajax.reload();
                        }, 1500);
                    } else {
                        alert('فشل في تحديث الحالة.');
                    }
                },
                error: function () {
                    alert('حدث خطأ أثناء التحديث.');
                }
            });
        });
</script>
@endsection
