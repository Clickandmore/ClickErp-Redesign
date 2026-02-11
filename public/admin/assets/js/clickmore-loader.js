(() => {
  const loader = document.getElementById("loading-wrapper");
  if (!loader) {
    return;
  }

  const SHOW_DELAY_MS = 180;
  const MIN_VISIBLE_MS = 180;
  const HIDE_TRANSITION_MS = 180;

  let showTimer = null;
  let shownAt = 0;
  let isVisible = false;

  const setVisible = () => {
    if (isVisible) {
      return;
    }
    isVisible = true;
    shownAt = Date.now();
    loader.style.display = "block";
    window.requestAnimationFrame(() => {
      loader.classList.add("is-active");
    });
  };

  const hide = () => {
    window.clearTimeout(showTimer);

    if (!isVisible) {
      loader.classList.remove("is-active");
      loader.style.display = "none";
      return;
    }

    const elapsed = Date.now() - shownAt;
    const wait = Math.max(0, MIN_VISIBLE_MS - elapsed);

    window.setTimeout(() => {
      isVisible = false;
      loader.classList.remove("is-active");
      window.setTimeout(() => {
        if (!isVisible) {
          loader.style.display = "none";
        }
      }, HIDE_TRANSITION_MS);
    }, wait);
  };

  const show = () => {
    window.clearTimeout(showTimer);
    setVisible();
  };

  const showDelayed = () => {
    window.clearTimeout(showTimer);
    showTimer = window.setTimeout(() => {
      setVisible();
    }, SHOW_DELAY_MS);
  };

  loader.style.display = "none";
  loader.classList.remove("is-active");

  window.cmShowLoader = show;
  window.cmShowLoaderDelayed = showDelayed;
  window.cmHideLoader = hide;

  if (document.readyState === "complete") {
    hide();
  } else {
    window.addEventListener("load", () => {
      hide();
    });
  }

  window.addEventListener("pageshow", () => {
    hide();
  });

  window.addEventListener("beforeunload", () => {
    show();
  });

  document.addEventListener("click", (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const link = event.target.closest("a[href]");
    if (!link) {
      return;
    }

    if (link.hasAttribute("data-no-loader") || link.getAttribute("target") === "_blank" || link.hasAttribute("download")) {
      return;
    }

    const href = (link.getAttribute("href") || "").trim();
    if (!href || href === "#" || href.startsWith("javascript:") || href.startsWith("mailto:") || href.startsWith("tel:")) {
      return;
    }

    let targetUrl;
    try {
      targetUrl = new URL(href, window.location.href);
    } catch (e) {
      return;
    }

    const currentUrl = new URL(window.location.href);
    const isSamePageAnchor =
      targetUrl.origin === currentUrl.origin &&
      targetUrl.pathname === currentUrl.pathname &&
      targetUrl.search === currentUrl.search &&
      targetUrl.hash;

    if (targetUrl.origin !== currentUrl.origin || isSamePageAnchor) {
      return;
    }

    showDelayed();
  });

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!form || !(form instanceof HTMLFormElement) || form.hasAttribute("data-no-loader")) {
      return;
    }
    showDelayed();
  });
})();
