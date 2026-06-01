<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}">
    <div class="col-md-6">
        <label class="form-label">{{ __("hr::hr.name") }}</label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __("hr::hr.code") }}</label>
        <input type="text" name="code" class="form-control">
    </div>

    <div class="col-md-8">
        <label class="form-label">{{ __("hr::hr.address") }}</label>
        <input type="text" name="address" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.Status") }}</label>
        <select name="is_active" class="form-control">
            <option value="1">{{ __("hr::hr.Enable") }}</option>
            <option value="0">{{ __("hr::hr.Disable") }}</option>
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
