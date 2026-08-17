<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}"
      enctype="multipart/form-data">

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }} </label>
        <input type="text" name="title" class="form-control"
               data-validation="required" >
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.city") }} </label>
        <select name="parent_id" class="form-control" >
            {!! showSelectElement($cities, 'title') !!}
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
