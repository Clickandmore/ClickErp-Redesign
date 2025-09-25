<script src="{{asset('site')}}/assets/js/swiper-bundle.min.js"></script>
<script src="{{asset('site')}}/assets/js/main.js"></script>
<script src="{{asset('site')}}/assets/js/app.js?v={{time()}}"></script>

<script src="{{asset('site')}}/assets/js/filepond.min.js"></script>
<script>
    $(document).ready(function () {
        $('.supportBtn').click(function () {
            $('.links').slideToggle(300);
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('admin')}}/assets/vendor/toster/js/jquery.toast.js"></script>
<script src="{{asset('admin')}}/assets/js/custom.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="{{asset('site')}}/assets/js/bs-stepper.js?v={{time()}}"></script>


@yield('js')
