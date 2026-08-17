<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
    <div class="col-md-4">
        <label for="principle_id" class="form-label">{{ __("finance::finance.principles") }}</label>
        {!! addUpButton(route('finance.principles.create'),'principle_id','' ,'name',false) !!}
        <select name="principle_id" id="principle_id" class="form-control select2">
            <option value="">{{ __("finance::finance.choose") }}</option>
            @foreach($principles as $principle)
                @if(!in_array($principle->id , $depreciationPrincipleIds))
                    <option value="{{ $principle->id }}"
                            data-perished_account_id="{{ @$principle->principlePerished->perished_account_id }}"
                            data-perished_account_name="{{ @$principle->principlePerished->perished_account->name  }}"
                            data-expense_account_id="{{ @$principle->principlePerished->expense_account_id }}"
                            data-expense_account_name="{{ @$principle->principlePerished->expense_account->name }}"
                            data-account_id="{{ @$principle->account_id }}"
                    >
                        {{ $principle->name }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    <!-- Perished Account ID -->
    <div class="col-md-4">
        <label for="perished_account_id" class="form-label">{{ __("finance::finance.perished_account") }}</label>
        <input type="hidden" name="account_id" id="account_id">
        <input type="hidden" name="perished_account_id" id="perished_account_id">
        <input type="text" readonly name="perished_account_name" id="perished_account_name" class="form-control">
    </div>

    <!-- Expense Account ID -->
    <div class="col-md-4">
        <label for="expense_account_id" class="form-label">{{ __("finance::finance.expense_account") }}</label>
        <input type="hidden" name="expense_account_id" id="expense_account_id">
        <input type="text" readonly name="expense_account_name" id="expense_account_name" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="date" class="form-label">{{ __("finance::finance.perished_date") }}</label>
        <input type="date" id="date" name="date" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="value" class="form-label" id="perished_value_label">{{ __("finance::finance.perished_value") }}</label>
        <input type="number" name="value" id="value" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label> {{ __('finance::finance.statement') }}</label>
        <textarea type="text" name="statement" class="form-control form-control-lg mb-1" data-validation="required"
                  placeholder="{{ __('finance::finance.statement') }}"></textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
