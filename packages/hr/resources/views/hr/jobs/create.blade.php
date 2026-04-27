<form class="row g-3 from-submit-global" method="post" action="{{route("jobs.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.job_title") }} </label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton( route("jobs.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
