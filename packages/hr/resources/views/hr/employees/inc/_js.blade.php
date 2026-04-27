<script>
    $('#json_array').multifield();
    $('#json_array2').multifield();
    $('#json_array3').multifield();
    $('#json_array4').multifield();

    $.validate({
        ignore: 'input[type=hidden]',
        modules: 'date, security',
        lang: '{{\App::currentLocale()}}',
        validateOnEvent: true
    });


    function registerEmployeeFilterInputs() {
        $('.employee-select-filter').each(function () {
            let $input = $(this);
            let targetSelector = $input.data('target');
            let $select = $(targetSelector);

            if (!$select.length || $select.data('employee-filter-ready')) {
                return;
            }

            let originalOptions = $select.find('option').map(function () {
                return {
                    value: this.value,
                    text: $(this).text(),
                    selected: this.selected,
                };
            }).get();

            $select.data('employee-filter-ready', true);
            $select.data('employee-original-options', originalOptions);

            $input.on('input', function () {
                let query = ($input.val() || '').toString().trim().toLowerCase();
                let currentValue = $select.val();
                let options = $select.data('employee-original-options') || [];

                let filteredOptions = options.filter(function (option) {
                    if (!option.value) {
                        return true;
                    }

                    return option.text.toLowerCase().includes(query);
                });

                if (currentValue && !filteredOptions.some(option => option.value === currentValue)) {
                    let currentOption = options.find(option => option.value === currentValue);
                    if (currentOption) {
                        filteredOptions.unshift(currentOption);
                    }
                }

                $select.empty();

                filteredOptions.forEach(function (option) {
                    let optionElement = new Option(option.text, option.value, false, option.value === currentValue);
                    $select.append(optionElement);
                });
            });
        });
    }

    registerEmployeeFilterInputs();

    function calculateTotal() {
        var totalDaysInput = document.getElementsByClassName('total_days');
        var tokenDaysInput = document.getElementsByClassName('token_days');
        var remainDaysInput = document.getElementsByClassName('remain_days');

        for (var i = 0; i < totalDaysInput.length; i++) {
            var totalDays = parseFloat(totalDaysInput[i].value) || 0;  // Get the total days (max)
            var tokenDays = parseFloat(tokenDaysInput[i].value) || 0;  // Get the entered token days

            // Ensure token days are not more than total days
            if (tokenDays > totalDays) {
                Swal.fire({
                    title: '{{__("auth.an error occurred")}}',
                    text: '{{__('hr::hr.Token days cannot exceed total days')}} ({{__('hr::hr.max')}}: ' + totalDays + ').',
                    icon: 'warning',
                    confirmButtonText: '{{__('buttons.cancel')}}',
                    confirmButtonColor: '#3085d6'
                });
                tokenDaysInput[i].value = totalDays;
                tokenDays = totalDays;
            }

            var remainDays = totalDays - tokenDays; // Calculate remaining days
            remainDaysInput[i].value = remainDays;  // Set the remaining days
        }
    }


    $(document).ready(function () {
        $(document).on('change', '.allowancesSelect', function () {
            getAllowanceData($(this).val(), $(this).closest('.group'));
        });
    });

    $(document).ready(function () {
        $(document).on('change', '.vacationsSelect', function () {
            // Get the selected option's data-max value
            var maxDays = $(this).find(':selected').data('max');

            // Find the nearest total_days input within the same row (tr) and set its max attribute
            $(this).closest('tr').find('.total_days').attr('max', maxDays);

            // Optionally, you can also set the value of total_days to maxDays if needed
            $(this).closest('tr').find('.total_days').val(maxDays);
        });
    });

    function productPrice(obj) {
        let productPrice = $(obj).find(":selected").attr('data-value');
        let productPrice2 = $(obj).find(":selected").attr('data-type');
        if (productPrice2 == 1) {
            productPrice3 = 'يوم';
        } else if (productPrice2 == 2) {
            productPrice3 = 'قيمة';
        } else {
            productPrice3 = 'نسبة';
        }
        let tr = $(obj).closest('tr');
        $('.valueInput', tr).val(productPrice);
        $('.typeInput', tr).val(productPrice3);
        $('.typeInput2', tr).val(productPrice2);
    }

    function checkOther() {
        updateNetSalary();
    }

