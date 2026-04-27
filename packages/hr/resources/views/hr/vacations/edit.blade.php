<form class="row g-3 from-submit-global" method="post" action="{{ route('vacations.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.vacation_name") }}</label>
        <input type="text" name="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
        <input type="hidden" name="vacationss_id" value="{{ $obj->id }}" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Number of days during the year") }}</label>
        <input type="number" name="number_of_days" value="{{ $obj->number_of_days }}" class="form-control" data-validation="required">
    </div>
    <div class="col-12">
        <input type="checkbox" value="1" {{ $obj->with_salary == 1 ? 'checked' : '' }} name="with_salary">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Will this leave be deducted from the salary?") }}</label>

    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('vacations.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
