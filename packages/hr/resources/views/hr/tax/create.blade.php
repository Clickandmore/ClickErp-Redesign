<form class="row g-3 from-submit-global" method="post" action="{{route("tax.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name_of_the_slice") }}</label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label class="form-label">{{ __("hr::hr.Tax rate") }} </label>
        <input type="number" class="form-control" max="100" name="value" data-validation="required" id="taxRateInput">
        <span id="taxRateError" style="color: red; display: none;">{{__("hr::hr.Value must be less than or equal to 100")}}</span>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route("tax.store")) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).ready(function() {
        $('#taxRateInput').on('input', function() {
            var max = parseFloat($(this).attr('max'));
            var value = parseFloat($(this).val());

            if (value > max || value < 0) {
                $('#taxRateError').show();
                $(this).val(max);
            } else {
                $('#taxRateError').hide();
            }
        });
    });
</script>

