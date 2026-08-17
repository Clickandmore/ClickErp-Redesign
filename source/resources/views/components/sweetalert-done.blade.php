@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">
@endpush

@push('js')
    <script>
        $('.btnConfirmTrgt').click(function() {
            swal({
                title: "{{ __('admin.guess_success') }}",
                text: " ",
                icon: 'success',
                timer: 2000, // Display for 2 seconds
                buttons: false, // Disable buttons
            }).then(function() {
                document.getElementById("playerSelectionForm").submit();
            });
        });
    </script>
    <script src="{{ asset('plugins/sweetalert/sweetalert.min.js') }}"></script>
@endpush
