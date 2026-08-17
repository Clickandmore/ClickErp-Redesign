<form class="row g-3 from-submit-global" method="post" action="{{route("permissionsSettings.store")}}" enctype="multipart/form-data">
    <div class="col-6">
        <label for="TextInput" class="form-label">{{__('hr::hr.number_of_hours')}} </label>
        <input type="text" name="number_of_hours" class="form-control" data-validation="required">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{__('hr::hr.per_month')}}</label>
        <input type="text" name="per_month" class="form-control" data-validation="required">
    </div>

    <div class="col-md-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.shifts") }} </label>
        <select name="shifts_ids[]" id="shifts_ids" class="form-control select2" multiple data-validation="required">
            {!! showSelectElement($shifts,'name') !!}
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("permissionsSettings.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
@section('js')
<script src="{{asset('admin')}}/assets/js/bundle/select2.bundle.js"></script>
<script>
    $(".max-select").select2({
        placeholder: "Select",
        maximumSelectionSize: 2,
    });
</script>
@endsection
