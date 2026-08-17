@extends('click.layout.indexs.index')
@section('style')
    <style>
        /* تنسيق الجدول */
        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table th {
            background-color: #007bff;
            color: white !important;
            font-weight: bold;
        }

        /* تمييز صفوف الإجمالي */
        .table .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        /* زر البحث */
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        /* جعل الفلتر مرتب */
        .filter-container {
            margin-bottom: 20px;
        }

        .filter-label {
            font-weight: bold;
        }

        .table>thead {
            vertical-align: bottom;
            position: sticky;
            top: 86px;
            z-index: 2;
        }
    </style>
    <style>
        #reportTableData {
            border: 1px solid black;
            border-collapse: collapse;
        }

        #reportTableData th,
        #reportTableData td {
            border: 1px solid black;
        }

        .highlight {
            background-color: yellow;
        }
    </style>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #reportTableData,
            #reportTableData * {
                visibility: visible;
            }

            #reportTableData {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ __('finance::finance.store-balances') }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ __('finance::finance.store-balances') }}</li>
@endsection
@section('content')
    {{-- <div class="row mb-3"> --}}
    <form class="row mb-3" id="filterForm">
        <div class="col-md-3">
            <label for="stock_id" class="filter-label">{{ __('finance::finance.stocks') }}</label>
            <select name="stock_id" id="stock_id" class="form-control select2">
                {!! showSelectElement($stocks, 'name') !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="item_id" class="filter-label">{{ __('finance::finance.items') }}</label>
            <select name="item_id" id="item_id" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 mt-3">
            <button type="submit" class="btn btn-primary">بحث</button>
        </div>
        <div class="col-md-3 mt-3">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i></button>
            <a id="toExcel" class="btn btn-warning"><i class="fa fa-file-excel-o"></i></a>
        </div>
    </form>
    {{-- </div> --}}

    <div class="table_data">
        @include('finance::finance.store_balances.table')
    </div>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function(e) {
            var table = document.querySelector('#reportTableData');
            TableToExcel.convert(table);
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                var stock_id = $('#stock_id').val();
                var item_id = $('#item_id').val();

                $.ajax({
                    url: "{{ route('finance.store-balances.index') }}",
                    method: 'GET',
                    data: {
                        stock_id: stock_id,
                        item_id: item_id,
                    },
                    success: function(response) {
                        $('.table_data').html(response); // تحديث الجدول فقط
                    }
                });
            });
        });
    </script>
@endsection
