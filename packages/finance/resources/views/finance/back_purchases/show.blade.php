@extends('click.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
        .invoice-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .logo {
            max-height: 140px;
            max-width: 140px;
        }

        .invoice-header h1 {
            font-size: 24px;
            color: #333;
        }

        .invoice-header .invoice-info {
            text-align: right;
        }

        .invoice-info h4 {
            margin: 0;
            font-size: 16px;
        }

        .invoice-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .invoice-body {
            margin-top: 20px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .invoice-table th, .invoice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .invoice-table th {
            background-color: #f7f7f7;
            font-weight: bold;
        }

        .invoice-summary {
            margin-top: 20px;
            padding: 20px;
        }

        .invoice-summary .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .summary-row span {
            font-size: 16px;
            font-weight: bold;
        }

        .summary-row span.label {
            color: #555;
        }

        .summary-row span.value {
            color: #000;
        }

        .file_area {
            margin-top: 20px;
            text-align: center;
        }

        .file_area a {
            padding: 10px 20px; /* Add some padding */
            transition: background-color 0.3s; /* Smooth transition */
        }

        .file_area a:hover {
            background-color: #218838; /* Darker shade on hover */
        }

        .additions_actions {
            padding: 10px;
        }

        .additions_actions button,.additions_action a {
            background-color: #fff;
            border: none;
            padding: 3px 0px;
            display: block;
            color: var(--bs-card-color);
        }

        .additions_actions button:hover, a:hover {
            color: var(--secondary-color);
        }

        .invoice-footer {
            text-align: center;
            margin-top: 30px;
            color: #777;
            font-size: 14px;
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
    <div class="invoice-container">
        <div class="invoice-header">
            @if(isset($setting_web))
                <img class="logo" src="{{showFile($setting_web->logo_header)}}" alt="">
            @else
                <img class="logo" src="{{asset('admin/login')}}/imgs/Frame 36125.svg" alt="">
            @endif
            <h1>{{__('finance::finance.back_Purchases_tax_bill')}} #{{ $obj->code }}</h1>
            <div class="invoice-info">
                <h4>{{__('finance::finance.date')}} : {{ $obj->date }}</h4>
                <p>{{__('finance::finance.the_supplier')}} : {{ $obj->supplier->name }}</p>
                <p>{{__('finance::finance.supplier_bill_number')}} : {{ $obj->supplier_bill_number }}</p>
            </div>
        </div>

        <div class="invoice-body">
            <table class="invoice-table">
                <thead>
                <tr>
                    <th>الاجمالي total</th>
                    {{-- <th> الخصم Dis</th> --}}
                    <th>الضريبة Tax</th>
                    <th>السعر price</th>
                    <th>الكميه Qty</th>
                    <th>البيان Descreption</th>
                    <th>الرقم Code</th>
                </tr>
                </thead>
                <tbody>
                @php
                    // Group items by 'item_id'
                    $groupedItems = $obj->purchaseItem->groupBy('item_id');
                    $total_price = 0;
                    $total_discount = 0;
                @endphp
                @foreach($groupedItems as $itemGroup)
                    @php
                        // Get the first item from the group
                        $firstItem = $itemGroup->first();
                        // Calculate total amount and final total for the group
                        $totalAmount = $itemGroup->sum('amount');
                        $price = $itemGroup->sum('price');
                        $one_price = number_format($price / $totalAmount,2);
                        $totalTaxes = $itemGroup->sum('tax_value');
                        $totalFinalTotal = $itemGroup->sum('final_total');
                        $discount_value = $itemGroup->sum('discount_value');
                        $total_price += $totalFinalTotal;
                        $total_discount += $discount_value;
                    @endphp
                    <tr>
                        <td>{{ number_format($totalFinalTotal, 2) }}</td>
                        {{-- <td> {{ number_format($discount_value, 2) }} </td> --}}
                        <td>{{ $firstItem->tax_per ? $firstItem->tax_per.'%' : "0%" }}</td>
                        <td>{{ $one_price }}</td>
                        <td>{{ $totalAmount }}</td>
                        <td>{{ @$firstItem->item->name }}</td>
                        <td>{{ @$firstItem->item->code }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="invoice-summary">
                <div class="summary-row">
                    <span class="label">{{__('finance::finance.Total before discount')}} :</span>
                    <span class="value">{{ number_format($obj->purchaseItem->sum('price'), 2) }} </span>
                </div>
                <div class="summary-row">
                    <span class="label">{{__('finance::finance.discount_value')}} :</span>
                    <span class="value">{{ number_format($obj->purchaseItem->sum('discount_value'), 2) }} </span>
                </div>
                <div class="summary-row">
                    <span class="label">{{ __('finance::finance.Total after discount') }} :</span>
                    <span class="value">{{ number_format($obj->purchaseItem->sum('price') - $obj->purchaseItem->sum('discount_value'), 2) }} </span>
                </div>

                <div class="summary-row">
                    <span class="label">{{ __('finance::finance.the_tax_value') }} :</span>
                    <span class="value">{{ number_format($obj->purchaseItem->sum('tax_value'), 2) }} </span>
                </div>
                <div class="summary-row">
                    <span class="label">{{ __('finance::finance.net') }} :</span>
                    <span class="value">{{ number_format($obj->final_total, 2) }} </span>
                </div>

            </div>
        </div>


        <div class="additions_actions">
            @if(count($obj->movements))
                @foreach($obj->movements as $move)
                    <button type="button" class="editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                            model-route="{{route('finance.stock-movement.show',$move->stock_movement_id)}}">
                        <i class="fa fa-arrow-left"></i>
                        {{__('finance::finance.show stock sarf')}}
                        {{@$move->movement->code}}
                    </button>
                @endforeach
            @endif


            @if($obj->file != 'no_file')
                <a class="value" href="{{showFile($obj->file)}}">
                    <i class="fa fa-arrow-left"></i>
                    {{__('finance::finance.attachment')}}
                </a>
            @endif
        </div>


        <div class="invoice-footer">
            <p>
                {{__('finance::finance.notes')}} :
                {{$obj->notes}}
            </p>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{asset("admin")}}/assets/jsbundle/dataTables.min.js"></script>
@endsection
