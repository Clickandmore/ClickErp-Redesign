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

    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('crm::crm.SL') }}</th>
            <th>{{ __("crm::crm.title") }} </th>
            <th>{{ __("crm::crm.is_shown") }}</th>
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
            let columns = [{
                "data": 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
                {
                    "data": "title",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "is_shown",
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


    </script>
    <script>
        $(document).on('click', '.update-status', function (e) {
            var purchaseId = $(this).data('id');
            var url = "{{ route('crm.social_contacts.show', ':id') }}";
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

                        setTimeout(function () {
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
