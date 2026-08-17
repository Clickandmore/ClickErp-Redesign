<form class="row g-3 from-submit-update-global" method="post" action="{{$route}}" enctype="multipart/form-data">
    @method("PUT")

    <div class="col-md-3">
        <label for="code" class="form-label">{{ __("finance.Voucher Number") }}</label>
        <input type="text" value="{{$lastId}}" id="code" name="code" class="form-control" data-validation="required">
    </div>
    <div class="col-md-3">
        <label for="date" class="form-label">{{ __("finance.Voucher Date") }} </label>
        <input type="date" id="date" name="date" class="form-control" value="{{date("Y-m-d")}}" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="bond_book_num" class="form-label">{{ __("finance.Bookkeeping Number") }}</label>
        <input type="text" id="bond_book_num" name="bond_book_num" class="form-control">
    </div>

    <div class="col-md-3">
        <label for="value" class="form-label"> {{ __("finance.Amount") }} </label>
        <input type="number" name="value" readonly id="value" class="form-control" value="{{$bond_obj->value}}">
        <input type="hidden" name="net" value="{{$bond_obj->net}}">
    </div>

    <div class="col-6">
        <label for="debit_account_id" class="form-label">{{ __("finance.To Account") }} </label>
        <select class="form-control show-tick ms select2" id="debit_account_id" name="debit_account_id" data-validation="required">
            {!! showSelectElement($accounts,"name") !!}
        </select>
    </div>

    <div class="col-6">
        <label for="credit_account_id" class="form-label"> {{ __("finance.From Account") }} </label>
        <input type="text" class="form-control" readonly value="{{$bond_obj->debitAccount->name}}">
        <input type="hidden" value="{{$bond_obj->debit_account_id}}" name="credit_account_id">
    </div>

    <div class="col-6">
        <label for="bs_maxlength_textarea" class="form-label">{{ __("finance.statement") }} </label>
        <textarea type="text" name="statement" data-validation="required" class="form-control form-control-lg mb-1" id="bs_maxlength_textarea"
                  rows="5"></textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("finance.Document Image") }}</label>
        <input type="file" name="file" class="dropify" data-default-file="">
    </div>

    <input type="hidden" value="{{$payment_type}}" name="payment_type">

    <div class="col-12 modal-footer">
         {!! submitButton( $route ) !!}
        {!! closeButton() !!}
    </div>
</form>
