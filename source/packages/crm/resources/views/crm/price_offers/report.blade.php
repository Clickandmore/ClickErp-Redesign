@extends('click.layout.indexs.index')
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
                <h3 class="card-title">{{$bladeTitle}} &nbsp;</h3>
            </div>
            <div class="card-body tableResponsive">
                <div class="row alert-success p-2">
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm::crm.clients") }}</label>
                        <select class="form-control show-tick ms select2" id="client_id" name="client_id"
                                data-validation="required">
                            {!! showSelectElement($clients,"name" ) !!}
                        </select>
                    </div>
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm::crm.status") }}</label>
                        <select class="form-control show-tick ms select2" id="status" name="status"
                                data-validation="required">

                            {!! getEnumData($statuses,"price_offer_statuses" ) !!}
                        </select>
                    </div>
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm::crm.from_date") }}</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" required>
                    </div>
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm::crm.to_date") }}</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" required>
                    </div>
                </div>
                <hr>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead class="thead-inverse">
                    <tr>
                        <th>{{ __('crm::crm.SL') }}</th>
                        <th>{{ __("crm::crm.clients") }} </th>
                        <th> {{ __("crm::crm.date") }} </th>
                        <th> {{ __("crm::crm.total") }} </th>
                        <th class="notPrintable">{{ __('crm::crm.actions') }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('crm::crm.SL') }}</th>
                        <th>{{ __("crm::crm.clients") }} </th>
                        <th> {{ __("crm::crm.date") }} </th>
                        <th> {{ __("crm::crm.total") }} </th>
                        <th>{{ __('crm::crm.actions') }}</th>
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
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
        function productPrice(obj){
            let servicePrice = $(obj).find(":selected").attr('data-price');
            let tr = $(obj).closest('tr');
            $('.priceInput',tr).val(servicePrice);
            updateTotal();
        }
    </script>
    <script>
        function updateTotal() {
            $(".amountInput, .priceInput, .discountInput").on("keyup", function(){
                var total = 0;
                $(".amountInput").each(function(index){
                    var amount = $(this).val() || 1;
                    var discount = $(this).closest("tr").find(".discountInput").val() || 0;
                    var price = $(this).closest("tr").find(".priceInput").val() || 0;
                    if(amount && price){
                        total += parseInt(amount) * parseFloat(price) -  parseFloat(discount);
                    }
                });
                $(".total").val(total);
            });
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
