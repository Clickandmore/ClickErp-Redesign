@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/print/print.css') }}">
    @if (@$langDir == 'rtl')
        <link rel="stylesheet" type="text/css" href="{{ asset('plugins/print/print-rtl.css') }}">
    @endif
@endpush
@push('js')
    {{--
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    <script src="{{ asset('plugins/print/jquery.printarea.js') }}" type="text/JavaScript"></script>
    <script type='text/javascript'>
        $(function () {
            $('.Printpage').on('click', function () {
                $(".PrintArea").printArea();
                return false;
            });
        });
    </script>
@endpush
<a class="btn btn-danger Printpage mx-1" href="#"><i class="fa fa-print"></i> @lang('admin.print') </a>