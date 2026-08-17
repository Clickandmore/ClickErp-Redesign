<form class="row g-3 from-submit-global" method="post" action="{{ route('jobs.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.job_title") }} </label>
        <input type="text" name="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
        <input type="hidden" name="job_id" value="{{ $obj->id }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('jobs.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
