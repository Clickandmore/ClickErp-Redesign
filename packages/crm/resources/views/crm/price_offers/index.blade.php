@extends('nami.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .ck .ck-reset .ck-editor .ck-rounded-corners {
            color: black !important;
        }

        .ck-editor__editable {
            min-height: 300px;
            color: black;
        }

        .cke_notification {
            display: none !important;
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('crm::crm.SL') }}</th>
            <th>{{ __("crm::crm.clients") }} </th>
            <th> {{ __("crm::crm.date") }} </th>
            <th> {{ __("crm::crm.total") }} </th>
            <th> {{ __("crm::crm.status") }} </th>
            <th class="notPrintable">{{ __('crm::crm.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>

@endsection
@section('js')
    {{--<script src="{{ asset('assets/js/cdn/ck2.js')}}"></script>--}}
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [{
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
                    "data": "date",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "status",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });

        function productPrice(obj) {
            let servicePrice = $(obj).find(":selected").attr('data-price');
            let tr = $(obj).closest('tr');
            $('.priceInput', tr).val(servicePrice);
            updateTotal();
        }
    </script>
    <script>
        function updateTotal() {
            $(".amountInput, .priceInput, .discountInput").on("keyup", function () {
                var total = 0;
                $(".amountInput").each(function (index) {
                    var amount = $(this).val() || 1;
                    var discount = $(this).closest("tr").find(".discountInput").val() || 0;
                    var price = $(this).closest("tr").find(".priceInput").val() || 0;
                    if (amount && price) {
                        total += parseInt(amount) * parseFloat(price) - parseFloat(discount);
                    }
                });
                $(".total").val(total);
            });
        }

        function copylink() {
            var copyButton = document.getElementById("show_link");
            // Select the text field
            // copyText.select();
            // copyText.setSelectionRange(0, 99999); // For mobile devices

            // Get the href attribute value
            var hrefValue = copyButton.getAttribute('href');

            // Copy the text inside the href attribute
            navigator.clipboard.writeText(hrefValue);

            // Alert the copied text
            alert('تم النسخ');
        }
    </script>
    {{-- <script>
       $(document).on('click','.update-status',function (e) {
               var purchaseId = $(this).data('id');
               var url = "{{ route('crm::crm.price-offers.show', ':id') }}";
               url = url.replace(':id', purchaseId);
               $.ajax({
                   type: 'GET',
                   url: url,
                   success: function (data) {
                       if (data.success) {
                           $.toast({
                               heading: '{{ __("crm::crm.success") }}',
                               text: '{{ __("crm::crm.successfuly done") }}',
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
