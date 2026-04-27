@php
    $statementPermissions = [
        'finance.account-statement-read',
        'finance.account-statement.index-read',
        'finance.account-statement-print-read',
        'finance::finance.account-statement-print-read',
    ];
    $canOpenAccountStatement = auth('admin')->check()
        && (
            auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value
            || count(array_intersect($statementPermissions, getUserPermissions())) > 0
        );
    $buildAccountStatementUrl = function ($accountId) {
        return route('finance.account-statement.index', [
            'account_id' => $accountId,
            'to_date' => now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d'),
            'type' => 'both',
            'other_account' => 'yes',
        ]);
    };
@endphp

<div class="row g-3">
    <div class="col-md-6 col-4">
        <label>{{ __('finance::finance.code') }}</label>
        <span class="form-control">{{ $obj->code }}</span>
    </div><div class="col-md-6 col-4">
        <label>{{ __('finance::finance.date') }}</label>
        <span class="form-control">{{ $obj->date }}</span>
    </div>
    <div class="col-md-6 col-4">
        <label>{{ __('finance::finance.statement') }}</label>
        <span class="form-control form-control-lg mb-1">{{ $obj->statement }}</span>
    </div>
    <div class="debtor__value"></div>
    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
        <tr>
            <td>{{ __('finance::finance.debtor') }}</td>
            <td>{{ __('finance::finance.creditor') }}</td>
            <td>{{ __('finance::finance.Account Name') }}</td>
            <td>{{ __("finance::finance.fn-cost-centers") }}</td>
            <td>{{ __('finance::finance.Account Statement') }}</td>
        </tr>
        </thead>
        <tbody class="table-body">
        @foreach ($obj->constraintDetails as $constraintDetails)
            @if ($constraintDetails->type == 'debtor')
                <tr class="item-row debtor_append" id="debtor_append">
                    <td>
                        <span class="form-control">{{ $constraintDetails->debtor_value }}</span>
                    </td>
                    <td>
                        <span class="form-control">0</span>
                    </td>
                    <td>
                        @php
                            $account = $accounts->firstWhere('id', $constraintDetails?->account_id);
                        @endphp
                        @if ($canOpenAccountStatement && filled($account?->id))
                            <a href="{{ $buildAccountStatementUrl($account->id) }}" target="_blank" rel="noopener"
                                class="form-control text-decoration-none text-primary fw-bold">
                                {{ $account->name }}
                            </a>
                        @else
                            <span class="form-control">{{ $account?->name }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="form-control">{{ @$cost_centers->firstWhere('id', $constraintDetails->cost_center_id)->title }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->statement }}</span>
                    </td>
                </tr>
            @endif
        @endforeach
        @foreach ($obj->constraintDetails as $constraintDetails)
            @if ($constraintDetails->type == 'creditor')
                <tr class="item-row" id="creditor_append">
                    <td>
                        <span class="form-control">0</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->creditor_value }}</span>
                    </td>
                    <td>
                        @php
                            $account = $accounts->firstWhere('id', $constraintDetails?->account_id);
                        @endphp
                        @if ($canOpenAccountStatement && filled($account?->id))
                            <a href="{{ $buildAccountStatementUrl($account->id) }}" target="_blank" rel="noopener"
                                class="form-control text-decoration-none text-primary fw-bold">
                                {{ $account->name }}
                            </a>
                        @else
                            <span class="form-control">{{ $account?->name }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="form-control">{{ @$cost_centers->firstWhere('id', $constraintDetails->cost_center_id)->title }}</span>
                    </td>
                    <td>
                        <span class="form-control">{{ $constraintDetails->statement }}</span>
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td><span class="form-control">{{$obj->value}}</span></td>
            <td><span class="form-control">{{$obj->value}}</span></td>
            <td>{{ __("finance::finance.Total") }}</td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>
    </table>

    <hr>
    <h4>{{ __('finance::finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $file)
                    <div class="uploadedImage">
                        <img src="{{ showFile($file->file) }}" alt="">
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</div>
