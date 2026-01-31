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

    function initNewsTicker() {
        const tickerLists = document.querySelectorAll('.news-ticker .news-items');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        tickerLists.forEach(function (list) {
            const items = Array.from(list.querySelectorAll('li'));
            if (items.length === 0) {
                return;
            }

            let activeIndex = 0;
            items.forEach(function (item, index) {
                const isActive = index === activeIndex;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            if (items.length < 2 || reduceMotion) {
                return;
            }

            setInterval(function () {
                items[activeIndex].classList.remove('is-active');
                items[activeIndex].setAttribute('aria-hidden', 'true');
                activeIndex = (activeIndex + 1) % items.length;
                items[activeIndex].classList.add('is-active');
                items[activeIndex].setAttribute('aria-hidden', 'false');
            }, 3500);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewsTicker);
    } else {
        initNewsTicker();
    }
</script>

@yield('js')
