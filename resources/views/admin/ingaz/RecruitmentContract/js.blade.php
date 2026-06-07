<script>
    $(document).ready(function () {
        // $('.select2').select2({
        //     placeholder: 'اختر الطلب',
        //     allowClear: true
        // });

        // $('#new-client-section').find('input, select').prop('disabled', true);

        $('#showClientSection').change(function () {
            if ($(this).is(':checked')) {
                // $('#new-client-section').find('input, select').prop('disabled', false);
                $('#order-selection').fadeOut();
                $('#maid-selection').removeClass('d-none');
                $('#maid_id').removeClass('d-none');
                // $('#new-maid-section').addClass('d-none');
            } else {
                // $('#new-client-section').find('input, select').prop('disabled', true);
                $('#order-selection').fadeIn();
                $('#maid-selection').addClass('d-none');
                $('#maid_id').addClass('d-none');
                // $('#new-maid-section').removeClass('d-none');
            }
        });


        $('#order_id').change(function () {
            var orderId = $(this).val();
            if (orderId == null) {
                orderId = $(this).data('id');
            }
            console.log(orderId)
            $.ajax({
                url: '{{route('showDataOfOrder')}}',
                method: 'GET',
                data: { orderId: orderId },
                beforeSend: function () {
                    $('#loading-wrapper').fadeIn();
                },
                complete: function () {
                    $('#loading-wrapper').fadeOut();
                },
                success: function (response) {
                    showUserData(response.data.data.user)
                    showMaidData(response.data.data.maid)
                    $('#maid-selection').addClass('d-none');
                },
                error: function (xhr, status, error) {
                    //console.error(xhr.responseText);
                }
            });
        });

        function showUserData(response) {
            const userData = response;
            const isCompanyClient = String(userData.client_type || '').toLowerCase() === 'company';
            const clientDocumentNumber = isCompanyClient
                ? (userData.tax_number || userData.document_number || userData.national_num || '')
                : (userData.document_number || userData.national_num || '');
            //
            $('#client_name').val(userData.name);
            $('#client_phone').val(userData.phone);
            $('#client_nationality_id').val(userData.nationality_id);
            $('#client_email').val(userData.email);
            $('#client_national_num').val(clientDocumentNumber);
            $('#client_birthdate').val(userData.birthdate);
            $('#phone2').val(userData.phone2);
            $('#address').val(userData.address);
            $('#city').val(userData.city);
            $('#beds_num').val(userData.rooms_num);
            $('#family_num').val(userData.family_num);
            $('#children').val(userData.children);
            $('#job').val(userData.job);

            const $documentInput = $('#client_national_num');
            const $personFieldWrappers = $('#client_birthdate, #beds_num, #family_num, #children, #job')
                .map(function () {
                    return $(this).closest('.js-client-person-field, .col-md-6, .col-lg-6')[0];
                });

            $documentInput.closest('.js-client-document-field, .col-md-6, .col-lg-6')
                .find('label').first()
                .html((isCompanyClient ? @json(helperTrans('ingaz.tax_number')) : @json(helperTrans('ingaz.national_num'))) + ' <span class="text-danger">*</span>');
            $personFieldWrappers.toggleClass('d-none', isCompanyClient);
            $personFieldWrappers.find('input, select, textarea').prop('disabled', isCompanyClient);

            if (isCompanyClient) {
                $personFieldWrappers.find('input, select, textarea').val('');
            }
        }

        function showMaidData(response) {
            const maidData = response;

            // $('#maid_name').val(maidData.name);
            $('#maid_passport').val(maidData.passport_num);
            $('#maid_religion').val(maidData.religion);
            $('#maid_job').val(maidData.job);
            $('#maid_nationality').val(maidData.nationality);
            $('#maid_salary').val(maidData.salary);
            $('#experience_years').val(maidData.experience_years);
            $('#age').val(maidData.age);
        }

        $('#maid_id').change(function () {
            var maidId = $(this).val();
            $.ajax({
                url: '{{route('showDataOfMaid')}}',
                method: 'GET',
                data: { maidId: maidId },
                beforeSend: function () {
                    $('#loading-wrapper').fadeIn();
                },
                complete: function () {
                    $('#loading-wrapper').fadeOut();
                },
                success: function (response) {
                    showMaidData(response.data.data.maid)
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

    });
</script>

<script>
    $(document).ready(function () {
        var currentStep = 0;
        var steps = $('.step');

        function showStep(stepIndex) {
            $(steps[currentStep]).removeClass('active');
            $(steps[currentStep]).addClass('d-none');
            $(steps[stepIndex]).addClass('active');
            $(steps[stepIndex]).removeClass('d-none');
            currentStep = stepIndex;

            if (currentStep === 0) {
                $('#prevBtn').addClass('d-none');
            } else {
                $('#prevBtn').removeClass('d-none');
            }

            if (currentStep === steps.length - 1) {
                $('#nextBtn').addClass('d-none');
                $('#submitBtnn').removeClass('d-none');
            } else {
                $('#nextBtn').removeClass('d-none');
                $('#submitBtnn').addClass('d-none');
            }
        }

        $('#nextBtn').click(function () {
            if (currentStep < steps.length - 1) {
                // Validate current step before proceeding
                if (validateStep(currentStep)) {
                    showStep(currentStep + 1);
                }
            }
        });

        $('#prevBtn').click(function () {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        });

        function validateStep(stepIndex) {
            // Implement validation logic for each step
            // Return true if validation passes, false otherwise
            return true; // Placeholder, replace with actual validation
        }
    });


</script>
