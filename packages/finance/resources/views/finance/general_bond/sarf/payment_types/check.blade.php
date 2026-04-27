<div class="col-4">
    <label for="TextInput" class="form-label"> البنك </label>
    <select class="form-control show-tick ms select2" name="credit_account_id" data-validation="required">
        {!! showSelectElement($accounts,"name",@$obj->credit_account_id) !!}
    </select>
</div>
<div class="col-4">
    <label for="TextInput" class="form-label">رقم الشيك </label>
    <input type="text" name="check_num" value="{{@$obj->check->check_num}}" class="form-control">
</div>

<div class="col-4">
    <label class="form-label">  تاريخ الشيك </label>
    <input class="form-control" name="check_date" value="{{@$obj->check->check_date}}" type="date" id="formFile">
</div>
