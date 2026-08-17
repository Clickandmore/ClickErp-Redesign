<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")
    <div class="col-12">
        <label for="title" class="form-label">{{ __('finance::finance.title') }} </label>
        <input type="text" name="title" id="title" class="form-control" data-validation="required" value="{{$obj->title}}">
    </div>

    <div class="col-12">
        <label for="title" class="form-label">{{ __('finance::finance.big_unit') }} </label>
        <select id="parent_id" name="parent_id" class="form-control">
            <option value=""> {{ __('finance::finance.choose') }}  </option>
            <option value="">{{ __('finance::finance.without') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" {{ $obj->parent_id == $unit->id ? 'selected' : '' }}>{{ $unit->title }}</option>
            @endforeach
       </select>
    </div>


    <div class="col-12 inputField">
        <label for="value" class="form-label">{{ __('finance::finance.value_for_big_unit') }}</label>
        <input type="number" name="value" id="value" class="form-control myInput" data-validation="required,number" value="@if (isset($obj->value)){{$obj->value}}@else{{""}}@endif">
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).ready(function() {
        // Initially hide all input fields
        $('.inputField').hide();

        $parent = $('#parent_id').val();
        if($parent !== '') {
            $('.inputField').show();
        }

        $(document).on('change', '#parent_id', function (e) {
            var selectedValue = $(this).val();

            $('.myInput').val('');

            // Hide all input fields
            $('.inputField').hide();

            // Show input fields based on the selected value
            if (selectedValue !== '') {
                $('.inputField').show();
            }
        });
    });
</script>
