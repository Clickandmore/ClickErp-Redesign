<script>
    $(document).on('input change', 'input[name="emp_value"], select[name="type"], input[name="value"]', function () {
        var value = parseFloat($('input[name="value"]').val()) || 0;
        var empValue = parseFloat($('input[name="emp_value"]').val()) || 0;
        var type = $('select[name="type"]').val();
        var companyValue = 0;

        if (type == 0 && empValue !== '') {
            companyValue = 100 - empValue;
        } else {
            companyValue = value - empValue;
        }

        $('input[name="company_value"]').val(companyValue);
    });
    $(document).on('click', '.toggle-status', function () {
        var btn = $(this);
        var id = btn.data('id');
        var tableName = btn.data('table');
        var status = btn.hasClass('btn-success');
        var confirmMsg = 'هل أنت متأكد من أنك تريد ' + (status ? 'تعطيل' : 'تنشيط') + ' هذا العنصر؟';

        Swal.fire({
            title: 'تأكيد',
            text: confirmMsg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم',
            cancelButtonText: 'الغاء',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{route('toggleStatus')}}',
                    type: 'POST',
                    data: {table_name: tableName, id: id},
                    success: function (response) {
                        console.log(200);
                        handleSuccessResponce(response);
                        var statusText = response.data.status;
                        console.log(statusText);
                        btn.toggleClass('btn-secondary btn-success').text(statusText);
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        console.log(500);
                        handleErrorResponce(errorObj);
                    }
                });
            }
        });
    });
</script>
