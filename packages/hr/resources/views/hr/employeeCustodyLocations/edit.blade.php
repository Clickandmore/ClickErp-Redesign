<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}">
    @method('put')

    <div class="col-md-6">
        <label class="form-label">{{ __("hr::hr.name") }}</label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __("hr::hr.code") }}</label>
        <input type="text" name="code" value="{{ $obj->code }}" class="form-control">
    </div>

    <div class="col-md-8">
        <label class="form-label">{{ __("hr::hr.address") }}</label>
        <input type="text" name="address" value="{{ $obj->address }}" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.Status") }}</label>
        <select name="is_active" class="form-control">
            <option value="1" {{ $obj->is_active ? 'selected' : '' }}>{{ __("hr::hr.Enable") }}</option>
            <option value="0" {{ !$obj->is_active ? 'selected' : '' }}>{{ __("hr::hr.Disable") }}</option>
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
