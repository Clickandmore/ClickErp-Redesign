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
    <form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
        @csrf
        <!-- First row with two text inputs -->
        <div class="col-md-6">
            <label for="taxRateExpenditure" class="form-label">{{ __('finance::finance.Tax expense ratio') }}:</label>
            <input type="hidden" value="tax_rate_expenditure" id="taxRateExpenditureTitle"
                name="data[tax_rate_expenditure][title]">
            {{-- <input type="text" class="form-control tax-rate-value" value="{{ $obj->get('tax_rate_expenditure')->value ?? 0 }}" id="taxRateExpenditureValue" name="data[tax_rate_expenditure][value]"> --}}
            <label for="TextInput" class="form-label">
                {{--            {{ __('finance::finance.taxSettings') }} --}}
                {!! addUpButton(route('finance.tax-setting.create'), 'tax_rate_expenditure', false, 'name', true) !!}
            </label> <select name="data[tax_rate_expenditure][value]" id="tax_rate_expenditure"
                class="form-control tax-rate-value">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($tax_settings as $item)
                    <option value="{{ $item->id }}"
                        {{ $obj->get('tax_rate_expenditure') && $obj->get('tax_rate_expenditure')->value == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                        {{ isset($item->value) ? ' - ' . $item->value . '%' : '' }}
                        {{ isset($item->account) ? ' - ' . ($item->account->code ?? '') . ' / ' . ($item->account->name ?? '') : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="taxRateRevenue" class="form-label">{{ __('finance::finance.Tax to revenue ratio') }}:</label>
            <input type="hidden" value="tax_rate_revenue" id="taxRateRevenueTitle" name="data[tax_rate_revenue][title]">
            <label for="TextInput" class="form-label">
                            {{ __('finance::finance.taxSettings') }}
                {!! addUpButton(route('finance.tax-setting.create'), 'tax_rate_revenue', false, 'name', true) !!}
            </label>
            <select name="data[tax_rate_revenue][value]" id="tax_rate_revenue" class="form-control tax-rate-value">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($tax_settings as $item)
                    <option value="{{ $item->id }}"
                        {{ $obj->get('tax_rate_revenue') && $obj->get('tax_rate_revenue')->value == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                        {{ isset($item->value) ? ' - ' . $item->value . '%' : '' }}
                        {{ isset($item->account) ? ' - ' . ($item->account->code ?? '') . ' / ' . ($item->account->name ?? '') : '' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">
                اختر إعداد الضريبة المرتبط بحساب ضريبة الإيرادات مثل `22200 / حساب الضريبة على الايرادات`.
            </small>
        </div>

        <div class="col-md-6">
            <label for="revenue_tax_account" class="form-label">{{ __('finance::finance.revenue_tax_account') }}:</label>
            <input type="hidden" value="revenue_tax_account" name="data[revenue_tax_account][title]">
            <label for="revenue_tax_account" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'revenue_tax_account', false, 'name', true) !!}
            </label>
            <select name="data[revenue_tax_account][value]" id="revenue_tax_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($revenue_tax_accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ (string) ($obj->get('revenue_tax_account')->value ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->code }} / {{ $item->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ __('finance::finance.revenue_tax_account_hint') }}</small>
        </div>

        <!-- Bonds Accounts -->
        <div class="col-md-6">
            <label for="taxRateRevenue" class="form-label">{{ __('finance::finance.Bond Sarf Account') }}:</label>
            <input type="hidden" value="bond_sarf_account" id="taxRateRevenueTitle" name="data[bond_sarf_account][title]">
            <label for="TextInput" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'bond_sarf_account', false, 'name', true) !!}
            </label>
            <select name="data[bond_sarf_account][value]" id="bond_sarf_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ $obj->get('bond_sarf_account') && $obj->get('bond_sarf_account')->value == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="taxRateRevenue" class="form-label">{{ __('finance::finance.Bond Qabd Account') }}:</label>
            <input type="hidden" value="bond_qabd_account" id="bondQabdAccount" name="data[bond_qabd_account][title]">
            <label for="TextInput" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'bond_qabd_account', false, 'name', true) !!}
            </label>
            <select name="data[bond_qabd_account][value]" id="bond_qabd_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ $obj->get('bond_qabd_account') && $obj->get('bond_qabd_account')->value == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="musaned_account" class="form-label">{{ __('finance::finance.Musaned Account') }}:</label>
            <input type="hidden" value="musaned_account" name="data[musaned_account][title]">
            <label for="musaned_account" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'musaned_account', false, 'name', true) !!}
            </label>
            <select name="data[musaned_account][value]" id="musaned_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($musaned_source_accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ (string) ($obj->get('musaned_account')->value ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->code }} / {{ $item->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ __('finance::finance.musaned_account_hint') }}</small>
        </div>

        <div class="col-md-6">
            <label for="musaned_intermediate_account" class="form-label">{{ __('finance::finance.Musaned Intermediate Account') }}:</label>
            <input type="hidden" value="musaned_intermediate_account" name="data[musaned_intermediate_account][title]">
            <label for="musaned_intermediate_account" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'musaned_intermediate_account', false, 'name', true) !!}
            </label>
            <select name="data[musaned_intermediate_account][value]" id="musaned_intermediate_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($musaned_accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ (string) ($obj->get('musaned_intermediate_account')->value ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->code }} / {{ $item->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ __('finance::finance.musaned_intermediate_account_hint') }}</small>
        </div>

        <div class="col-md-6">
            <label for="musaned_commission_account" class="form-label">{{ __('finance::finance.Musaned Commission Account') }}:</label>
            <input type="hidden" value="musaned_commission_account" name="data[musaned_commission_account][title]">
            <label for="musaned_commission_account" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'musaned_commission_account', false, 'name', true) !!}
            </label>
            <select name="data[musaned_commission_account][value]" id="musaned_commission_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($debtor_accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ (string) ($obj->get('musaned_commission_account')->value ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->code }} / {{ $item->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ __('finance::finance.musaned_commission_account_hint') }}</small>
        </div>

        <div class="col-md-6">
            <label for="expense_tax_account" class="form-label">{{ __('finance::finance.Expense Tax Account') }}:</label>
            <input type="hidden" value="expense_tax_account" name="data[expense_tax_account][title]">
            <label for="expense_tax_account" class="form-label">
                            {{ __('finance::finance.Directory account') }}
                {!! addUpButton(route('finance.accounts.create'), 'expense_tax_account', false, 'name', true) !!}
            </label>
            <select name="data[expense_tax_account][value]" id="expense_tax_account" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach ($debtor_accounts as $item)
                    <option value="{{ $item->id }}"
                        {{ (string) ($obj->get('expense_tax_account')->value ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->code }} / {{ $item->name }}</option>
                @endforeach
            </select>
            <small class="text-muted d-block mt-1">{{ __('finance::finance.expense_tax_account_hint') }}</small>
        </div>

        <div class="col-12">
            <hr class="my-2">
            <h5 class="mb-2">{{ __('finance::finance.rental_maid_expense_accounts') }}</h5>
            <p class="text-muted mb-0">{{ __('finance::finance.rental_maid_expense_accounts_hint') }}</p>
        </div>

        @forelse ($rental_maid_expense_definitions as $definition)
            <div class="col-md-6">
                <label for="{{ $definition['setting_key'] }}" class="form-label">{{ $definition['display_label'] }}</label>
                <input type="hidden" value="{{ $definition['setting_key'] }}"
                    name="data[{{ $definition['setting_key'] }}][title]">
                <label for="{{ $definition['setting_key'] }}" class="form-label">
                                {{ __('finance::finance.Directory account') }}
                    {!! addUpButton(route('finance.accounts.create'), $definition['setting_key'], false, 'name', true) !!}
                </label>
                <select name="data[{{ $definition['setting_key'] }}][value]" id="{{ $definition['setting_key'] }}"
                    class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($debtor_accounts as $item)
                        <option value="{{ $item->id }}"
                            {{ (string) ($definition['selected_account_id'] ?? '') === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->code }} / {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning mb-0">{{ __('finance::finance.rental_maid_expense_accounts_empty') }}</div>
            </div>
        @endforelse
        <!-- Bonds Accounts -->

        <!-- Cost Center Radio Buttons -->
        {{-- <div class="col-md-6">
        <label for="costCenter" class="form-label">{{ __("finance::finance.Do you want cost centers to be required?") }}:</label>
        <input type="hidden" value="cost_center" id="costCenterTitle" name="data[cost_center][title]">
        <input type="radio" class="tax-rate-value" value="on" name="data[cost_center][value]" {{ ($obj->get('cost_center')->value ?? 'off') == 'on' ? 'checked' : '' }}> {{ __("finance::finance.Yes") }}
        <input type="radio" class="tax-rate-value" value="off" name="data[cost_center][value]" {{ ($obj->get('cost_center')->value ?? 'off') == 'off' ? 'checked' : '' }}> {{ __("finance::finance.No") }}
    </div> --}}

        @if (auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
            <div class="col-md-6">
                <label class="form-label">{{ __('finance::finance.Screens need to cost centers') }}:</label>
                <div class="form-check">
                    <input type="hidden" name="data[cost_center_sarf_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="costCenterSarfBond"
                        name="data[cost_center_sarf_bond][value]"
                        {{ ($obj->get('cost_center_sarf_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="cost_center_sarf_bond" name="data[cost_center_sarf_bond][title]">
                    <label class="form-check-label" for="costCenterSarfBond">{{ __('finance::finance.sarf_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[cost_center_qabd_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="costCenterQabdBond"
                        name="data[cost_center_qabd_bond][value]"
                        {{ ($obj->get('cost_center_qabd_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="cost_center_qabd_bond" name="data[cost_center_qabd_bond][title]">
                    <label class="form-check-label" for="costCenterQabdBond">{{ __('finance::finance.qabd_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[cost_center_general_sarf_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="costCenterGeneralSarfBond"
                        name="data[cost_center_general_sarf_bond][value]"
                        {{ ($obj->get('cost_center_general_sarf_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="cost_center_general_sarf_bond"
                        name="data[cost_center_general_sarf_bond][title]">
                    <label class="form-check-label"
                        for="costCenterGeneralSarfBond">{{ __('finance::finance.general_sarf_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[cost_center_general_qabd_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="costCenterGeneralQabdBond"
                        name="data[cost_center_general_qabd_bond][value]"
                        {{ ($obj->get('cost_center_general_qabd_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="cost_center_general_qabd_bond"
                        name="data[cost_center_general_qabd_bond][title]">
                    <label class="form-check-label"
                        for="costCenterGeneralQabdBond">{{ __('finance::finance.general_qabd_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[cost_center_account_transfer][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="costCenterAccountTransfer"
                        name="data[cost_center_account_transfer][value]"
                        {{ ($obj->get('cost_center_account_transfer')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="cost_center_account_transfer"
                        name="data[cost_center_account_transfer][title]">
                    <label class="form-check-label"
                        for="costCenterAccountTransfer">{{ __('finance::finance.account_transfers') }}</label>
                </div>
            </div>

            <!-- Second row with multiple checkboxes -->
            <div class="col-md-6">
                <label class="form-label">{{ __('finance::finance.Screens need to be approved') }}:</label>
                <div class="form-check">
                    <input type="hidden" name="data[sarf_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="sarfBond"
                        name="data[sarf_bond][value]" {{ ($obj->get('sarf_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="sarf_bond" name="data[sarf_bond][title]">
                    <label class="form-check-label" for="sarfBond">{{ __('finance::finance.sarf_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[qabd_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="qabdBond"
                        name="data[qabd_bond][value]" {{ ($obj->get('qabd_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="qabd_bond" name="data[qabd_bond][title]">
                    <label class="form-check-label" for="qabdBond">{{ __('finance::finance.qabd_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[general_sarf_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="generalSarfBond"
                        name="data[general_sarf_bond][value]"
                        {{ ($obj->get('general_sarf_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="general_sarf_bond" name="data[general_sarf_bond][title]">
                    <label class="form-check-label" for="generalSarfBond">{{ __('finance::finance.general_sarf_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[general_qabd_bond][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="generalQabdBond"
                        name="data[general_qabd_bond][value]"
                        {{ ($obj->get('general_qabd_bond')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="general_qabd_bond" name="data[general_qabd_bond][title]">
                    <label class="form-check-label" for="generalQabdBond">{{ __('finance::finance.general_qabd_bond') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[account_transfer][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="accountTransfer"
                        name="data[account_transfer][value]"
                        {{ ($obj->get('account_transfer')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="account_transfer" name="data[account_transfer][title]">
                    <label class="form-check-label" for="accountTransfer">{{ __('finance::finance.account_transfers') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[bill_sales][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="bill_sales"
                           name="data[bill_sales][value]"
                        {{ ($obj->get('bill_sales')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="general_qabd_bond" name="data[bill_sales][title]">
                    <label class="form-check-label" for="bill_sales">{{ __('finance::finance.bill-sales') }}</label>
                </div>
                <div class="form-check">
                    <input type="hidden" name="data[bill_purchases][value]" value="off">
                    <input class="form-check-input" type="checkbox" value="on" id="bill_purchases"
                           name="data[bill_purchases][value]"
                        {{ ($obj->get('bill_purchases')->value ?? 'off') == 'on' ? 'checked' : '' }}>
                    <input type="hidden" value="bill_purchases" name="data[bill_purchases][title]">
                    <label class="form-check-label" for="bill_sales">{{ __('finance::finance.bill_purchases') }}</label>
                </div>
            </div>
        @endif

        <!-- Modal footer with buttons -->
        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
        </div>
    </form>
@endsection
@section('js')
    <script>
        $('.select2').select2({
            dropdownParent: $('#upCreateModal .modal-body'),
        });
        $('.select2').select2();
    </script>
@endsection
