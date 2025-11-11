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

<script>
    $(document).ready(function () {
        $('.supportBtn').click(function () {
            $('.links').slideToggle(300);
        });
    });

    document.getElementById('close-ad').onclick = function () {
        document.getElementById('ad-popup').style.display = 'none';
    };

    function checkInternetConnection() {
        return navigator.onLine;
    }

    document.querySelector('form').addEventListener('submit', function (e) {
        if (!checkInternetConnection()) {
            e.preventDefault();
            Toastify({
                text: "{{__("web.you_are_offline")}}",
                duration: 4000,
                gravity: "top",
                position: "right",
                backgroundColor: "red"
            }).showToast();
        }
    });
    if (!checkInternetConnection()) {
        Toastify({
            text: "{{ __('web.you_are_offline') }}",
            duration: 4000,
            gravity: "top",
            position: "right",
            backgroundColor: "red"
        }).showToast();

    }

</script>
@yield('js')
