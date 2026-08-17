<form class="row g-3 from-submit-update-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @method('PUT')

    <div class="col-12">
        <label class="form-label">{{ __('finance::finance.name') }}</label>
        <input type="text" name="name" class="form-control" data-validation="required" value="{{ $obj->name }}">
    </div>

    <input type="hidden" name="code" value="{{ $obj->code }}">
    <input type="hidden" name="status" value="{{ $obj->status->value }}">
    <input type="hidden" name="parent_id" value="{{ $obj->parent_id ?? 'without' }}">
    <input type="hidden" name="type" value="{{ $obj->type->value }}">
    <input type="hidden" name="operation_type" value="{{ $obj->operation_type }}">

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
