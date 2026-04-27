<form class="row g-3 from-submit-global" method="post" action="{{ route('permissionsSettings.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-6">
        <label for="TextInput" class="form-label">{{__('hr::hr.number_of_hours')}} </label>
        <input type="text" name="number_of_hours" class="form-control" data-validation="required" value="{{ $obj->number_of_hours }}">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{__('hr::hr.per_month')}}</label>
        <input type="text" name="per_month" class="form-control" data-validation="required" value="{{ $obj->per_month }}">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.shifts") }} </label>
        <select name="shifts_ids[]" id="shifts_ids" class="form-control select2" multiple data-validation="required">
            <option value="">{{ __('hr::hr.choose')}}</option>
            @foreach ($shifts as $shift)
                <option value="{{ $shift->id }}" {{in_array($shift->id, $obj->shifts->pluck('id')->toArray()) ? 'selected' : ''}}>{{ $shift->name }}</option>
            @endforeach
        </select>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton( route('permissionsSettings.update', $obj->id) ) !!}
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
