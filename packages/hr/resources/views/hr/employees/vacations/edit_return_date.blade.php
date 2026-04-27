<form class="row g-3 from-submit-global" method="post" action="{{ route('employee.vacations-return-date.update', $obj->id) }}" enctype="multipart/form-data">
    @method('put')


    <div class="col-12">
        <label for="return_date" class="form-label">{{ __("hr::hr.return_date") }}</label>
        <input type="date" id="return_date" value="{{ $obj->return_date }}" min="{{ $obj->from_date }}" name="return_date" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file"  name="return_file" class="dropify">
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" id="submitBtn" type="submit">{{ __('buttons.save') }}</button>
        <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        const objReturnDate = new Date('{{ $obj->from_date }}');

        function validateReturnDate() {
            const returnDate = new Date($('#return_date').val());
            if (returnDate <= objReturnDate) {
                warningToster('ملاحظة', "تحقق من ان تاريخ العودة بعد تاريخ طلب الاجازة")
                $('#submitBtn').prop('disabled', true);
                return false;
            } else {
                $('#submitBtn').prop('disabled', false);
                return true;
            }
        }

        // Initial validation check
        validateReturnDate();

        // Re-validate on input change
        $('#return_date').on('change', function() {
            validateReturnDate();
        });

        // Validate on form submit
        $('form').on('submit', function(event) {
            if (!validateReturnDate()) {
                event.preventDefault();
            }
        });
    });
</script>
