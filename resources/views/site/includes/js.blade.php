<script src="{{asset('site')}}/assets/js/swiper-bundle.min.js"></script>
<script src="{{asset('site')}}/assets/js/main.js"></script>
<script src="{{asset('site')}}/assets/js/app.js?v={{time()}}"></script>

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

    const closeAdButton = document.getElementById('close-ad');
    if (closeAdButton) {
        closeAdButton.onclick = function () {
            const adPopup = document.getElementById('ad-popup');
            if (adPopup) {
                adPopup.style.display = 'none';
            }
        };
    }

    function checkInternetConnection() {
        return navigator.onLine;
    }

    const forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
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

    function unlockScrollIfNoOverlay() {
        const hasOverlay = document.querySelector('.modal.show, .offcanvas.show, .fancybox__container[aria-hidden="false"]');
        if (!hasOverlay) {
            document.body.classList.remove('modal-open', 'compensate-for-scrollbar');
            document.documentElement.classList.remove('with-fancybox');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    }

    window.addEventListener('load', function () {
        unlockScrollIfNoOverlay();
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.style.display = 'none';
        }
    });

    document.addEventListener('hidden.bs.modal', unlockScrollIfNoOverlay);
    document.addEventListener('hidden.bs.offcanvas', unlockScrollIfNoOverlay);

</script>

@yield('js')
