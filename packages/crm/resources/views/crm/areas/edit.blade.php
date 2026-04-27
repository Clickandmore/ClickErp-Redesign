<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('PUT')

        <div class="col-12">
            <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
            <input type="text" name="title" value="{{ $obj->title }}" class="form-control"  data-validation="required" >
        </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.city") }} </label>
        <select name="parent_id" class="form-control" >
            {!! showSelectElement($cities, 'title', $obj->parent_id) !!}
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
