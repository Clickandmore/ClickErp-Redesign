<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="title" class="form-label">{{ __('finance::finance.title') }} </label>
        <input type="text" name="title" id="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="title" class="form-label">{{ __('finance::finance.big_unit') }} </label>
        <select id="parent_id" name="parent_id" class="form-control">
            <option value=""> {{ __('finance::finance.choose') }}  </option>
            <option value="">{{ __('finance::finance.without') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" >{{ $unit->title }}</option>
            @endforeach
       </select>
    </div>


    <div class="col-12 inputField">
        <label for="value" class="form-label">{{ __('finance::finance.value_for_big_unit') }}</label>
        <input type="number" name="value" id="value" class="form-control myInput" data-validation="required,number">
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).ready(function() {
        // Initially hide all input fields
        $('.inputField').hide();

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
