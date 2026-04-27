<script>
    function getTypeHtml() {
        var selectedOption = document.getElementById("allowance-type").value;
        document.getElementById("day-field").style.display = (selectedOption == 1) ? "block" : "none";
        document.getElementById("field").style.display = (selectedOption == 1 || selectedOption == 2 || selectedOption == 3) ? "block" : "none";
        document.getElementById("value-field").style.display = (selectedOption == 2) ? "block" : "none";
        document.getElementById("percentage-field").style.display = (selectedOption == 3) ? "block" : "none";
    }
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
