<style>
    html {
        scroll-behavior: smooth;
        scrollbar-color: #315a63 #e8eef0;
        scrollbar-width: auto;
    }

    ::-webkit-scrollbar {
        width: 14px;
        height: 14px;
    }

    ::-webkit-scrollbar-track {
        background: #e8eef0;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: #315a63;
        border: 3px solid #e8eef0;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #1f434a;
    }

    .page-scroll-controls {
        position: fixed;
        inset-inline-end: 18px;
        bottom: 22px;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .page-scroll-control {
        width: 46px;
        height: 46px;
        border: 2px solid rgba(255, 255, 255, .9);
        border-radius: 50%;
        color: #fff;
        background: #315a63;
        box-shadow: 0 4px 14px rgba(20, 48, 55, .3);
        opacity: .92;
        transition: transform .15s ease, opacity .15s ease, background .15s ease;
    }

    .page-scroll-control:hover,
    .page-scroll-control:focus {
        color: #fff;
        background: #1f434a;
        opacity: 1;
        transform: scale(1.08);
        outline: none;
    }

    .page-scroll-control[disabled] {
        opacity: .35;
        cursor: default;
        transform: none;
    }

    @media (max-width: 767.98px) {
        .page-scroll-controls {
            inset-inline-end: 10px;
            bottom: 14px;
        }

        .page-scroll-control {
            width: 42px;
            height: 42px;
        }
    }
</style>

<div class="page-scroll-controls" aria-label="التنقل داخل الصفحة">
    <button type="button" class="page-scroll-control" id="pageScrollTop" title="أعلى الصفحة" aria-label="أعلى الصفحة">
        <i class="fa fa-angle-up fa-lg" aria-hidden="true"></i>
    </button>
    <button type="button" class="page-scroll-control" id="pageScrollBottom" title="أسفل الصفحة" aria-label="أسفل الصفحة">
        <i class="fa fa-angle-down fa-lg" aria-hidden="true"></i>
    </button>
</div>

<script>
    (function () {
        const topButton = document.getElementById('pageScrollTop');
        const bottomButton = document.getElementById('pageScrollBottom');

        if (!topButton || !bottomButton) {
            return;
        }

        const pageWrapper = document.querySelector('.layout-1 .wrapper');
        const documentScroller = document.scrollingElement || document.documentElement;
        const scrollContainer = pageWrapper && pageWrapper.scrollHeight > pageWrapper.clientHeight
            ? pageWrapper
            : documentScroller;
        const usesWindowScroll = scrollContainer === documentScroller;

        const getScrollTop = function () {
            return usesWindowScroll ? window.scrollY : scrollContainer.scrollTop;
        };

        const getScrollHeight = function () {
            return usesWindowScroll ? documentScroller.scrollHeight : scrollContainer.scrollHeight;
        };

        const getViewportHeight = function () {
            return usesWindowScroll ? window.innerHeight : scrollContainer.clientHeight;
        };

        const scrollPageTo = function (top) {
            if (usesWindowScroll) {
                window.scrollTo({ top: top, behavior: 'smooth' });
                return;
            }

            scrollContainer.scrollTo({ top: top, behavior: 'smooth' });
        };

        const refreshButtons = function () {
            const maxScroll = Math.max(0, getScrollHeight() - getViewportHeight());
            const currentScroll = getScrollTop();

            topButton.disabled = currentScroll <= 8;
            bottomButton.disabled = maxScroll <= 8 || currentScroll >= maxScroll - 8;
        };

        topButton.addEventListener('click', function () {
            scrollPageTo(0);
        });

        bottomButton.addEventListener('click', function () {
            scrollPageTo(getScrollHeight());
        });

        (usesWindowScroll ? window : scrollContainer).addEventListener('scroll', refreshButtons, { passive: true });
        window.addEventListener('resize', refreshButtons);
        window.addEventListener('load', refreshButtons);
        refreshButtons();
    })();
</script>