</script>
<script>
    function toggleInputs() {
        var accountInfo = document.getElementById("account-info");
        if (event.target.checked) {
            accountInfo.classList.remove("d-none");
        } else {
            accountInfo.classList.add("d-none");
        }
    }
</script>
<script>
    function updateNetSalary() {
        let salary = parseFloat(document.getElementById('salary').value) || 0;
        let totalAllowances = 0;
        let totalInsurances = 0;
        let totalTax = 0;

        // Calculate total taxes from the Tax table
        $('#taxTable tbody tr').each(function () {
            let checkbox = $(this).find('input.tax[type="checkbox"]');
            if (checkbox.is(':checked')) {
                let taxValue = parseFloat($(this).find('input.form-control').val()) || 0; // Updated selector to use class
                totalTax += (taxValue / 100) * salary;
            }
        });

        // Calculate total insurance values from the Insurance table
        $('#insuranceTable tbody tr').each(function () {
            let checkbox = $(this).find('input.insurance[type="checkbox"]');
            if (checkbox.is(':checked')) {
                let insuranceType = checkbox.data('type');
                let insuranceId = checkbox.val();  // Get insurance ID from checkbox value
                let insuranceValue = parseFloat($(this).find(`input[name="insuranceValue[${insuranceId}]"]`).val()) || 0; // Use insurance ID to find value

                if (insuranceType == 0) {
                    // Type 0: Percentage
                    totalInsurances += (insuranceValue / 100) * salary;
                } else {
                    // Type 1: Fixed value
                    totalInsurances += insuranceValue;
                }
            }
        });

        // Calculate total allowances from the Allowance table
        $('#allowanceTable tbody tr').each(function () {
            let checkbox = $(this).find('input.allowances[type="checkbox"]');
            if (checkbox.is(':checked')) {
                let allowanceType = checkbox.data('type');
                let allowanceId = checkbox.val();  // Get allowance ID from checkbox value
                let allowanceValue = parseFloat($(this).find(`input[name="valueInput[${allowanceId}]"]`).val()) || 0; // Use allowance ID to find value

                if (allowanceType == 1) {
                    // Type 1: Daily rate
                    totalAllowances += (salary / 30) * allowanceValue;

                } else if (allowanceType == 2) {
                    // Type 2: Fixed value
                    totalAllowances += allowanceValue;
                } else {
                    // Type 3: Percentage
                    totalAllowances += (allowanceValue / 100) * salary;
                }
            }
        });

        // Update total fields
        document.getElementById('totalTax').value = totalTax.toFixed(2);
        document.getElementById('totalInsurances').value = totalInsurances.toFixed(2);
        document.getElementById('totalAllowances').value = totalAllowances.toFixed(2);

        // Calculate net salary
        let netSalary = (salary + totalAllowances - totalInsurances - totalTax).toFixed(2);
        document.getElementById('netSalary').value = netSalary;
    }

    // Recalculate net salary when input changes
    $(document).on('input', '.form-control', function () {
        updateNetSalary();
    });

    // Recalculate net salary when checkboxes are toggled
    $(document).on('change', '.tax, .insurance, .allowances', function () {
        updateNetSalary();
    });

    $(document).ready(function () {
        $('#salary').trigger('input');
        updateNetSalary();
        registerEmployeeFilterInputs();

        $('#createAdminToggle').on('change', function () {
            if ($(this).is(':checked')) {
                $('#passwordField').show();
                $('#createAdminAccountInput').val('1');
            } else {
                // Hide fields if the checkbox is unchecked
                $('#passwordField').hide();
                $('#createAdminAccountInput').val('0');
            }

        });
    });
</script>
