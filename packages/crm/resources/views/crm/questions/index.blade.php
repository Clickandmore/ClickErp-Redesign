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
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
            <tr>
                <th>{{ __('crm.SL') }}</th>
                <th>{{ __('crm.question') }} </th>
                <th>{{ __('crm.answers') }} </th>
                <th>{{ __('crm.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>

    </table>
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
                    searchable: true
                },
                {
                    "data": "show",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });

        // $(document).ajaxComplete(function() {
        //     $('#type').change(function() {
        //         var type = $(this).val();
        //         if (type == 'choices' ) {
        //             $('#answers').show();
        //         } else {
        //             $('#answers').hide();
        //         }
        //     });
        // });
    </script>
@endsection
