<form class="row g-3 from-submit-global" method="post" action="{{ route('officialVacations.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name") }} </label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.from_date") }}</label>
        <input type="date" name="from_date" value="{{ $obj->from_date }}" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">  {{ __("hr::hr.to_date") }} </label>
        <input type="date" name="to_date" value="{{ $obj->to_date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('officialVacations.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
