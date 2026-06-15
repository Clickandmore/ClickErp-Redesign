<form class="row g-2 from-submit-global" method="post" action="{{ $storeRoute }}">
    <div class="col-md-4 col-12">
        <label>{{ __('finance.Journal Entry Number') }}</label>
        <input type="text" class="form-control" name="code" value="{{ $lastCode }}"
            placeholder="{{ __('finance.Journal Entry Number') }}" disabled>
        <input type="hidden" name="code" value="{{ $lastCode }}">
    </div>
    <div class="col-md-4 col-12">
        <label>{{ __('finance.date') }}</label>
        <input type="date" name="date" data-validation="required" class="form-control" value="{{ date('Y-m-d') }}"
            placeholder="{{ __('finance.date') }}">
    </div>
    <div class="col-md-4 col-12">
        <label> {{ __('finance.statement') }}</label>
        <textarea type="text" name="statement" class="form-control form-control-lg mb-1"
            placeholder="{{ __('finance.statement') }}"></textarea>
    </div>
    <div class="debtor__value"></div>
    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
            <td>
                <div class="w-100 d-flex align-items-center justify-content-between add_debtor">
                    <span> {{ __('finance.debtor') }}</span>
                    <i class="fa fa-plus-circle"></i>
                </div>
            </td>
            <td>
                <div class="w-100 d-flex align-items-center justify-content-between add_creditor">
                    <span> {{ __('finance.creditor') }}</span>
                    <i class="fa fa-plus-circle"></i>
                </div>
            </td>
            <td>{{ __('finance.Account Name') }}</td>
            <td>{{ __('finance.fn-cost-centers') }}</td>
            <td>{{ __('finance.Account Statement') }}</td>
            <td>{{ __('finance.Delete') }}</td>
        </thead>
        <tbody class="table-body">
            <tr class="item-row debtor_append" id="debtor_append">
                <td>
                    <input type="hidden" class="form-control" value="debtor" name="account_type[]">
                    <input class="debtor form-control" data-validation="required" type="number" value="0"
                        name="debtor_value[]">
                    <span class="error-span" hidden style='color:red'>{{ __('finance.Enter Valid Values') }}</span>
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
                    <select class="form-control show-tick ms select2" name="cost_center_id[]"
                        @if (isset($cost_center_required) && $cost_center_required->value == 'on') data-validation="required" @endif>
                        {!! showSelectElement($cost_centers, 'title') !!}
                    </select>
                    <input type="hidden" name="cost_center_required"
                        value="{{ isset($cost_center_required) && $cost_center_required->value == 'on' ? 'on' : 'off' }}">

                </td>
                <td>
                    <textarea name="statement_account[]" class="form-control" placeholder="{{ __('finance.statement') }}"></textarea>
                </td>

                <td><i class="fa fa-trash"></i></td>
            </tr>
            <tr class="item-row" id="creditor_append">
                <td>
                    <input type="hidden" value="0" name="debtor_value[]">
                </td>
                <td>
                    <input type="hidden" class="form-control" value="creditor" name="account_type[]">
                    <input type="number" data-validation="required" class="creditor form-control" value="0"
                        name="creditor_value[]">
                    <span class="error-span" hidden style='color:red'> {{ __('finance.Enter Valid Values') }}</span>
                </td>
                <td>
                    <select name="account_id[]" data-validation="required"
                        class="form-control select-account select-unique-option select2">
                        {!! showSelectElement($accounts, 'name') !!}
                    </select>
                </td>
                <td>
                    <select class="form-control show-tick ms select2" name="cost_center_id[]"
                        @if (isset($cost_center_required) && $cost_center_required->value == 'on') data-validation="required" @endif>
                        {!! showSelectElement($cost_centers, 'title') !!}
                    </select>
                    <input type="hidden" name="cost_center_required"
                        value="{{ isset($cost_center_required) && $cost_center_required->value == 'on' ? 'on' : 'off' }}">

                </td>
                <td>
                    <textarea name="statement_account[]" class="form-control" placeholder="{{ __('finance.statement') }}"></textarea>
                </td>

                <td><i class="fa fa-trash remove_creditor"></i></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td><input id="debtor_footer_value" class="form-control" value="0" disabled></td>
                <td><input id="creditor_footer_value" class="form-control" value="0" disabled></td>
                <td>{{ __('finance.Total') }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <hr>
    <h4>{{ __('finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" multiple name="file[]" id="img-upload"
                    onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{ asset('admin') }}/assets/img/plus.svg" alt="plus">
                </div>
            </label>
        </div>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
