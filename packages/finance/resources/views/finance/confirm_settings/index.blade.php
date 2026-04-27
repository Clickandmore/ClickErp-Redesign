@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .finance-settings-page {
            --settings-border: #e5e7eb;
            --settings-muted: #667085;
            --settings-surface: #ffffff;
            --settings-soft: #f8fafc;
            --settings-accent: #0f766e;
            --settings-warning: #b45309;
            color: #1f2937;
        }

        .finance-settings-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 18px;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            background: var(--settings-surface);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
        }

        .finance-settings-header__content {
            max-width: 760px;
        }

        .finance-settings-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }

        .finance-settings-header p {
            margin: 8px 0 0;
            color: var(--settings-muted);
            line-height: 1.8;
        }

        .finance-settings-save {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 160px;
        }

        .settings-nav {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .settings-nav__item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 58px;
            padding: 12px 14px;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            color: #1f2937;
            background: #ffffff;
            text-decoration: none;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .settings-nav__item:hover,
        .settings-nav__item:focus {
            color: #0f766e;
            border-color: #99f6e4;
            box-shadow: 0 10px 20px rgba(15, 118, 110, .08);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .settings-nav__icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 8px;
            color: #0f766e;
            background: #ecfdf5;
        }

        .settings-nav__title {
            display: block;
            font-weight: 800;
            line-height: 1.4;
        }

        .settings-nav__count {
            display: block;
            margin-top: 2px;
            color: var(--settings-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .settings-section {
            margin-bottom: 14px;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            background: var(--settings-surface);
            overflow: hidden;
        }

        .settings-section summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 16px 18px;
            cursor: pointer;
            list-style: none;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .settings-section summary::-webkit-details-marker {
            display: none;
        }

        .settings-section-title {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
        }

        .settings-section-hint {
            margin: 5px 0 0;
            color: var(--settings-muted);
            line-height: 1.7;
            font-size: 13px;
        }

        .settings-section-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            color: var(--settings-accent);
            background: #ffffff;
            transition: transform .2s ease;
        }

        .settings-section[open] .settings-section-icon {
            transform: rotate(180deg);
        }

        .settings-section-body {
            padding: 18px;
            border-top: 1px solid var(--settings-border);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .settings-grid--three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .setting-field {
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 14px;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            background: var(--settings-soft);
        }

        .setting-field__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .setting-field label {
            margin: 0;
            font-weight: 800;
            line-height: 1.6;
        }

        .setting-field__tools {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .setting-info {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #bfdbfe;
            border-radius: 50%;
            color: #1d4ed8;
            background: #eff6ff;
            cursor: help;
        }

        .setting-field small {
            display: block;
            margin-top: 8px;
            color: var(--settings-muted);
            line-height: 1.8;
        }

        .setting-field__tree {
            margin-top: 8px;
            padding: 8px 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #475467;
            background: #ffffff;
            font-size: 12px;
            line-height: 1.7;
        }

        .setting-field .select2-container,
        .setting-field .form-control {
            width: 100% !important;
        }

        .approval-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .approval-option {
            display: flex;
            gap: 10px;
            min-height: 104px;
            padding: 14px;
            border: 1px solid var(--settings-border);
            border-radius: 8px;
            background: #ffffff;
        }

        .approval-option input {
            margin-top: 5px;
            flex: 0 0 auto;
        }

        .approval-option strong {
            display: block;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .approval-option span {
            color: var(--settings-muted);
            line-height: 1.8;
        }

        .settings-actions {
            position: sticky;
            bottom: 0;
            z-index: 5;
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
            padding: 14px 0 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), #ffffff 35%);
        }

        @media (max-width: 991.98px) {
            .settings-grid,
            .settings-grid--three,
            .approval-options,
            .settings-nav {
                grid-template-columns: 1fr;
            }

            .finance-settings-header {
                flex-direction: column;
            }

            .finance-settings-save {
                width: 100%;
                justify-content: stretch;
            }

            .finance-settings-save .btn {
                width: 100%;
            }
        }

        @media (min-width: 992px) and (max-width: 1199.98px) {
            .settings-grid--three,
            .settings-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
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
    @php
        $settingValue = fn (string $key, $default = '') => (string) ($obj->get($key)->value ?? $default);
        $selected = fn (string $key, $id) => $settingValue($key) === (string) $id ? 'selected' : '';
        $dailyConstraintsApproval = $settingValue('daily_constraints', 'off');
        $accountTreeLabel = __('finance::finance.account_tree_position');
    @endphp

    <div class="finance-settings-page">
        <form class="from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
            @csrf

            <div class="finance-settings-header">
                <div class="finance-settings-header__content">
                    <h2>{{ $bladeTitle }}</h2>
                    <p>{{ __('finance::finance.confirm_settings_intro') }}</p>
                </div>
                <div class="finance-settings-save">
                    {!! submitButton($storeRoute) !!}
                </div>
            </div>

            <div class="settings-nav">
                <a class="settings-nav__item" href="#tax-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-percent"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.confirm_settings_tax_section') }}</span>
                        <span class="settings-nav__count">4 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#bonds-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-exchange"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.confirm_settings_bonds_section') }}</span>
                        <span class="settings-nav__count">2 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#musaned-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-credit-card"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.confirm_settings_musaned_section') }}</span>
                        <span class="settings-nav__count">3 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#rental-maid-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-list-ul"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.rental_maid_expense_accounts') }}</span>
                        <span class="settings-nav__count">{{ count($rental_maid_expense_definitions) }} {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
            </div>

            <details class="settings-section" id="tax-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.confirm_settings_tax_section') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.confirm_settings_tax_section_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="tax_rate_expenditure">{{ __('finance::finance.Tax expense ratio') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.tax-setting.create'), 'tax_rate_expenditure', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.tax_rate_expenditure_info') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="tax_rate_expenditure" name="data[tax_rate_expenditure][title]">
                            <select name="data[tax_rate_expenditure][value]" id="tax_rate_expenditure" class="form-control tax-rate-value">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($tax_settings as $item)
                                    <option value="{{ $item->id }}" {{ $selected('tax_rate_expenditure', $item->id) }}>
                                        {{ $item->name }}
                                        {{ isset($item->value) ? ' - ' . $item->value . '%' : '' }}
                                        {{ isset($item->account) ? ' - ' . ($item->account->code ?? '') . ' / ' . ($item->account->name ?? '') : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.tax_rate_expenditure_info') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="tax_rate_revenue">{{ __('finance::finance.Tax to revenue ratio') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.tax-setting.create'), 'tax_rate_revenue', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.tax_rate_revenue_info') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="tax_rate_revenue" name="data[tax_rate_revenue][title]">
                            <select name="data[tax_rate_revenue][value]" id="tax_rate_revenue" class="form-control tax-rate-value">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($tax_settings as $item)
                                    <option value="{{ $item->id }}" {{ $selected('tax_rate_revenue', $item->id) }}>
                                        {{ $item->name }}
                                        {{ isset($item->value) ? ' - ' . $item->value . '%' : '' }}
                                        {{ isset($item->account) ? ' - ' . ($item->account->code ?? '') . ' / ' . ($item->account->name ?? '') : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.tax_rate_revenue_info') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="revenue_tax_account">{{ __('finance::finance.revenue_tax_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'revenue_tax_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.revenue_tax_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="revenue_tax_account" name="data[revenue_tax_account][title]">
                            <select name="data[revenue_tax_account][value]" id="revenue_tax_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($revenue_tax_accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('revenue_tax_account', $item->id) }}>
                                        {{ $item->code }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.revenue_tax_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.revenue_tax_account_tree_hint') }}
                            </div>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="expense_tax_account">{{ __('finance::finance.Expense Tax Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'expense_tax_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.expense_tax_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="expense_tax_account" name="data[expense_tax_account][title]">
                            <select name="data[expense_tax_account][value]" id="expense_tax_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($debtor_accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('expense_tax_account', $item->id) }}>
                                        {{ $item->code }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.expense_tax_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.expense_tax_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="bonds-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.confirm_settings_bonds_section') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.confirm_settings_bonds_section_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="bond_sarf_account">{{ __('finance::finance.Bond Sarf Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'bond_sarf_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.bond_sarf_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="bond_sarf_account" name="data[bond_sarf_account][title]">
                            <select name="data[bond_sarf_account][value]" id="bond_sarf_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('bond_sarf_account', $item->id) }}>
                                        {{ $item->code ?? '' }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.bond_sarf_account_tree_hint') }}
                            </div>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="bond_qabd_account">{{ __('finance::finance.Bond Qabd Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'bond_qabd_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.bond_qabd_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="bond_qabd_account" name="data[bond_qabd_account][title]">
                            <select name="data[bond_qabd_account][value]" id="bond_qabd_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('bond_qabd_account', $item->id) }}>
                                        {{ $item->code ?? '' }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.bond_qabd_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="musaned-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.confirm_settings_musaned_section') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.confirm_settings_musaned_section_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid settings-grid--three">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="musaned_account">{{ __('finance::finance.Musaned Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'musaned_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.musaned_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="musaned_account" name="data[musaned_account][title]">
                            <select name="data[musaned_account][value]" id="musaned_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($musaned_source_accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('musaned_account', $item->id) }}>
                                        {{ $item->code }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.musaned_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.musaned_account_tree_hint') }}
                            </div>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="musaned_intermediate_account">{{ __('finance::finance.Musaned Intermediate Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'musaned_intermediate_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.musaned_intermediate_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="musaned_intermediate_account" name="data[musaned_intermediate_account][title]">
                            <select name="data[musaned_intermediate_account][value]" id="musaned_intermediate_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($musaned_accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('musaned_intermediate_account', $item->id) }}>
                                        {{ $item->code }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.musaned_intermediate_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.musaned_intermediate_account_tree_hint') }}
                            </div>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="musaned_commission_account">{{ __('finance::finance.Musaned Commission Account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'musaned_commission_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.musaned_commission_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="musaned_commission_account" name="data[musaned_commission_account][title]">
                            <select name="data[musaned_commission_account][value]" id="musaned_commission_account" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($debtor_accounts as $item)
                                    <option value="{{ $item->id }}" {{ $selected('musaned_commission_account', $item->id) }}>
                                        {{ $item->code }} / {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small>{{ __('finance::finance.musaned_commission_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.musaned_commission_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="rental-maid-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.rental_maid_expense_accounts') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.rental_maid_expense_accounts_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid settings-grid--three">
                        @forelse ($rental_maid_expense_definitions as $definition)
                            <div class="setting-field">
                                <div class="setting-field__top">
                                    <label for="{{ $definition['setting_key'] }}">{{ $definition['display_label'] }}</label>
                                    <span class="setting-field__tools">
                                        {!! addUpButton(route('finance.accounts.create'), $definition['setting_key'], false, 'name', true) !!}
                                        <span class="setting-info" title="{{ __('finance::finance.rental_maid_account_tree_hint', ['code' => $definition['code']]) }}"><i class="fa fa-info"></i></span>
                                    </span>
                                </div>
                                <input type="hidden" value="{{ $definition['setting_key'] }}" name="data[{{ $definition['setting_key'] }}][title]">
                                <select name="data[{{ $definition['setting_key'] }}][value]" id="{{ $definition['setting_key'] }}" class="form-control select2">
                                    <option value="">{{ __('finance::finance.choose') }}</option>
                                    @foreach ($debtor_accounts as $item)
                                        <option value="{{ $item->id }}" {{ (string) ($definition['selected_account_id'] ?? '') === (string) $item->id ? 'selected' : '' }}>
                                            {{ $item->code }} / {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="setting-field__tree">
                                    <strong>{{ $accountTreeLabel }}:</strong>
                                    {{ __('finance::finance.rental_maid_account_tree_hint', ['code' => $definition['code']]) }}
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-warning mb-0">{{ __('finance::finance.rental_maid_expense_accounts_empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </details>

            <details class="settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.confirm_settings_approval_section') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.confirm_settings_approval_section_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <input type="hidden" value="daily_constraints" name="data[daily_constraints][title]">
                    <div class="approval-options">
                        <label class="approval-option" for="dailyConstraintsAuto">
                            <input type="radio" id="dailyConstraintsAuto" name="data[daily_constraints][value]" value="off" {{ $dailyConstraintsApproval !== 'on' ? 'checked' : '' }}>
                            <span>
                                <strong>{{ __('finance::finance.constraints_auto_approval') }}</strong>
                                <span>{{ __('finance::finance.constraints_auto_approval_hint') }}</span>
                            </span>
                        </label>
                        <label class="approval-option" for="dailyConstraintsManual">
                            <input type="radio" id="dailyConstraintsManual" name="data[daily_constraints][value]" value="on" {{ $dailyConstraintsApproval === 'on' ? 'checked' : '' }}>
                            <span>
                                <strong>{{ __('finance::finance.constraints_manual_approval') }}</strong>
                                <span>{{ __('finance::finance.constraints_manual_approval_hint') }}</span>
                            </span>
                        </label>
                    </div>
                    <small class="text-muted d-block mt-3">
                        <i class="fa fa-info-circle"></i>
                        {{ __('finance::finance.constraints_approval_permission_hint') }}
                    </small>
                </div>
            </details>

            @if (auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
                <details class="settings-section">
                    <summary>
                        <div>
                            <h3 class="settings-section-title">{{ __('finance::finance.confirm_settings_developer_section') }}</h3>
                            <p class="settings-section-hint">{{ __('finance::finance.confirm_settings_developer_section_hint') }}</p>
                        </div>
                        <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                    </summary>
                    <div class="settings-section-body">
                        <div class="settings-grid">
                            <div class="setting-field">
                                <label class="mb-2">{{ __('finance::finance.Screens need to cost centers') }}</label>
                                @foreach ([
                                    'cost_center_sarf_bond' => __('finance::finance.sarf_bond'),
                                    'cost_center_qabd_bond' => __('finance::finance.qabd_bond'),
                                    'cost_center_general_sarf_bond' => __('finance::finance.general_sarf_bond'),
                                    'cost_center_general_qabd_bond' => __('finance::finance.general_qabd_bond'),
                                    'cost_center_account_transfer' => __('finance::finance.account_transfers'),
                                ] as $key => $label)
                                    <div class="form-check">
                                        <input type="hidden" name="data[{{ $key }}][value]" value="off">
                                        <input class="form-check-input" type="checkbox" value="on" id="{{ $key }}" name="data[{{ $key }}][value]" {{ $settingValue($key, 'off') === 'on' ? 'checked' : '' }}>
                                        <input type="hidden" value="{{ $key }}" name="data[{{ $key }}][title]">
                                        <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="setting-field">
                                <label class="mb-2">{{ __('finance::finance.Screens need to be approved') }}</label>
                                @foreach ([
                                    'sarf_bond' => __('finance::finance.sarf_bond'),
                                    'qabd_bond' => __('finance::finance.qabd_bond'),
                                    'general_sarf_bond' => __('finance::finance.general_sarf_bond'),
                                    'general_qabd_bond' => __('finance::finance.general_qabd_bond'),
                                    'account_transfer' => __('finance::finance.account_transfers'),
                                    'bill_sales' => __('finance::finance.bill-sales'),
                                    'bill_purchases' => __('finance::finance.bill_purchases'),
                                ] as $key => $label)
                                    <div class="form-check">
                                        <input type="hidden" name="data[{{ $key }}][value]" value="off">
                                        <input class="form-check-input" type="checkbox" value="on" id="{{ $key }}" name="data[{{ $key }}][value]" {{ $settingValue($key, 'off') === 'on' ? 'checked' : '' }}>
                                        <input type="hidden" value="{{ $key }}" name="data[{{ $key }}][title]">
                                        <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </details>
            @endif

            <div class="settings-actions">
                {!! submitButton($storeRoute) !!}
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        $('.select2').select2({
            width: '100%'
        });
    </script>
@endsection
