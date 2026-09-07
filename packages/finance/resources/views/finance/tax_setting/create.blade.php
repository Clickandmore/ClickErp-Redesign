<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}"
    enctype="multipart/form-data">

        <div class="col-12">
            <label for="name" class="form-label">{{ __('finance::finance.tax_name') }}</label>
            <input type="text" name="name" id="name" class="form-control"data-validation="required">
        </div>


    <!-- First Modal -->
    <!-- Second Modal -->
    <div class="col-12">


        <label for="account_id" class="form-label"> {{ __('finance::finance.Directory account') }}  {!! addUpButton(route('finance.accounts.create'),'account_id' ,false, 'name') !!}</label>
        <select class="form-control required show-tick ms select2" name="account_id" id="account_id" data-validation="required">
            {!! showSelectElement($accounts, 'name', request()->account_id) !!}
        </select>
    </div>

    <div class="col-12">
        <label for="value" class="form-label">{{ __('finance::finance.percentage') }}</label>
        <fieldset class="form-icon-group right-icon position-relative">
            <div class="form-icon position-absolute">
                <i class="fa fa-percentage"></i>
            </div>
            <input type="number" name="value" class="form-control" min="0" max="100" id="value" data-validation="required,number">
        </fieldset>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
