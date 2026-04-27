@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {{-- {!! addButton($createRoute , $addButtonText) !!} --}}
    {{--    @if (Auth::guard('admin')->user()->hasPermission('finance.items-import-create')) --}}
    <a href="{{ $excelRoute }}" class="btn btn-info float-start mx-1">
        {{ __('finance::finance.Excel upload for items') }}
    </a>
    {{--    @endif --}}
    <div class="d-flex mb-3 justify-content-end">
        <a class="btn btn-success float-start mx-1" href="{{ $createRoute }}" title="">
            {{ __('buttons.add button') }} {{ $addButtonText }}
        </a>
    </div>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.name') }}</th>
                <th>{{ __('finance::finance.main_category') }}</th>
                <th>{{ __('finance::finance.type') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
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
                    "data": "main_category_id",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "type",
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
    </script>
@endsection
