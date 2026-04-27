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



    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                        <thead class="thead-inverse">
                        <tr>
                            <th>{{ __('crm::crm.SL') }}</th>
                            <th>{{ __("crm::crm.clients") }} </th>
                            <th>{{ __("crm::crm.user") }} </th>
                            <th>{{ __("crm::crm.follow_clients_category") }} </th>
                            <th>{{ __("crm::crm.date") }}</th>
                            <th class="notPrintable">{{ __('crm::crm.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>

                    </table>

@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
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
                    exportOptions: {
                        columns: ':visible:not(.notPrintable)'
                    },
                    titleAttr: '',
                    customize: function (win) {
                        $.ajax({
                            url: '{{ url('print-header') }}',
                            success: function(response) {
                                var headerHtml = response.html;

                                // Check the current locale and set the appropriate direction and text alignment
                                if (currentLocale === 'Arabic-ar') {
                                    $(win.document.body)
                                        .css('direction', 'rtl')
                                        .css('text-align', 'right')
                                        .prepend(headerHtml);
                                } else {
                                    $(win.document.body)
                                        .css('direction', 'ltr')
                                        .css('text-align', 'left')
                                        .prepend(headerHtml);
                                }

                                // Remove the last column from the table
                                // $(win.document.body).find('table thead th:last-child, table tbody td:last-child').remove();
                                setTimeout(function() {
                                    win.print();
                                }, 34000);
                            },
                            error: function(xhr, status, error) {
                                console.error('Failed to load print header:', status, error);
                            }
                        });
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
                    "data": "client_id",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "user",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "category",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "date",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
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
        });


    </script>


    {{-- <script>
       $(document).on('click','.update-status',function (e) {
               var purchaseId = $(this).data('id');
               var url = "{{ route('follow-clients.show', ':id') }}";
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
   </script> --}}
@endsection
