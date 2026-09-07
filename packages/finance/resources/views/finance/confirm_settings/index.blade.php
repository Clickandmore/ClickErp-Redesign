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
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            color: #1f2937;
        }

        body:has(.finance-settings-page) {
            overflow-x: hidden;
        }

        body:has(.finance-settings-page) .wrapper {
            width: auto;
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
        }

        body:has(.finance-settings-page) .page-body,
        body:has(.finance-settings-page) .container-fluid,
        body:has(.finance-settings-page) .card,
        body:has(.finance-settings-page) .card-body {
            min-width: 0;
            max-width: 100%;
        }

        body:has(.finance-settings-page) .card,
        body:has(.finance-settings-page) .card-body {
            overflow-x: hidden !important;
        }

        .finance-settings-page *,
        .finance-settings-page *::before,
        .finance-settings-page *::after {
            box-sizing: border-box;
        }

        .finance-settings-page form,
        .finance-settings-header,
        .settings-nav,
        .settings-section,
        .settings-section-body,
        .settings-grid,
        .setting-field {
            min-width: 0;
            max-width: 100%;
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
            min-width: 0;
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
            overflow-wrap: anywhere;
        }

        .settings-nav__count {
            display: block;
            margin-top: 2px;
            color: var(--settings-muted);
            font-size: 12px;
            line-height: 1.4;
            overflow-wrap: anywhere;
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
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
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
            min-width: 0;
            overflow-wrap: anywhere;
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
            overflow-wrap: anywhere;
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
            overflow-wrap: anywhere;
        }

        .setting-field .select2-container,
        .setting-field .form-control {
            width: 100% !important;
            max-width: 100% !important;
        }

        .setting-field .select2-container .select2-selection--single,
        .setting-field .select2-container .select2-selection--multiple {
            max-width: 100%;
        }

        .setting-field .select2-container .select2-selection__rendered {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        $automaticJournalEntries = $settingValue('automatic_journal_entries', 'on');
        $accountTreeLabel = __('finance::finance.account_tree_position');
        $accountOption = fn ($id) => $selected_accounts->get((int) $id);
        $settingAccountOption = fn (string $key) => $accountOption($settingValue($key));
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
                <a class="settings-nav__item" href="#automatic-journal-entries-section">
                    <span class="settings-nav__icon"><i class="fa fa-cogs"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.automatic_journal_entries') }}</span>
                        <span class="settings-nav__count">1 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#constraints-approval-section">
                    <span class="settings-nav__icon"><i class="fa fa-check-circle"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.confirm_settings_approval_section') }}</span>
                        <span class="settings-nav__count">1 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
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
                <a class="settings-nav__item" href="#rental-contract-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-file-contract"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.rental_contract_accounts_section') }}</span>
                        <span class="settings-nav__count">5 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#musaned-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-credit-card"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.confirm_settings_musaned_section') }}</span>
                        <span class="settings-nav__count">3 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#general-invoice-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-file-invoice-dollar"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.general_invoice_accounts') }}</span>
                        <span class="settings-nav__count">1 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#ingaz-delegation-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-file-signature"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.ingaz_delegation_accounts') }}</span>
                        <span class="settings-nav__count">4 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#nationality-accounts-section">
                    <span class="settings-nav__icon"><i class="fa fa-sitemap"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.nationality_accounts') }}</span>
                        <span class="settings-nav__count">{{ count($nationality_account_definitions) }} {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#rental-maid-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-list-ul"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.rental_maid_expense_accounts') }}</span>
                        <span class="settings-nav__count">{{ count($rental_maid_expense_definitions) * 3 }} {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
                <a class="settings-nav__item" href="#shelter-living-settings-section">
                    <span class="settings-nav__icon"><i class="fa fa-home"></i></span>
                    <span>
                        <span class="settings-nav__title">{{ __('finance::finance.shelter_living_expense_account') }}</span>
                        <span class="settings-nav__count">1 {{ __('finance::finance.settings_fields') }}</span>
                    </span>
                </a>
            </div>

            <details class="settings-section" id="automatic-journal-entries-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.automatic_journal_entries') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.automatic_journal_entries_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <input type="hidden" value="automatic_journal_entries" name="data[automatic_journal_entries][title]">
                    <div class="approval-options">
                        <label class="approval-option" for="automaticJournalEntriesOn">
                            <input type="radio" id="automaticJournalEntriesOn" name="data[automatic_journal_entries][value]" value="on" {{ $automaticJournalEntries !== 'off' ? 'checked' : '' }}>
                            <span>
                                <strong>{{ __('finance::finance.automatic_journal_entries_on') }}</strong>
                                <span>{{ __('finance::finance.automatic_journal_entries_on_hint') }}</span>
                            </span>
                        </label>
                        <label class="approval-option" for="automaticJournalEntriesOff">
                            <input type="radio" id="automaticJournalEntriesOff" name="data[automatic_journal_entries][value]" value="off" {{ $automaticJournalEntries === 'off' ? 'checked' : '' }}>
                            <span>
                                <strong>{{ __('finance::finance.automatic_journal_entries_off') }}</strong>
                                <span>{{ __('finance::finance.automatic_journal_entries_off_hint') }}</span>
                            </span>
                        </label>
                    </div>
                    <small class="text-muted d-block mt-3">
                        <i class="fa fa-info-circle"></i>
                        {{ __('finance::finance.automatic_journal_entries_scope_hint') }}
                    </small>
                </div>
            </details>

            <details class="settings-section" id="constraints-approval-section" open>
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
                            <p>{{ __('finance::finance.tax_single_source_hint') }}</p>
                            <a href="{{ route('finance.tax-setting.index') }}" class="btn btn-outline-primary">{{ __('finance::finance.taxSettings') }}</a>
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
                            <select name="data[bond_sarf_account][value]" id="bond_sarf_account"
                                    class="form-control account-ajax-select" data-account-scope="leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('bond_sarf_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
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
                            <select name="data[bond_qabd_account][value]" id="bond_qabd_account"
                                    class="form-control account-ajax-select" data-account-scope="leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('bond_qabd_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.bond_qabd_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="rental-contract-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.rental_contract_accounts_section') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.rental_contract_accounts_section_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="rental_contract_revenue_account">{{ __('finance::finance.rental_contract_revenue_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'rental_contract_revenue_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="rental_contract_revenue_account" name="data[rental_contract_revenue_account][title]">
                            <select name="data[rental_contract_revenue_account][value]" id="rental_contract_revenue_account"
                                    class="form-control account-ajax-select" data-account-scope="creditor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('rental_contract_revenue_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.rental_contract_revenue_account_hint') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="rental_contract_exempt_salary_revenue_account">{{ __('finance::finance.rental_contract_exempt_salary_revenue_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'rental_contract_exempt_salary_revenue_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="rental_contract_exempt_salary_revenue_account"
                                name="data[rental_contract_exempt_salary_revenue_account][title]">
                            <select name="data[rental_contract_exempt_salary_revenue_account][value]"
                                    id="rental_contract_exempt_salary_revenue_account"
                                    class="form-control account-ajax-select" data-account-scope="creditor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('rental_contract_exempt_salary_revenue_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.rental_contract_exempt_salary_revenue_account_hint') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="rental_contract_clients_parent_account">{{ __('finance::finance.rental_contract_clients_parent_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'rental_contract_clients_parent_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="rental_contract_clients_parent_account" name="data[rental_contract_clients_parent_account][title]">
                            <select name="data[rental_contract_clients_parent_account][value]" id="rental_contract_clients_parent_account"
                                    class="form-control account-ajax-select" data-account-scope="rental_client_parent">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('rental_contract_clients_parent_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.rental_contract_clients_parent_account_hint') }}</small>
                        </div>

                        @foreach ([
                            'rental_contract_cash_account' => 'rental_contract_cash_account',
                            'rental_contract_bank_account' => 'rental_contract_bank_account',
                            'rental_contract_electronic_account' => 'rental_contract_electronic_account',
                        ] as $settingKey => $translationKey)
                            <div class="setting-field">
                                <div class="setting-field__top">
                                    <label for="{{ $settingKey }}">{{ __('finance::finance.' . $translationKey) }}</label>
                                    <span class="setting-field__tools">
                                        {!! addUpButton(route('finance.accounts.create'), $settingKey, false, 'name', true) !!}
                                    </span>
                                </div>
                                <input type="hidden" value="{{ $settingKey }}" name="data[{{ $settingKey }}][title]">
                                <select name="data[{{ $settingKey }}][value]" id="{{ $settingKey }}"
                                        class="form-control account-ajax-select" data-account-scope="rental_receipt">
                                    <option value="">{{ __('finance::finance.choose') }}</option>
                                    @if ($item = $settingAccountOption($settingKey))
                                        <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                    @endif
                                </select>
                                <small>{{ __('finance::finance.' . $translationKey . '_hint') }}</small>
                            </div>
                        @endforeach
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
                            <select name="data[musaned_account][value]" id="musaned_account"
                                    class="form-control account-ajax-select" data-account-scope="musaned_source">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('musaned_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
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
                            <select name="data[musaned_intermediate_account][value]" id="musaned_intermediate_account"
                                    class="form-control account-ajax-select" data-account-scope="musaned_intermediate">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('musaned_intermediate_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
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
                            <select name="data[musaned_commission_account][value]" id="musaned_commission_account"
                                    class="form-control account-ajax-select" data-account-scope="debtor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('musaned_commission_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.musaned_commission_account_hint') }}</small>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.musaned_commission_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="general-invoice-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.general_invoice_accounts') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.general_invoice_accounts_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="general_invoice_revenue_account">{{ __('finance::finance.general_invoice_revenue_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'general_invoice_revenue_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="general_invoice_revenue_account" name="data[general_invoice_revenue_account][title]">
                            <select name="data[general_invoice_revenue_account][value]" id="general_invoice_revenue_account"
                                    class="form-control account-ajax-select" data-account-scope="creditor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('general_invoice_revenue_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.general_invoice_revenue_account_hint') }}</small>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="ingaz-delegation-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.ingaz_delegation_accounts') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.ingaz_delegation_accounts_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid settings-grid--three">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="ingaz_delegation_revenue_account">{{ __('finance::finance.ingaz_delegation_revenue_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'ingaz_delegation_revenue_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="ingaz_delegation_revenue_account" name="data[ingaz_delegation_revenue_account][title]">
                            <select name="data[ingaz_delegation_revenue_account][value]" id="ingaz_delegation_revenue_account"
                                    class="form-control account-ajax-select" data-account-scope="creditor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('ingaz_delegation_revenue_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.ingaz_delegation_revenue_account_hint') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="ingaz_delegation_ingaz_account">{{ __('finance::finance.ingaz_delegation_ingaz_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'ingaz_delegation_ingaz_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="ingaz_delegation_ingaz_account" name="data[ingaz_delegation_ingaz_account][title]">
                            <select name="data[ingaz_delegation_ingaz_account][value]" id="ingaz_delegation_ingaz_account"
                                    class="form-control account-ajax-select" data-account-scope="creditor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('ingaz_delegation_ingaz_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.ingaz_delegation_ingaz_account_hint') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="ingaz_delegation_cost_account">{{ __('finance::finance.ingaz_delegation_cost_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'ingaz_delegation_cost_account', false, 'name', true) !!}
                                </span>
                            </div>
                            <input type="hidden" value="ingaz_delegation_cost_account" name="data[ingaz_delegation_cost_account][title]">
                            <select name="data[ingaz_delegation_cost_account][value]" id="ingaz_delegation_cost_account"
                                    class="form-control account-ajax-select" data-account-scope="debtor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $settingAccountOption('ingaz_delegation_cost_account'))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <small>{{ __('finance::finance.ingaz_delegation_cost_account_hint') }}</small>
                        </div>

                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="ingaz_delegation_fixed_cost">{{ __('finance::finance.ingaz_delegation_fixed_cost') }}</label>
                            </div>
                            <input type="hidden" value="ingaz_delegation_fixed_cost" name="data[ingaz_delegation_fixed_cost][title]">
                            <input type="number" step="0.01" min="0" name="data[ingaz_delegation_fixed_cost][value]"
                                   id="ingaz_delegation_fixed_cost" class="form-control"
                                   value="{{ $settingValue('ingaz_delegation_fixed_cost', '137') }}">
                            <small>{{ __('finance::finance.ingaz_delegation_fixed_cost_hint') }}</small>
                        </div>
                    </div>
                </div>
            </details>

            <details class="settings-section" id="nationality-accounts-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.nationality_accounts') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.nationality_accounts_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        {{ __('finance::finance.nationality_accounts_creation_hint') }}
                    </div>
                    <h4 class="mb-3">{{ __('finance::finance.nationality_existing_account_assignments') }}</h4>
                    <p class="text-muted mb-3">{{ __('finance::finance.nationality_existing_account_assignments_hint') }}</p>
                    @forelse ($nationality_account_assignments as $nationalityAssignment)
                        <details class="settings-section mb-3">
                            <summary>
                                <div>
                                    <h4 class="settings-section-title">{{ $nationalityAssignment['nationality_name'] }}</h4>
                                    <p class="settings-section-hint">
                                        {{ __('finance::finance.nationality_assigned_accounts_count', [
                                            'selected' => collect($nationalityAssignment['accounts'])->whereNotNull('selected_account_id')->where('selected_account_id', '!=', '')->count(),
                                            'total' => count($nationalityAssignment['accounts']),
                                        ]) }}
                                    </p>
                                </div>
                                <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                            </summary>
                            <div class="settings-section-body">
                                <div class="settings-grid settings-grid--three">
                                    @foreach ($nationalityAssignment['accounts'] as $assignment)
                                        <div class="setting-field">
                                            <label class="mb-2" for="{{ $assignment['setting_key'] }}">{{ $assignment['label'] }}</label>
                                            <input type="hidden" value="{{ $assignment['setting_key'] }}" name="data[{{ $assignment['setting_key'] }}][title]">
                                            <select name="data[{{ $assignment['setting_key'] }}][value]"
                                                    id="{{ $assignment['setting_key'] }}" class="form-control nationality-account-select">
                                                <option value="">{{ __('finance::finance.create_from_default_parent') }}</option>
                                                @if (!empty($assignment['selected_account_id']) && !empty($assignment['selected_account_label']))
                                                    <option value="{{ $assignment['selected_account_id'] }}" selected>{{ $assignment['selected_account_label'] }}</option>
                                                @endif
                                            </select>
                                            <small>{{ __('finance::finance.choose_existing_nationality_account_hint') }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="alert alert-warning">{{ __('finance::finance.no_nationalities_for_account_assignment') }}</div>
                    @endforelse

                    <hr class="my-4">
                    <h4 class="mb-3">{{ __('finance::finance.nationality_default_parent_accounts') }}</h4>
                    <p class="text-muted mb-3">{{ __('finance::finance.nationality_default_parent_accounts_hint') }}</p>
                    <div class="settings-grid settings-grid--three">
                        @foreach ($nationality_account_definitions as $definition)
                            <div class="setting-field">
                                <div class="setting-field__top">
                                    <label for="{{ $definition['setting_key'] }}">{{ $definition['label'] }}</label>
                                    <span class="setting-field__tools">
                                        {!! addUpButton(route('finance.accounts.create'), $definition['setting_key'], false, 'name', true) !!}
                                    </span>
                                </div>
                                <input type="hidden" value="{{ $definition['setting_key'] }}" name="data[{{ $definition['setting_key'] }}][title]">
                                <select name="data[{{ $definition['setting_key'] }}][value]"
                                        id="{{ $definition['setting_key'] }}" class="form-control nationality-account-select">
                                    <option value="">{{ __('finance::finance.automatic_default_parent') }}</option>
                                    @if (!empty($definition['selected_account_id']) && !empty($definition['selected_account_label']))
                                        <option value="{{ $definition['selected_account_id'] }}" selected>{{ $definition['selected_account_label'] }}</option>
                                    @endif
                                </select>
                                <small>{{ __('finance::finance.nationality_parent_account_hint') }}</small>
                            </div>
                        @endforeach
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
                                <select name="data[{{ $definition['setting_key'] }}][value]" id="{{ $definition['setting_key'] }}"
                                        class="form-control account-ajax-select" data-account-scope="debtor_leaf">
                                    <option value="">{{ __('finance::finance.choose') }}</option>
                                    @if ($item = $accountOption($definition['selected_account_id'] ?? null))
                                        <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                    @endif
                                </select>
                                <div class="setting-field__tree">
                                    <strong>{{ $accountTreeLabel }}:</strong>
                                    {{ __('finance::finance.rental_maid_account_tree_hint', ['code' => $definition['code']]) }}
                                </div>
                            </div>
                            <div class="setting-field">
                                <div class="setting-field__top">
                                    <label for="{{ $definition['prepaid_setting_key'] }}">{{ __('finance::finance.rental_maid_prepaid_account_for', ['name' => $definition['display_label']]) }}</label>
                                    <span class="setting-field__tools">
                                        {!! addUpButton(route('finance.accounts.create'), $definition['prepaid_setting_key'], false, 'name', true) !!}
                                        <span class="setting-info" title="{{ __('finance::finance.rental_maid_prepaid_account_tree_hint', ['code' => $definition['prepaid_code']]) }}"><i class="fa fa-info"></i></span>
                                    </span>
                                </div>
                                <input type="hidden" value="{{ $definition['prepaid_setting_key'] }}" name="data[{{ $definition['prepaid_setting_key'] }}][title]">
                                <select name="data[{{ $definition['prepaid_setting_key'] }}][value]" id="{{ $definition['prepaid_setting_key'] }}"
                                        class="form-control account-ajax-select" data-account-scope="debtor_leaf">
                                    <option value="">{{ __('finance::finance.choose') }}</option>
                                    @if ($item = $accountOption($definition['selected_prepaid_account_id'] ?? null))
                                        <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                    @endif
                                </select>
                                <div class="setting-field__tree">
                                    <strong>{{ $accountTreeLabel }}:</strong>
                                    {{ __('finance::finance.rental_maid_prepaid_account_tree_hint', ['code' => $definition['prepaid_code']]) }}
                                </div>
                            </div>
                            <div class="setting-field">
                                <div class="setting-field__top">
                                    <label for="{{ $definition['amount_setting_key'] }}">{{ __('finance::finance.rental_maid_default_amount_for', ['name' => $definition['display_label']]) }}</label>
                                </div>
                                <input type="hidden" value="{{ $definition['amount_setting_key'] }}" name="data[{{ $definition['amount_setting_key'] }}][title]">
                                <input type="number" step="0.01" min="0"
                                       name="data[{{ $definition['amount_setting_key'] }}][value]"
                                       id="{{ $definition['amount_setting_key'] }}"
                                       class="form-control"
                                       value="{{ $definition['selected_default_amount'] ?? 0 }}">
                                <small>{{ __('finance::finance.rental_maid_default_amount_hint') }}</small>
                            </div>
                        @empty
                            <div class="alert alert-warning mb-0">{{ __('finance::finance.rental_maid_expense_accounts_empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </details>

            <details class="settings-section" id="shelter-living-settings-section" open>
                <summary>
                    <div>
                        <h3 class="settings-section-title">{{ __('finance::finance.shelter_living_expense_account') }}</h3>
                        <p class="settings-section-hint">{{ __('finance::finance.shelter_living_expense_account_hint') }}</p>
                    </div>
                    <span class="settings-section-icon"><i class="fa fa-chevron-down"></i></span>
                </summary>
                <div class="settings-section-body">
                    <div class="settings-grid">
                        <div class="setting-field">
                            <div class="setting-field__top">
                                <label for="shelter_living_expense_account">{{ __('finance::finance.shelter_living_expense_account') }}</label>
                                <span class="setting-field__tools">
                                    {!! addUpButton(route('finance.accounts.create'), 'shelter_living_expense_account', false, 'name', true) !!}
                                    <span class="setting-info" title="{{ __('finance::finance.shelter_living_expense_account_tree_hint') }}"><i class="fa fa-info"></i></span>
                                </span>
                            </div>
                            <input type="hidden" value="shelter_living_expense_account" name="data[shelter_living_expense_account][title]">
                            <select name="data[shelter_living_expense_account][value]" id="shelter_living_expense_account"
                                    class="form-control account-ajax-select" data-account-scope="debtor_leaf">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @if ($item = $accountOption($shelter_living_expense_account_id ?? null))
                                    <option value="{{ $item->id }}" selected>{{ $item->code }} / {{ $item->name }}</option>
                                @endif
                            </select>
                            <div class="setting-field__tree">
                                <strong>{{ $accountTreeLabel }}:</strong> {{ __('finance::finance.shelter_living_expense_account_tree_hint') }}
                            </div>
                        </div>
                    </div>
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

        $('.account-ajax-select, .nationality-account-select').each(function () {
            const select = $(this);

            select.select2({
                width: '100%',
                allowClear: true,
                placeholder: '{{ __('finance::finance.search_chart_accounts') }}',
                ajax: {
                    url: @json($nationality_account_options_route),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            scope: select.data('account-scope') || ''
                        };
                    },
                    processResults: function (data) {
                        return {results: data.results || []};
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        });
    </script>
@endsection
