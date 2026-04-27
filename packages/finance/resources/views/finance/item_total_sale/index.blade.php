@extends('click.layout.indexs.index')
@section("style")
    <style>
        /* تنسيق الجدول */
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table th {
            background-color: #007bff;
            color: white;
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

        .table > thead {
            vertical-align: bottom;
            position: sticky;
            top: 86px;
            z-index: 2;
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
    <form class="row mb-3" id="filterForm">
        @csrf
        <div class="col-md-3">
            <label>{{ __("finance::finance.from_date") }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label>{{ __("finance::finance.to_date") }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label>{{ __("finance::finance.type") }}</label>
            <select name="type" id="type" class="form-control">
                {!! getEnumData($types) !!}
            </select>
        </div>
        <div class="col-md-3">
            <label>{{ __("finance::finance.items") }}</label>
            <select name="item_id[]" id="item_id" class="form-control select2" multiple="multiple">
                {!! showSelectElement($items, "name") !!}
            </select>
        </div>


        <div class="col-md-3 mt-3">
            <button type="submit" class="btn btn-primary">بحث</button>
            <a id="toExcel" class="btn btn-warning"><i class="fa fa-file-excel"></i><i class="fa fa-download"></i></a>
        </div>
        <div class="col-md-3 mt-3">

        </div>
    </form>

    <div id="reportTable">
        <table class="table" id="reportTable_data">
            <thead>
            <tr>
                <th>{{ __("finance::finance.code") }}</th>
                <th>{{ __("finance::finance.item") }}</th>
                <th>{{ __("finance::finance.category") }}</th>
                <th>{{ __("finance::finance.unit") }}</th>
                <th>{{ __("finance::finance.amount") }}</th>
                <th>{{ __("finance::finance.sales_price") }}</th>
                <th>{{ __("finance::finance.total") }}</th>
                <th>{{ __("finance::finance.user") }}</th>
                <th>{{ __("finance::finance.Statement") }}</th>
                <th>{{ __("finance::finance.total_discount_value") }}</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total = 0;
            @endphp
            @forelse($report as $item)
                @php
                    $sign =  ($item->type == 'back_sales') ? '-' : "" ;
                    $total =  ($item->type == 'back_sales') ? $total - $item->total_amount * $item->total_sale_price : $total + $item->total_amount * $item->total_sale_price;
                @endphp
                <tr>
                    <td>{{ $item->item->code }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->item->mainCategory->title ?? '' }}</td>
                    <td>{{ $item->item->saleUnit->title ?? '' }}</td>
                    <td>{{ $sign . $item->total_amount }}</td>
                    <td>{{ $sign . $item->total_sale_price }}</td>
                    <td>{{ $sign . $item->total_amount * $item->total_sale_price }}</td>
                    <td>{{ $item->sale->created_by->name ?? 'No User' }}</td>
                    <td>{{ Nami\Finance\Enums\SalesTypeEnum::from($item->type)->lang() }}</td>

                    <td>{{ $item->sale->discount ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">{{ __("No Data Available") }}</td>
                </tr>
            @endforelse
            </tbody>

            <tfoot>
            <tr>
                <td colspan="6">{{ __("finance::finance.total") }}</td>
                <td>{{ $total }}</td>

            </tr>
            </tfoot>
        </table>

    </div>

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function (e) {
            var table = document.querySelector('#reportTable_data');
            TableToExcel.convert(table);
        });
    </script>
    <script>
        $(document).ready(function () {
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route("finance.item-total-sale.index") }}",
                    method: 'GET',
                    data: $(this).serialize(),
                    success: function (response) {
                        $('#reportTable').html(response.data);
                    },
                    error: function (response) {
                        alert('Error fetching data');
                    }
                });
            });
        });
    </script>
@endsection
