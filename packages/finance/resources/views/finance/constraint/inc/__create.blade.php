<input type="hidden" name="cost_center_required" value="off">
<input type="hidden" name="value" id="constraint_total_value">

<div class="col-12">
    <div class="page-card section-card">
        <div class="section-title">
            <div>
                <h5>{{ __('finance::finance.constraint_basic_information') }}</h5>
                <p>{{ __('finance::finance.constraint_basic_information_hint') }}</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-3 col-md-4 col-12">
                <label class="form-label">{{ __('finance::finance.Journal Entry Number') }} <span
                        class="text-danger">*</span></label>
                <div class="summary-box">
                    <span class="summary-label">{{ __('finance::finance.Journal Entry Number') }}</span>
                    <div class="summary-value">{{ $lastCode }}</div>
                </div>
                <input type="hidden" name="code" value="{{ $lastCode }}">
            </div>

            <div class="col-lg-3 col-md-4 col-12">
                <label class="form-label">{{ __('finance::finance.date') }} <span class="text-danger">*</span></label>
                <input type="date" name="date" data-validation="required" class="form-control"
                    value="{{ date('Y-m-d') }}" placeholder="{{ __('finance::finance.date') }}">
            </div>

            <div class="col-lg-6 col-12">
                <label class="form-label">{{ __('finance::finance.statement') }}</label>
                <textarea name="statement" class="form-control" rows="3"
                    placeholder="{{ __('finance::finance.constraint_statement_placeholder') }}"></textarea>
            </div>
        </div>
    </div>
</div>

