<script>
        // $(document).ajaxComplete(function() {
        //     function setTaxRate() {
        //         var selectedOption = $('#tax_setting_id option:selected');
        //         var dataValue = selectedOption.data('value');
        //         var accountId = selectedOption.data('account-id');
        //         $('#tax_account_id').val(accountId);
        //         $('#tax_rate').val(dataValue);
        //         calculateTax();
        //     }

        //     $('#tax_setting_id').on('change', function() {
        //         setTaxRate();
        //     });
        //     setTaxRate();
        // });
      function toggleTaxFields() {
            var yesRadio = document.getElementById('is_tax_yes');
            var taxFields = document.getElementById('tax-fields');

            if (yesRadio.checked) {
                taxFields.style.display = 'block';
            } else {
                taxFields.style.display = 'none';
            }
            calculateTax();
        }

        function calculateTax() {
            var isTaxChecked = document.getElementById('is_tax_yes').checked;
            var value = parseFloat(document.getElementById('value').value) || 0;
            var taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            var taxAmount = 0;
            var net = value;

            if (isTaxChecked) {
                taxAmount = value - ((value * 100) / (taxRate + 100));
                net = (value * 100) / (taxRate + 100);
            }

            document.getElementById('tax_amount').value = taxAmount.toFixed(2);
            document.getElementById('net').value = net.toFixed(2);
        }



    $(document).on('click', '.confirmButton', function (e) {
        e.preventDefault()
        let buttonObj = $(this);
        let confirmRoute = buttonObj.attr("confirm-route");
        let bondId = buttonObj.attr("confirm-id");
        let postData = {"bond_id":bondId};
        swal.fire({
            title: "{{__('buttons.Are You Sure')}}",
            text: "{{__('buttons.You Can not to rollback')}}",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{{__('buttons.accept')}}",
            cancelButtonText: "{{__('buttons.cancel')}}",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: confirmRoute,
                    data:postData,
                    type: 'POST',
                    beforeSend: function () {
                    },
                    complete: function () {
                    },
                    success: function (data) {
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('buttons.Confirm successfully')}}",
                            confirmButtonText: "{{__('buttons.close')}}",
                        });
                        reloadDataTable();
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        });
    });

    function getPaymentTypeHtml(type) {
        let obj = $("#payment_type");
        let paymentTypeValue = obj.find(":selected").val();
        let url = '{{ route('finance.bonds-payment.show', ':id') }}';
        url = url.replace(':id', paymentTypeValue);
        url += "?type="+type;
        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function () {
                $("#content-div").html("");
            },
            complete: function () {
            },
            success: function (result) {
                $("#content-div").html(result.data.html);
            },
            error: function (errorObj, errorText, errorThrown) {
                handleErrorResponce(errorObj);
            }
        });
    }
</script>
