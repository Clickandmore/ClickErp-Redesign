<div class="col-4">
    <label for="TextInput" class="form-label"> البنك </label>
    <select class="form-control show-tick ms select2" name="credit_account_id" data-validation="required">
        {!! showSelectElement($accounts,"name" ,@$obj->credit_account_id) !!}
    </select>
</div>
<div class="col-4">
    <label for="TextInput" class="form-label">رقم التحويل </label>
    <input type="text" name="transfer_number" value="{{@$obj->transfer_number}}" class="form-control"
           maxlength="500" data-validation="required">
</div>

<div class="col-4">
    <label class="form-label"> صورة التحويل </label>
    @if(isset($obj->transfer_file))
        <input type="file" name="transfer_file" class="dropify" data-default-file="{{showFile($obj->transfer_file)}}">
    @else
        <input class="form-control" name="transfer_file" type="file" id="formFile">
    @endif
</div>
