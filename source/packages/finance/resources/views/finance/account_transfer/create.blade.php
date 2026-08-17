<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}"
    enctype="multipart/form-data">

    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.date") }} </label>
        <input type="date" name="date" class="form-control" value="{{date("Y-m-d")}}" data-validation="required">
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.From Account") }} : </label>
        <label for="TextInput" class="form-label"> {{ __('finance::finance.Directory account') }}
            {!! addUpButton(route('finance.accounts.create'),'credit_account_id' ,false, 'name') !!}</label>
        <select class="form-control show-tick ms select2" id="credit_account_id" name="credit_account_id" data-validation="required" onchange="updateOptions('#credit_account_id', '#debit_account_id')">
            {!! showSelectElement($accounts, 'name') !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.To Account") }} : </label>
        <label for="TextInput" class="form-label"> {{ __('finance::finance.Directory account') }}
            {!! addUpButton(route('finance.accounts.create'),'debit_account_id' ,false, 'name') !!}</label>
        <select class="form-control show-tick ms select2" id="debit_account_id" name="debit_account_id" data-validation="required" onchange="updateOptions('#debit_account_id', '#credit_account_id')">
            {!! showSelectElement($accounts, 'name') !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("finance::finance.fn-cost-centers") }} : </label>
        <label for="TextInput" class="form-label">
            {!! addUpButton(route('finance.cost-centers.create'),'cost_center_id' ,false, 'title') !!}</label>
        <select class="form-control show-tick ms select2" name="cost_center_id" id="cost_center_id">
            {!! showSelectElement($costCenters, "title") !!}
        </select>
    </div>
    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("finance::finance.value") }} </label>
        <input type="number" min="0.01" step="any" name="value" value="1" class="form-control" data-validation="required,min_value">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("finance::finance.statement") }} </label>
        <textarea type="text" name="statement" class="form-control form-control-lg mb-1" id="bs_maxlength_textarea"
                  rows="5"></textarea>
    </div>


    <h4>{{ __('finance::finance.attachments') }}</h4>
    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                       id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy"
                         src="{{ asset('admin') }}/assets/img/plus.svg"
                         alt="plus"
                         style="width: 50px; height: 50px;">
                </div>
            </label>
        </div>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
