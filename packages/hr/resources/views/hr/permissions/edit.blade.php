<form class="row g-3 from-submit-global" method="post" action="{{ route('permissions.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.permission") }} </label>
        <input type="text" name="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
    </div>
    {{-- <div class="col-6">
        <label for="TextInput" class="form-label">عدد الساعات </label>
        <input type="text" name="number_of_hours" value="{{ $obj->number_of_hours }}" class="form-control" data-validation="required">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">عدد المرات خلال الشهر </label>
        <input type="text" name="per_month" value="{{ $obj->per_month }}" class="form-control" data-validation="required">
    </div> --}}

    <div class="col-12 modal-footer">
        {!! submitButton( route('permissions.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