<div class="col-12">
    <div class="page-card section-card">
        <div class="constraint-toolbar">
            <div class="section-title mb-0">
                <div>
                    <h5>{{ __('finance::finance.constraint_lines') }}</h5>
                    <p>{{ __('finance::finance.constraint_lines_hint') }}</p>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-success add_debtor">
                    <i class="fa fa-plus-circle"></i>
                    {{ __('finance::finance.add_debtor_line') }}
                </button>

                <button type="button" class="btn btn-outline-danger add_creditor">
                    <i class="fa fa-plus-circle"></i>
                    {{ __('finance::finance.add_creditor_line') }}
                </button>
            </div>
        </div>

        <div class="totals-group">
            <div class="total-chip debtor">
                <span>{{ __('finance::finance.debtor_total') }}</span>
                <strong id="debtor_total_badge">0.00</strong>
            </div>

            <div class="total-chip creditor">
                <span>{{ __('finance::finance.creditor_total') }}</span>
                <strong id="creditor_total_badge">0.00</strong>
            </div>
        </div>

        <div id="balance_alert" class="alert alert-warning balance-alert">
            {{ __('finance::finance.debit_credit_totals_must_match') }}
        </div>

        <div class="table-responsive mt-3">
            <table class="table constraint-table align-middle">
                <thead>
                    <tr>
                        <th>{{ __('finance::finance.debtor') }}</th>
                        <th>{{ __('finance::finance.creditor') }}</th>
                        <th>{{ __('finance::finance.Account Name') }} <span class="text-danger">*</span></th>
                        <th>{{ __('finance::finance.fn-cost-centers') }}</th>
                        <th>{{ __('finance::finance.Account Statement') }}</th>
                        <th class="text-center">{{ __('finance::finance.Delete') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">
                    <tr>
                        <td>
                            <input type="hidden" value="debtor" name="account_type[]">
                            <input type="text" inputmode="decimal" autocomplete="off"
                                class="form-control amount-input debtor js-amount-input" data-validation="required"
                                name="debtor_value[]" placeholder="0.00">
                        </td>
                        <td>
                            <input type="hidden" value="0" name="creditor_value[]">
                        </td>
                        <td>
                            <select name="account_id[]" data-validation="required"
                                class="form-control select-account select-unique-option select2">
                                {!! showSelectElement($accounts, 'name') !!}
                            </select>
                        </td>
                        <td>
                            <select class="form-control show-tick ms select2" name="cost_center_id[]">
                                {!! showSelectElement($cost_centers, 'title') !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="statement_account[]" class="form-control" rows="2"
                                placeholder="{{ __('finance::finance.statement') }}"></textarea>
                        </td>
                        <td class="text-center">
                            <button type="button" class="delete-row-btn" title="{{ __('finance::finance.Delete') }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <input type="hidden" value="0" name="debtor_value[]">
                        </td>
                        <td>
                            <input type="hidden" value="creditor" name="account_type[]">
                            <input type="text" inputmode="decimal" autocomplete="off"
                                class="form-control amount-input creditor js-amount-input" data-validation="required"
                                name="creditor_value[]" placeholder="0.00">
                        </td>
                        <td>
                            <select name="account_id[]" data-validation="required"
                                class="form-control select-account select-unique-option select2">
                                {!! showSelectElement($accounts, 'name') !!}
                            </select>
                        </td>
                        <td>
                            <select class="form-control show-tick ms select2" name="cost_center_id[]">
                                {!! showSelectElement($cost_centers, 'title') !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="statement_account[]" class="form-control" rows="2"
                                placeholder="{{ __('finance::finance.statement') }}"></textarea>
                        </td>
                        <td class="text-center">
                            <button type="button" class="delete-row-btn"
                                title="{{ __('finance::finance.Delete') }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>

                <tfoot>
                    <tr>
                        <td><input id="debtor_footer_value" class="form-control" value="0.00" disabled></td>
                        <td><input id="creditor_footer_value" class="form-control" value="0.00" disabled></td>
                        <td>{{ __('finance::finance.Total') }}</td>
                        <td colspan="3">{{ __('finance::finance.cost_center_optional_hint') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<template id="constraint-row-template-debtor">
    <tr>
        <td>
            <input type="hidden" value="debtor" name="account_type[]">
            <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control amount-input debtor js-amount-input" data-validation="required"
                name="debtor_value[]" placeholder="0.00">
        </td>
        <td>
            <input type="hidden" value="0" name="creditor_value[]">
        </td>
        <td>
            <select name="account_id[]" data-validation="required"
                class="form-control select-account select-unique-option select2">
                {!! showSelectElement($accounts, 'name') !!}
            </select>
        </td>
        <td>
            <select class="form-control show-tick ms select2" name="cost_center_id[]">
                {!! showSelectElement($cost_centers, 'title') !!}
            </select>
        </td>
        <td>
            <textarea name="statement_account[]" class="form-control" rows="2"
                placeholder="{{ __('finance::finance.statement') }}"></textarea>
        </td>
        <td class="text-center">
            <button type="button" class="delete-row-btn" title="{{ __('finance::finance.Delete') }}">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<template id="constraint-row-template-creditor">
    <tr>
        <td>
            <input type="hidden" value="0" name="debtor_value[]">
        </td>
        <td>
            <input type="hidden" value="creditor" name="account_type[]">
            <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control amount-input creditor js-amount-input" data-validation="required"
                name="creditor_value[]" placeholder="0.00">
        </td>
        <td>
            <select name="account_id[]" data-validation="required"
                class="form-control select-account select-unique-option select2">
                {!! showSelectElement($accounts, 'name') !!}
            </select>
        </td>
        <td>
            <select class="form-control show-tick ms select2" name="cost_center_id[]">
                {!! showSelectElement($cost_centers, 'title') !!}
            </select>
        </td>
        <td>
            <textarea name="statement_account[]" class="form-control" rows="2"
                placeholder="{{ __('finance::finance.statement') }}"></textarea>
        </td>
        <td class="text-center">
            <button type="button" class="delete-row-btn" title="{{ __('finance::finance.Delete') }}">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<div class="col-12">
    <div class="page-card section-card">
        <div class="section-title">
            <div>
                <h5>{{ __('finance::finance.attachments') }}</h5>
                <p>{{ __('finance::finance.constraint_attachments_hint') }}</p>
            </div>
        </div>

        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]" id="img-upload"
                    onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin') }}/assets/img/plus.svg" alt="plus"
                        style="width: 30px; height: 30px;">
                </div>
            </label>
        </div>
    </div>
</div>
