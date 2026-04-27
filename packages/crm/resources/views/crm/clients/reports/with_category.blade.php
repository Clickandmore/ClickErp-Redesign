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
                        <label for="TextInput" class="form-label">{{ __("crm::crm.social_contacts") }}</label>
                        <select class="form-control show-tick ms select2" id="social_contact_id" name="social_contact_id"
                                data-validation="required">
                            @foreach ($social_contacts as $item)
                                <option value="{{ $item->id }}">{{ getFinalTrans('title',$item) }}</option>
                            @endforeach
{{--                            {!! showSelectElement($social_contacts,"title" ) !!}--}}
                        </select>
                    </div>
                    <div class="col-3 float-start">
                        <label for="TextInput" class="form-label">{{ __("crm::crm.categories") }}</label>
                        <select class="form-control show-tick ms select2" id="category_id" name="category_id"
                                data-validation="required">
                            {!! showSelectElement($categories,"name" ) !!}
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
                    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead class="thead-inverse">
                    <tr>
                        <th>{{ __('crm::crm.SL') }}</th>
                        <th>{{ __("crm::crm.name") }} </th>
                        <th>{{ __("crm::crm.phone") }} </th>
                        <th>{{ __("crm::crm.status") }}</th>
{{--                        <th>{{ __('crm::crm.actions') }}</th>--}}
                    </tr>
                </thead>
                <tbody>

                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('crm::crm.SL') }}</th>
                        <th>{{ __("crm::crm.name") }} </th>
                        <th>{{ __("crm::crm.phone") }} </th>
                        <th> {{ __("crm::crm.status") }} </th>
{{--                        <th>{{ __('crm::crm.actions') }}</th>--}}
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
                    "data": "name",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "phone",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "status",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
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
</script>
@endsection
