<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("finance::finance.principle") }}</label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control"
               data-validation="required" readonly>
               <input type="hidden" name="principle_id" value="{{ $obj->id }}" class="form-control"
               data-validation="required" readonly>
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("finance::finance.from-principle-websites") }}</label>
        <input type="text" value="{{ $obj->principle_website->title ?? "" }}" class="form-control"
               data-validation="required" readonly>
        <input type="hidden" name="from_website_id" value="{{ $obj->principle_website_id }}" class="form-control"
               data-validation="required" readonly>
    </div>
    <div class="col-md-12">
        <label for="to_website_id" class="form-label">{{ __("finance::finance.to-principle-websites") }}</label>
        <select name="to_website_id" id="to_website_id" class="form-control select2" data-validation="required">
            {!! showSelectElement($principle_websites,'title') !!}
        </select>
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
