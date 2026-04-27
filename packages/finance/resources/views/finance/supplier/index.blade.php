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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        {!! addButton($createRoute , $addButtonText) !!}
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('finance::finance.suppliers') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                    <thead>
                    <tr>
                        <th>{{ __('finance::finance.SL') }}</th>
                        <th>{{ __('finance::finance.code') }} </th>
                        <th>{{ __('finance::finance.name') }} </th>
                        <th>{{ __('finance::finance.phone') }} </th>
                        <th>{{ __('finance::finance.address') }} </th>
                        <th>{{ __('finance::finance.suppliers_types') }} </th>
                        <th>{{ __('finance::finance.commercial_register_number') }} </th>
                        <th>{{ __('finance::finance.tax_number') }} </th>
                        <th>{{ __('finance::finance.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex',name: 'DT_RowIndex',orderable: false,searchable: false},
                {"data": "code",orderable: false,searchable: true},
                {"data": "name",orderable: false,searchable: true},

                {"data": "phone",orderable: false,searchable: true},
                {"data": "address",orderable: false,searchable: true},
                {"data": "supplier_type_id",orderable: false,searchable: true},

                {"data": "commercial_register_number",orderable: false,searchable: true},
                {"data": "tax_number",orderable: false,searchable: true},
                {"data": "actions",orderable: false,searchable: false},
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>

@endsection
