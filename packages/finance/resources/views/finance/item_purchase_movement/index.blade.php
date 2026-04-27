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
            <label>{{ __("finance::finance.suppliers") }}</label>
            <select name="supplier_id" id="supplier_id" class="form-control">
                {!! showSelectElement($suppliers, "name") !!}
            </select>
        </div>
        <div class="col-md-3">
            <label>{{ __("finance::finance.items") }}</label>
            <select name="item_id" id="item_id" class="form-control">
                {!! showSelectElement($items, "name") !!}
            </select>
        </div>
        <div class="col-md-3">
            <label>{{ __("finance::finance.stocks") }}</label>
            <select name="stock_id" id="stock_id" class="form-control">
                {!! showSelectElement($stocks, "name") !!}
            </select>
        </div>

        <div class="col-md-3 mt-3">
            <button type="submit" class="btn btn-primary">بحث</button>
        </div>
        <div class="col-md-3 mt-3">
            <a id="toExcel" class="btn btn-warning"><i class="fa fa-file-excel-o"></i></a>
        </div>
    </form>

    <div id="reportTable">
    </div>

@endsection
@section('js')
<script src="{{asset("admin")}}/assets/exelJs/dist/tableToExcel.517b1af9.js"></script>
<script>
    var button = document.querySelector('#toExcel');
    button.addEventListener('click', function (e) {
        var table = document.querySelector('#reportTableData');
        TableToExcel.convert(table);
    });
</script>
<script>
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route("finance.item-purchase-movement.index") }}",
                method: 'GET',
                data: $(this).serialize(),
                success: function(response) {
                    $('#reportTable').html(response.data);
                },
                error: function(response) {
                    alert('Error fetching data');
                }
            });
        });
    });
</script>
@endsection
