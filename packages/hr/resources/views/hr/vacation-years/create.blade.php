<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    @if(!$checkYear)
        @if(url()->previous() != $storeRoute)
        <h5 style="color:red;">{{ trans('finance.the year is not active') }}</h5>
        @endif
        <input type="hidden" name="status" value="1">
    @endif
    <div class="col-12">
        <label for="title" class="form-label">{{ __('hr::hr.title') }} </label>
        <input type="text" name="title" id="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="from_date" class="form-label">{{ __('hr::hr.from_date') }}</label>
        <input type="date" name="from_date" id="from_date" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="to_date" class="form-label">{{ __('hr::hr.to_date') }}</label>
        <input type="date" name="to_date" id="to_date" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
