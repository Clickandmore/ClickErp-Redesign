<form class="row g-3 from-submit-global" method="post" action="{{route("officialVacations.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name") }} </label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.from_date") }}</label>
        <input type="date" name="from_date" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">  {{ __("hr::hr.to_date") }} </label>
        <input type="date" name="to_date" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("officialVacations.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
