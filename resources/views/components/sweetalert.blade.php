@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">
@endpush

@push('js')
    <script>
        $('.btnDeleteTrgt').click(function() {
            let SelectedId = $(this).data("id");
            let DeleteForm = $('#dataDeleteForm-' + SelectedId);
            swal({
                title: "{{ __('admin.areYouSure') }}",
                text: "{{ __('admin.cantRecoverData') }}",
                icon: 'warning',
                dangerMode: true,
                buttons: {
                    cancel: "{{ __('admin.no') }}",
                    delete: "{{ __('admin.yesDelete') }}",
                }
            }).then(function(willDelete) {
                if (willDelete) {
                    swal("{{ __('admin.deletedSuccess') }}", {
                        icon: "success",
                        buttons: {
                            info: "{{ __('admin.ok') }}",
                        }
                    }).then(function() {
                        DeleteForm.submit();
                    });
                }
                // {{-- else { --}}
                // {{--    swal("{{__('locale.deleteCancelled')}}", { --}}
                // {{--        title: "{{__('locale.cancelled')}}", --}}
                // {{--        icon: "error", --}}
                // {{--    }); --}}
                // {{-- } --}}
            });
        });
    </script>
    <script src="{{ asset('plugins/sweetalert/sweetalert.min.js') }}"></script>
@endpush
