<script>
    $(document).ready(function() {
        $('#form-renew-contract').on('submit', function(e) {
            e.preventDefault();
            let $submitBtn = $(this).find('button[type="submit"]');
            let originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> {{ __("ingaz.sending") }}');

            let formData = new FormData(this);
            let url = $(this).attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Toastify({
                        text: response.message,
                        duration: 4000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                        stopOnFocus: true
                    }).showToast();

                    $('#form-renew-contract')[0].reset();

                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessages = '';
                        for (let key in errors) {
                            errorMessages += `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("ingaz.error") }}',
                            html: `<div>${errorMessages}</div>`,
                        });
                    } else {
                        Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                    }
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        })
    })
</script>
