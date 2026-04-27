<script>
    function handleSelectChange() {
        var sanctionId = $('select[name="sanction_id"]').val();
        if (sanctionId) {
            $.ajax({
                url: '{{ route("sanctions.show", ":id") }}'.replace(':id', sanctionId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('.valueSac').val(data.value);
                    $('.type').val(data.type);
                    var type = data.type;
                    if (type == 1) {
                        $('.typeName').val('{{ __("hr::hr.days_nmuber") }} = ' + data.value);
                    } else if (type == 2) {
                        $('.typeName').val('{{ __("hr::hr.fixed_amount") }} = ' + data.value);
                    } else if (type == 3) {
                        $('.typeName').val('{{ __("hr::hr.percentage") }} =' + data.value);
                    } else {
                        $('.typeName').val('');
                    }
                    updateValue();
                },
                error: function() {
                    alert('حدث خطأ أثناء جلب بيانات الراتب.');
                }
            }).done(function() {
                // Ensure this binding only happens once for keyup events
                $('.salary').off('keyup').on('keyup', function() {
                    updateValue();
                });
            });
        }
    }

    function updateValue() {
        var salary = $('.salary').val() || 0;
        var monthDays = $('.month_days').val();
        var value = $('.valueSac').val();
        var type = $('.type').val();
        if (type == 1) {
            value = (salary / monthDays) * value;
            value =  Math.round(value * 100) / 100;
        } else if (type == 2) {
            value =  value;
        } else if (type == 3) {
            value = (salary * value) / 100;
        } else {
            value = '';
        }
        $('.valueEmp').val(value).trigger('input');
    }

    $(document).ajaxComplete(function() {
        $('select[name="employee_id"]').on('change', function() {
            var employeeId = $(this).val();
            $.ajax({
                url: '{{ route("employees.show", ":id") }}'.replace(':id', employeeId),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('.salary').val(data.salary);
                    $('.month_days').val(data.month_days);
                    updateValue();
                },
                error: function() {
                    alert('حدث خطأ أثناء جلب بيانات الراتب.');
                }
            });
        });
        $('select[name="sanction_id"]').on('change', handleSelectChange);
        // Attach change event handler to the select element

        // Set up a MutationObserver to detect when options are added to the select
        const selectElement = document.querySelector('select[name="sanction_id"]');
        const observer = new MutationObserver(function(mutationsList) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    // When new options are added, trigger the change event
                    $(selectElement).trigger('change');
                }
            }
        });

        // Start observing the select element for child node changes (i.e., new options)
        observer.observe(selectElement, { childList: true });
    });


</script>
