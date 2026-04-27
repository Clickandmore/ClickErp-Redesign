<table class="table">
    <tbody>
    <tr>
        <th scope="rocodew">{{ __("finance::finance.Voucher Number") }}</th>
        <td>{{ $obj->id }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.Voucher Date") }}</th>
        <td>{{ $obj->date}}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.Bookkeeping Number") }}</th>
        <td>{{ $obj->bond_book_num }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.Amount") }} </th>
        <td>{{ $obj->value }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.Is there a tax?") }} </th>
        <td>{{ $obj->is_tax == 1 ? __("finance::finance.Yes") : __("finance::finance.No") }}</td>
    </tr>
    @if ($obj->is_tax == 1)
        <tr>
            <th scope="row">{{ __("finance::finance.Tax rate") }} </th>
            <td>{{ $obj->bondTax->tax_rate ?? 0 }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __("finance::finance.Tax amount") }} </th>
            <td>{{ $obj->bondTax->tax_amount ?? 0  }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __("finance::finance.net") }} </th>
            <td>{{ $obj->net }}</td>
        </tr>
    @endif
    <tr>
        <th scope="row">{{ __("finance::finance.fn-cost-centers") }}</th>
        <td>{{ $obj->costCenter->title ?? "" }}</td>
    </tr>
    {{-- <tr>
        <th scope="row"> {{ __("finance::finance.Payee") }}</th>
        <td>{{ $obj->debitAccount->name }}</td>
    </tr> --}}
    <tr>
        <th scope="row"> {{ __("finance::finance.Payment Method") }}</th>
        <td>{{ getEnumValue("bond_payment_type", $obj->payment_type) }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.statement") }}  </th>
        <td>{{ $obj->statement }}</td>
    </tr>
    <tr>
        <th scope="row">{{ __("finance::finance.From Account") }}  </th>
        <td>{{ $obj->account_text }}</td>
    </tr>
    <tr>
        <th scope="row"> {{ __("finance::finance.Document Image") }}</th>
        <td>
            <a href="{{ showFile($obj->file) }}" download>
                <img width="100px" height="100px" src="{{asset('storage').'/'.$obj->file}}">
                <i class="fa fa-download"></i>
            </a>
        </td>
    </tr>
    {{-- <tr>
        <th scope="row">الدائن</th>
        <td>{{ $obj->creditAccount->name }}</td>
    </tr>
    <tr><tr>
        <th scope="row">المدين</th>
        <td>{{ $obj->debitAccount->name }}</td>
    </tr> --}}
    @if ($obj->payment_type != Nami\Finance\Enums\BondPaymentTypes::Cash->value)
        <tr>
            <th scope="row"> {{ __("finance::finance.Transfer number") }}</th>
            <td>{{ $obj->transfer_number }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __("finance::finance.Transfer image") }}</th>
            <td><img width="100px" height="100px" src="{{asset('storage').'/'.$obj->transfer_file}}">
                <a href="{{ showFile($obj->transfer_file) }}" download>
                    <i class="fa fa-download"></i>
                </a>
            </td>
        </tr>
    @endif
    </tbody>
</table>
