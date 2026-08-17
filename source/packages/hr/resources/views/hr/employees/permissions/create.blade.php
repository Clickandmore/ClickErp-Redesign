<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <input type="hidden" name="employee_id" value="{{ $employee_id }}">

    <div class="col-6">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Permission") }} </label>
        <select class="form-control show-tick ms" name="permission_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($permissions as $permission)
                <option value="{{ $permission->id }}">{{ $permission->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="date" class="form-label">{{ __("hr::hr.date") }} </label>
        <input type="date" name="date" min="{{date('Y-m-d')}}" class="form-control" data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-6">
        <label for="time" class="form-label">{{ __("hr::hr.Time (minutes)") }}</label>
        <input type="number" id="time" name="time" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <textarea name="reason" class="form-control" data-validation="required" rows="4"></textarea>
    </div>
    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file"  name="file" class="dropify">
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
