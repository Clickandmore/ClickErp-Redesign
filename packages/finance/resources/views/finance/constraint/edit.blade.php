@php
    $constraintFormClass = $constraintFormClass ?? 'from-submit-update-global';
    $inlineConstraintEdit = $inlineConstraintEdit ?? false;
@endphp

@once
    @push('style')
        <style>
            .constraint-edit-form .constraint-edit-table-wrap {
                overflow-x: auto;
                padding-bottom: .25rem;
            }

            .constraint-edit-form .constraint-edit-table {
                min-width: 1120px;
                table-layout: fixed;
            }

            .constraint-edit-form .constraint-edit-table th,
            .constraint-edit-form .constraint-edit-table td {
                vertical-align: top;
            }

            .constraint-edit-form .constraint-edit-table th {
                white-space: nowrap;
            }

            .constraint-edit-form .constraint-amount-col {
                width: 120px;
            }

            .constraint-edit-form .constraint-account-col {
                width: 260px;
            }

            .constraint-edit-form .constraint-cost-center-col {
                width: 240px;
            }

            .constraint-edit-form .constraint-statement-col {
                width: 420px;
            }

            .constraint-edit-form .constraint-action-col {
                width: 70px;
            }

            .constraint-edit-form .select2-container {
                width: 100% !important;
            }

            .constraint-edit-form .select2-selection--single {
                min-height: 42px;
            }

            .constraint-edit-form .select2-selection__rendered {
                white-space: normal !important;
                line-height: 1.5 !important;
                padding-top: .35rem;
                padding-bottom: .35rem;
            }

            .constraint-edit-form .statement-account-field {
                min-height: 88px;
                resize: vertical;
                line-height: 1.7;
            }

            .constraint-edit-form .fa-trash,
            .constraint-edit-form .delete-row {
                cursor: pointer;
                color: #dc3545;
            }
        </style>
    @endpush
@endonce

