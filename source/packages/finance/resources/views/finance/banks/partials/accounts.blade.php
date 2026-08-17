@php
    $rows = $accounts->isNotEmpty() ? $accounts : collect([null]);
@endphp

<div class="col-12">
    <h6 class="mb-2">{{ __('finance::finance.bank_accounts') }}</h6>
    <div class="table-responsive">
        <table class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th>{{ __('finance::finance.account_name') }}</th>
                <th>{{ __('finance::finance.bank_number') }}</th>
                <th>{{ __('finance::finance.iban') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section":".y-group","btnAdd":".btn-add-bank-account","btnRemove":".btnRemove"}'>
            @foreach($rows as $key => $account)
                <tr class="y-group">
                    <td>
                        @if($account)
                            <input type="hidden" name="accounts[{{ $key }}][id]" value="{{ $account->id }}" class="index-plus">
                        @endif
                        <input type="text" name="accounts[{{ $key }}][name]" value="{{ $account?->name }}"
                               class="form-control index-plus" data-validation="required">
                    </td>
                    <td>
                        <input type="text" name="accounts[{{ $key }}][bank_number]"
                               value="{{ $account?->bank_number }}" class="form-control index-plus">
                    </td>
                    <td>
                        <input type="text" name="accounts[{{ $key }}][iban]" value="{{ $account?->iban }}"
                               class="form-control index-plus" maxlength="50" dir="ltr">
                    </td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-success btn-add-bank-account">
                            <i class="fa fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btnRemove">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted">{{ __('finance::finance.bank_accounts_tree_hint') }}</small>
</div>
