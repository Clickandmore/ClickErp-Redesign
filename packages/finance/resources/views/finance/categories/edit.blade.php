<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-12">
        <label for="title" class="form-label">{{ __("finance::finance.name") }}</label>
        <input type="text" name="title" id="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-12">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" id="code" class="form-control" data-validation="required" value="{{ $obj->code }}">
    </div>

    <div class="col-md-12">
        <label for="title" class="form-label">{{ __("finance::finance.main_category") }}</label>
        <select name="parent_id" id="parent_id" class="form-control">
            <option value="">{{ __("finance::finance.without") }}</option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}" {{ $item->id == $obj->parent_id ? "selected" : "" }}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
