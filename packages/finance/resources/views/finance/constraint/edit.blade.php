@php
    $constraintFormClass = $constraintFormClass ?? 'from-submit-update-global';
    $inlineConstraintEdit = $inlineConstraintEdit ?? false;
@endphp

<form class="row g-3 {{ $constraintFormClass }}" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
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
    <div class="debtor__value"></div>
    <table id="myDataTable_no_filter" class="table align-middle card-table">
        <thead>
        <td>
            <div class="w-100 d-flex align-items-center justify-content-between add_debtor">
                <span>{{ __('finance::finance.debtor') }}</span>
                <i class="fa fa-plus-circle"></i>
            </div>
        </td>
        <td>
            <div class="w-100 d-flex align-items-center justify-content-between add_creditor">
                <span>{{ __('finance::finance.creditor') }}</span>
                <i class="fa fa-plus-circle"></i>
            </div>
        </td>
        <td>{{ __('finance::finance.Account Name') }}</td>
        <td>{{ __("finance::finance.fn-cost-centers") }}</td>
        <td>{{ __('finance::finance.Account Statement') }}</td>
        <td>{{ __('finance::finance.Delete') }}</td>
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
                            <select name="account_id[]" class="form-control select-account select-unique-option ">
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
                        <td><textarea name="statement_account[]" class="form-control" placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement}}</textarea>
                        </td>
                        <td><i class="fa fa-trash"></i></td>
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
                            <select name="account_id[]" class="form-control select-account select-unique-option ">
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
                        <td><textarea name="statement_account[]" class="form-control" placeholder="{{ __('finance::finance.statement') }}">{{ $constraintDetails->statement }}</textarea>
                        </td>
                        <td><i class="fa fa-trash"></i></td>
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
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#constraintInlineEdit">
                {{ __('buttons.close') }}
            </button>
        @else
            {!! closeButton() !!}
        @endif
    </div>
</form>
