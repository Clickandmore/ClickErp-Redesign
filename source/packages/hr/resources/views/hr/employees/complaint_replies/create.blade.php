<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">


    <input type="hidden" name="complaint_id" value="{{request('complaint_id')}}" id="">
    <input type="hidden" name="admin_id" value="{{auth('admin')->user()->id}}" id="">
    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.employees") }}  </label>
        <select class="form-control show-tick ms" name="employee_id" data-validation="required" disabled>
            <option value=""> {{ __("hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ $employee->id == @$complaint->employee_id ? "selected" : "" }}>{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.date") }}  </label>
        <input type="date" name="date" disabled value="{{ $complaint->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-12">
        <label for="TextInput" class="form-label"> {{ __("hr.complaint_text") }}  </label>
        <textarea name="description" disabled class="form-control" cols="5" rows="4">{{ $complaint->description }}</textarea>
    </div>
    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.reply") }}  </label>
        <textarea name="text" class="form-control" cols="15" rows="4"></textarea>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.date") }}  </label>
        <input type="date" name="date" value="{{date('Y-m-d')}}" class="form-control" data-validation="required">
    </div>




    <div class="col-12 modal-footer">
       {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>


