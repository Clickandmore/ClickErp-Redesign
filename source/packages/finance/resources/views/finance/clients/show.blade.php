<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('finance::finance.client_details') }}</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <tbody>
                <tr>
                    <th width="30%">{{ __("finance::finance.client_type") }}</th>
                    <td>{{ $obj->is_company ? __('finance::finance.company_client') : __('finance::finance.normal_client') }}</td>
                </tr>
                <tr>
                    <th>{{ __("finance::finance.client_category") }}</th>
                    <td>{{ $obj->category->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __("finance::finance.name") }}</th>
                    <td>{{ $obj->name }}</td>
                </tr>
                <tr>
                    <th>{{ __("finance::finance.phone") }}</th>
                    <td>{{ $obj->phone }}</td>
                </tr>
                <tr>
                    <th>{{ __("finance::finance.email") }}</th>
                    <td>{{ $obj->email ?? '-' }}</td>
                </tr>
                <tr>
                        <th>{{ __("finance::finance.tax_number") }}</th>
                        <td>{{ $obj->tax_number ?? '-' }}</td>
                    </tr>
                <tr>
                        <th>{{ __("finance::finance.city") }}</th>
                        <td>{{ $obj->city ?? '-' }}</td>
                    </tr>
                <tr>
                        <th>{{ __("finance::finance.postal_zone") }}</th>
                        <td>{{ $obj->postal_zone ?? '-' }}</td>
                    </tr>
                <tr>
                        <th>{{ __("finance::finance.address") }}</th>
                        <td>{{ $obj->address ?? '-' }}</td>
                    </tr>
                <tr>
                    <th>{{ __("finance::finance.days_credit_period") }}</th>
                    <td>{{ $obj->days_credit_period }}</td>
                </tr>
                <tr>
                    <th>{{ __("finance::finance.credit_limit") }}</th>
                    <td>{{ number_format($obj->credit_limit, 2) }}</td>
                </tr>
                <tr>
                    <th>{{ __('finance::finance.bill_restrictions') }}</th>
                    <td>
                        @if($obj->exceeding_minimum_cash_limit)
                            {{ __('finance::finance.exceeding_minimum_cash_limit') }}<br>
                        @endif
                        @if($obj->exceeding_minimum_days_limit)
                            {{ __('finance::finance.exceeding_minimum_days_limit') }}
                        @endif
                        @if(!$obj->exceeding_minimum_cash_limit && !$obj->exceeding_minimum_days_limit)
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>{{ __('finance::finance.account_code') }}</th>
                    <td>{{ $obj->account->code ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ __('finance::finance.account_name') }}</th>
                    <td>{{ $obj->account->name ?? '-' }}</td>
                </tr>
                @if($obj->images->count())
                    <tr>
                        <th>{{ __('finance::finance.additional_attachments') }}</th>
                        <td>
                            <div class="d-flex flex-wrap">
                                @foreach($obj->images as $image)
                                    <a href="{{ asset($image->file) }}" target="_blank" class="m-2">
                                        <img src="{{ asset($image->file) }}" width="80" class="img-thumbnail">
                                    </a>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