<form class="row g-3 constraint-edit-form {{ $constraintFormClass }}" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @csrf
    @method("PUT")

    <div class="col-md-4 col-12">
        <label>{{ __("finance::finance.Journal Entry Number") }}</label>
        <input type="text" class="form-control" name="code" value="{{ $obj->code }}" placeholder="{{ __("finance.Journal Entry Number") }}" disabled>
    </div>
    <div class="col-md-4 col-12">
        <label>{{ __('finance::finance.date') }}</label>
        <input type="date" name="date" class="form-control" value="{{ $obj->date }}" placeholder="{{ __('finance.date') }}">
    </div>
    <div class="col-md-4 col-12">
        <label>{{ __('finance::finance.statement') }}</label>
        <textarea type="text" name="statement" class="form-control form-control-lg mb-1"
                  placeholder="{{ __('finance::finance.statement') }}">{{ $obj->statement }}</textarea>
    </div>
    <div class="debtor__value">
        <input type="hidden" name="value" value="{{ $obj->value }}" class="constraint-total-value">
    </div>
    <div class="table-responsive constraint-edit-table-wrap">
    <table id="myDataTable_no_filter" class="table align-middle card-table constraint-edit-table">
        <colgroup>
            <col class="constraint-amount-col">
            <col class="constraint-amount-col">
            <col class="constraint-account-col">
            <col class="constraint-cost-center-col">
            <col class="constraint-statement-col">
            <col class="constraint-action-col">
        </colgroup>
        <thead>
        <tr>
        <th>
            <div class="w-100 d-flex align-items-center justify-content-between add_debtor">
                <span>{{ __('finance::finance.debtor') }}</span>
                <i class="fa fa-plus-circle"></i>
            </div>
        </th>
        <th>
            <div class="w-100 d-flex align-items-center justify-content-between add_creditor">
                <span>{{ __('finance::finance.creditor') }}</span>
                <i class="fa fa-plus-circle"></i>
            </div>
        </th>
        <th>{{ __('finance::finance.Account Name') }}</th>
        <th>{{ __("finance::finance.fn-cost-centers") }}</th>
        <th>{{ __('finance::finance.Account Statement') }}</th>
        <th>{{ __('finance::finance.Delete') }}</th>
        </tr>
        </thead>
        <tbody class="table-body">
            @foreach ($obj->constraintDetails as $constraintDetails)
                @if ($constraintDetails->type == 'debtor')
                    <tr class="item-row debtor_append" id="debtor_append">
                        <td>
                            <input type="hidden" name="old_id[]" value="{{ $constraintDetails->id }}" class="constraint-old-id">
                            <input type="hidden" class="form-control" value="debtor"
                            name="account_type[]">
                            <input class="debtor form-control" min="0.01" step="any" type="number" value="{{ $constraintDetails->debtor_value }}" name="debtor_value[]">
                            <span class="error-span" hidden style='color:red'>{{ __("finance::finance.Enter Valid Values") }}</span>
                        </td>
                        <td>
                            <input type="hidden" value="0" name="creditor_value[]">
                        </td>
                        <td>
                            <select name="account_id[]" class="form-control select-account select-unique-option select2">
                                <option value="">{{ __("finance::finance.Select the Account") }}</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" {{$account->id == $constraintDetails->account_id ? 'selected' : ''}}>{{ $account->name }}</option>
                                @endforeach
                                {{-- {!! showSelectElement($accounts ,'name') !!} --}}
                            </select>
                        </td>
                        <td>
                            <select class="form-control show-tick ms select2" name="cost_center_id[]"
                            @if (isset($cost_center_required) && $cost_center_required->value == "on")
                                data-validation="required"
                            @endif>
                                {!! showSelectElement($cost_centers,"title",$constraintDetails->cost_center_id) !!}
                            </select>
                            <input type="hidden" name="cost_center_required" value="{{ (isset($cost_center_required) && $cost_center_required->value == "on") ? "on" : "off" }}">

                        </td>
                        <td><textarea name="statement_account[]" class="form-control statement-account-field" rows="3" placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement}}</textarea>
                        </td>
                        <td><i class="fa fa-trash delete-row"></i></td>
                    </tr>
                @endif
            @endforeach
            @foreach ($obj->constraintDetails as $constraintDetails)
                @if ($constraintDetails->type == 'creditor')
                    <tr class="item-row" id="creditor_append">
                        <td>
                            <input type="hidden" name="old_id[]" value="{{ $constraintDetails->id }}" class="constraint-old-id">
                            <input type="hidden" value="0" name="debtor_value[]">
                        </td>
                        <td>
                            <input type="hidden" class="form-control" value="creditor"
                            name="account_type[]">
                            <input type="number" min="0.01" step="any" class="creditor form-control" value="{{ $constraintDetails->creditor_value }}"
                                name="creditor_value[]">
                            <span class="error-span" hidden style='color:red'> {{ __("finance::finance.Enter Valid Values") }}</span>
                        </td>
                        <td>
                            <select name="account_id[]" class="form-control select-account select-unique-option select2">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" {{$account->id == $constraintDetails->account_id ? 'selected' : ''}}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control show-tick ms select2" name="cost_center_id[]"
                            @if (isset($cost_center_required) && $cost_center_required->value == "on")
                                data-validation="required"
                            @endif>
                                {!! showSelectElement($cost_centers,"title",$constraintDetails->cost_center_id) !!}
                            </select>
                            <input type="hidden" name="cost_center_required" value="{{ (isset($cost_center_required) && $cost_center_required->value == "on") ? "on" : "off" }}">

                        </td>
                        <td><textarea name="statement_account[]" class="form-control statement-account-field" rows="3" placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement }}</textarea>
                        </td>
                        <td><i class="fa fa-trash delete-row"></i></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td><input id="debtor_footer_value" class="form-control" value="{{$obj->value}}" disabled></td>
            <td><input id="creditor_footer_value" class="form-control" value="{{$obj->value}}" disabled></td>
            <td>{{ __("finance::finance.Total") }}</td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>
    </table>
    </div>

    <hr>
    <h4>{{ __('finance::finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{asset("admin")}}/assets/img/plus.svg" alt="plus">
                </div>
            </label>
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $file)
                    <div class="uploadedImage">
                        <img src="{{ showFile($file->file) }}" alt="">
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                                delete-route="{{ route('finance.constraint-files.destroy', $file->id) }}">
                            <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <div class="col-12 modal-footer">
         {!! submitButton(   $updateRoute  ) !!}
        @if ($inlineConstraintEdit)
            <a class="btn btn-outline-secondary" href="{{ $closeRoute ?? url()->current() }}">
                {{ __('buttons.close') }}
            </a>
        @else
            {!! closeButton() !!}
        @endif
    </div>
</form>
