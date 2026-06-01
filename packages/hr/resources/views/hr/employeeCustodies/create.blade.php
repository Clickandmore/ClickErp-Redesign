<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
    <div class="col-12">
        <h6 class="border-bottom pb-2 mb-0">{{ __("hr::hr.custody_assignment_data") }}</h6>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.employees") }}</label>
        <select class="form-control show-tick ms" name="employee_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.custody_location") }}</label>
        <select class="form-control show-tick ms" name="location_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}">{{ $location->name }}{{ $location->code ? ' - ' . $location->code : '' }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.received_at") }}</label>
        <input type="date" name="received_at" value="{{ date('Y-m-d') }}" class="form-control">
    </div>

    <div class="col-12">
        <h6 class="border-bottom pb-2 mt-2 mb-0">{{ __("hr::hr.custody_details") }}</h6>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.custody_name") }}</label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.custody_code") }}</label>
        <input type="text" name="code" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.value") }}</label>
        <input type="number" step="0.01" min="0" name="value" class="form-control" value="0">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.Status") }}</label>
        <select class="form-control show-tick ms" name="status" data-validation="required">
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.returned_at") }}</label>
        <input type="date" name="returned_at" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file" name="file" class="dropify">
    </div>

    <div class="col-md-12">
        <label class="form-label">{{ __("hr::hr.notes") }}</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
