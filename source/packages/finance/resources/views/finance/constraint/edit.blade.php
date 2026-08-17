@php
    $constraintFormClass = $constraintFormClass ?? 'from-submit-update-global';
    $inlineConstraintEdit = $inlineConstraintEdit ?? false;
    $debtorTotal = (float) $obj->constraintDetails->sum('debtor_value');
    $creditorTotal = (float) $obj->constraintDetails->sum('creditor_value');
    $debtorDetails = $obj->constraintDetails->filter(function ($detail) {
        return (float) $detail->debtor_value > 0
            || ((float) $detail->creditor_value <= 0 && $detail->type === 'debtor');
    });
    $creditorDetails = $obj->constraintDetails->filter(function ($detail) {
        return (float) $detail->creditor_value > 0
            || ((float) $detail->debtor_value <= 0 && $detail->type === 'creditor');
    });
@endphp

<form class="row g-3 constraint-edit-form {{ $constraintFormClass }}" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <input type="hidden" name="cost_center_required"
        value="{{ isset($cost_center_required) && $cost_center_required->value == 'on' ? 'on' : 'off' }}">
    <input type="hidden" name="value" value="{{ number_format((float) $obj->value, 2, '.', '') }}"
        class="constraint-total-value">

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
                    <label class="form-label">{{ __('finance::finance.Journal Entry Number') }}</label>
                    <div class="summary-box">
                        <span class="summary-label">{{ __('finance::finance.Journal Entry Number') }}</span>
                        <div class="summary-value">{{ $obj->code }}</div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-4 col-12">
                    <label class="form-label">{{ __('finance::finance.date') }} <span
                            class="text-danger">*</span></label>
                    <input type="date" name="date" data-validation="required" class="form-control"
                        value="{{ $obj->date }}" placeholder="{{ __('finance::finance.date') }}">
                </div>

                <div class="col-lg-6 col-12">
                    <label class="form-label">{{ __('finance::finance.statement') }}</label>
                    <textarea name="statement" class="form-control" rows="3"
                        placeholder="{{ __('finance::finance.constraint_statement_placeholder') }}">{{ $obj->statement }}</textarea>
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
                <div class="total-chip debtor-total">
                    <span>{{ __('finance::finance.debtor_total') }}</span>
                    <strong id="debtor_total_badge">{{ number_format($debtorTotal, 2, '.', ',') }}</strong>
                </div>

                <div class="total-chip creditor-total">
                    <span>{{ __('finance::finance.creditor_total') }}</span>
                    <strong id="creditor_total_badge">{{ number_format($creditorTotal, 2, '.', ',') }}</strong>
                </div>
            </div>

            <div id="balance_alert" class="alert alert-warning balance-alert">
                {{ __('finance::finance.debit_credit_totals_must_match') }}
            </div>

            <div class="table-responsive mt-3 constraint-edit-table-wrap">
                <table id="myDataTable_no_filter" class="table constraint-table align-middle constraint-edit-table">
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
                        @foreach ($debtorDetails as $constraintDetails)
                                <tr class="item-row debtor_append" id="debtor_append">
                                    <td>
                                        <input type="hidden" name="old_id[]" value="{{ $constraintDetails->id }}"
                                            class="constraint-old-id">
                                        <input type="hidden" value="debtor" name="account_type[]">
                                        <input class="debtor form-control amount-input js-constraint-amount"
                                            inputmode="decimal" autocomplete="off" type="text"
                                            value="{{ number_format((float) $constraintDetails->debtor_value, 2, '.', '') }}"
                                            name="debtor_value[]" data-validation="required">
                                        <span class="error-span" hidden
                                            style="color:red">{{ __('finance::finance.Enter Valid Values') }}</span>
                                    </td>
                                    <td>
                                        <input type="hidden" value="0" name="creditor_value[]">
                                    </td>
                                    <td>
                                        <select name="account_id[]" data-validation="required"
                                            class="form-control select-account select-unique-option select2">
                                            <option value="">{{ __('finance::finance.Select the Account') }}</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $account->id == $constraintDetails->account_id ? 'selected' : '' }}>
                                                    {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control show-tick ms select2" name="cost_center_id[]"
                                            @if (isset($cost_center_required) && $cost_center_required->value == 'on') data-validation="required" @endif>
                                            {!! showSelectElement($cost_centers, 'title', $constraintDetails->cost_center_id) !!}
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="statement_account[]" class="form-control statement-account-field" rows="2"
                                            placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement }}</textarea>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="delete-row-btn delete-row"
                                            title="{{ __('finance::finance.Delete') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                        @endforeach

                        @foreach ($creditorDetails as $constraintDetails)
                                <tr class="item-row" id="creditor_append">
                                    <td>
                                        <input type="hidden" name="old_id[]" value="{{ $constraintDetails->id }}"
                                            class="constraint-old-id">
                                        <input type="hidden" value="0" name="debtor_value[]">
                                    </td>
                                    <td>
                                        <input type="hidden" value="creditor" name="account_type[]">
                                        <input type="text" inputmode="decimal" autocomplete="off"
                                            class="creditor form-control amount-input js-constraint-amount"
                                            value="{{ number_format((float) $constraintDetails->creditor_value, 2, '.', '') }}"
                                            name="creditor_value[]" data-validation="required">
                                        <span class="error-span" hidden
                                            style="color:red">{{ __('finance::finance.Enter Valid Values') }}</span>
                                    </td>
                                    <td>
                                        <select name="account_id[]" data-validation="required"
                                            class="form-control select-account select-unique-option select2">
                                            <option value="">{{ __('finance::finance.Select the Account') }}</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $account->id == $constraintDetails->account_id ? 'selected' : '' }}>
                                                    {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control show-tick ms select2" name="cost_center_id[]"
                                            @if (isset($cost_center_required) && $cost_center_required->value == 'on') data-validation="required" @endif>
                                            {!! showSelectElement($cost_centers, 'title', $constraintDetails->cost_center_id) !!}
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="statement_account[]" class="form-control statement-account-field" rows="2"
                                            placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement }}</textarea>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="delete-row-btn delete-row"
                                            title="{{ __('finance::finance.Delete') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td><input id="debtor_footer_value" class="form-control"
                                    value="{{ number_format($debtorTotal, 2, '.', ',') }}" disabled></td>
                            <td><input id="creditor_footer_value" class="form-control"
                                    value="{{ number_format($creditorTotal, 2, '.', ',') }}" disabled></td>
                            <td>{{ __('finance::finance.Total') }}</td>
                            <td colspan="3">{{ __('finance::finance.cost_center_optional_hint') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="page-card section-card">
            <div class="section-title">
                <div>
                    <h5>{{ __('finance::finance.attachments') }}</h5>
                    <p>{{ __('finance::finance.constraint_attachments_hint') }}</p>
                </div>
            </div>

            <div class="work_samples_grid constraint-attachment-grid">
                <label class="img_upload">
                    <input type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" multiple name="file[]" id="img-upload"
                        onchange="handleFileSelect(event)">
                    <div class="img justify-content-center">
                        <img loading="lazy" src="{{ asset('admin') }}/assets/img/plus.svg" alt="plus"
                            style="width: 30px; height: 30px;">
                    </div>
                </label>

                @if ($obj->files->count() > 0)
                    @foreach ($obj->files as $file)
                        @php
                            $extension = strtolower(pathinfo($file->file, PATHINFO_EXTENSION));
                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                        @endphp
                        <div class="uploadedImage">
                            @if ($isImage)
                                <img src="{{ showFile($file->file) }}" alt="">
                            @else
                                <a href="{{ showFile($file->file) }}" target="_blank" class="constraint-file-preview" title="{{ basename($file->file) }}">
                                    <i class="fa fa-file-text-o"></i>
                                    <span>{{ strtoupper($extension ?: 'FILE') }}</span>
                                </a>
                            @endif
                            <button type="button" style="border: none;background: none;color:red"
                                class="delete deleteImagesButton"
                                delete-route="{{ route('finance.constraint-files.destroy', $file->id) }}">
                                <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                            </button>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="page-card submit-bar">
            <p class="submit-note">{{ __('finance::finance.constraint_submit_note') }}</p>
            <div class="submit-actions d-flex gap-2">
                @if ($inlineConstraintEdit)
                    <a class="btn btn-light border" href="{{ $closeRoute ?? url()->current() }}">
                        {{ __('buttons.close') }}
                    </a>
                @else
                    {!! closeButton() !!}
                @endif
                <button class="btn btn-primary mySubmitButton" type="submit">
                    {{ __('buttons.save') }}
                </button>
            </div>
        </div>
    </div>
</form>
