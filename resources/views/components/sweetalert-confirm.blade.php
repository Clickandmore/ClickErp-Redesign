@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">
@endpush

@push('js')
    <script>
        $('.btnOfTargetedForm').click(function(event) {
            event.preventDefault(); // Prevent form submission

            let targetForm = $('#datatargetForm');

            swal({
                title: "{{ __('admin.areYouSure') }}",
                text: "",
                icon: 'warning',
                dangerMode: true,
                buttons: {
                    cancel: "{{ __('admin.no') }}",
                    delete: "{{ __('admin.yes') }}",
                }
            }).then(function(willDelete) {
                if (willDelete) {
                    swal("{{ __('admin.success') }}", {
                        icon: "success",
                        buttons: {
                            info: "{{ __('admin.ok') }}",
                        }
                    }).then(function() {
                        targetForm.off('submit').submit(); // Submit the form
                    });
                }
            });
        });
    </script>
    <script src="{{ asset('plugins/sweetalert/sweetalert.min.js') }}"></script>
@endpush
