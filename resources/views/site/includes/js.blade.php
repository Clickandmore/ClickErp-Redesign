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
@yield('js')
