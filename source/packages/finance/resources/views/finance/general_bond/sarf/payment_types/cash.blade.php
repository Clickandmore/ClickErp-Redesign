<div class="col-6">
    <label for="TextInput" class="form-label"> الخزينة </label>
    <select class="form-control show-tick ms select2" name="credit_account_id" data-validation="required">
        {!! showSelectElement($accounts,"name",@$obj->credit_account_id) !!}
    </select>
</div>
