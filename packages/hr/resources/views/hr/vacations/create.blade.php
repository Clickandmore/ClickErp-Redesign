<form class="row g-3 from-submit-global" method="post" action="{{route("vacations.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.vacation_name") }}</label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Number of days during the year") }}</label>
        <input type="number" name="number_of_days" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <input type="checkbox" name="with_salary" value="1" checked>
        <label for="TextInput" class="form-label">{{ __("hr::hr.Will this leave be deducted from the salary?") }}</label>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("vacations.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
