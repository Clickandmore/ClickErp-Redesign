(() => {
  const SCROLL_KEY = "cm_sidebar_scroll_top_v1";
  const OPEN_MENUS_KEY = "cm_sidebar_open_menus_v1";

  const sidebar = document.querySelector(".sidebar");
  if (!sidebar) {
    return;
  }

  const scrollCandidates = [sidebar.querySelector(".main-menu"), sidebar].filter(Boolean);
  const scrollContainer =
    scrollCandidates.find((el) => el.scrollHeight > el.clientHeight + 4) || scrollCandidates[0];
  let saveScroll = () => {};

  if (scrollContainer) {
    const storedTop = Number(sessionStorage.getItem(SCROLL_KEY));
    if (!Number.isNaN(storedTop) && storedTop > 0) {
      requestAnimationFrame(() => {
        scrollContainer.scrollTop = storedTop;
      });
    }

    saveScroll = () => {
      sessionStorage.setItem(SCROLL_KEY, String(scrollContainer.scrollTop || 0));
    };

    scrollContainer.addEventListener("scroll", saveScroll, { passive: true });
    window.addEventListener("beforeunload", saveScroll);

    document.addEventListener("click", (event) => {
      const link = event.target.closest(".sidebar a[href]");
      if (!link) {
        return;
      }
      const href = link.getAttribute("href");
      if (href && href !== "#") {
        saveScroll();
      }
    });
  }

  let openedMenus = [];
  try {
    openedMenus = JSON.parse(sessionStorage.getItem(OPEN_MENUS_KEY) || "[]");
  } catch (e) {
    openedMenus = [];
  }
  const openedMenusSet = new Set(openedMenus);

  const saveOpenedMenus = () => {
    sessionStorage.setItem(OPEN_MENUS_KEY, JSON.stringify(Array.from(openedMenusSet)));
  };

  const toggles = document.querySelectorAll(
    ".sidebar .m-link[data-bs-toggle='collapse'][data-bs-target]"
  );

  sidebar.querySelectorAll(".menu-list > li > .m-link").forEach((link) => {
    const label = link.querySelector("span:not(.arrow)")?.textContent?.trim();
    if (label) {
      link.setAttribute("title", label);
      link.setAttribute("aria-label", label);
    }
  });

  document.addEventListener("click", (event) => {
    if (!sidebar.classList.contains("sidebar-mini")) {
      sidebar.querySelectorAll(".cm-mini-open").forEach((item) => {
        item.classList.remove("cm-mini-open");
      });
      return;
    }

    const toggle = event.target.closest(
      ".sidebar .menu-list > li > .m-link[data-bs-toggle='collapse'][data-bs-target]"
    );

    if (toggle) {
      const parentItem = toggle.closest("li");
      const isOpen = parentItem?.classList.contains("cm-mini-open");

      sidebar.querySelectorAll(".cm-mini-open").forEach((item) => {
        if (item !== parentItem) {
          item.classList.remove("cm-mini-open");
        }
      });

      if (parentItem) {
        parentItem.classList.toggle("cm-mini-open", !isOpen);
      }

      event.preventDefault();
      event.stopPropagation();
      return;
    }

    if (!event.target.closest(".sidebar")) {
      sidebar.querySelectorAll(".cm-mini-open").forEach((item) => {
        item.classList.remove("cm-mini-open");
      });
    }
  }, true);

  toggles.forEach((toggle) => {
    const targetSelector = toggle.getAttribute("data-bs-target");
    if (!targetSelector || !targetSelector.startsWith("#")) {
      return;
    }

    const target = document.querySelector(targetSelector);
    if (!target) {
      return;
    }

    const menuId = target.id;
    const parentItem = toggle.closest("li");
    const hasActiveChild = !!target.querySelector(".m-link.active");

    if (!hasActiveChild) {
      const shouldOpen = openedMenusSet.has(menuId);
      if (shouldOpen) {
        target.classList.add("show");
        toggle.setAttribute("aria-expanded", "true");
        parentItem && parentItem.classList.remove("collapsed");
      } else {
        target.classList.remove("show");
        toggle.setAttribute("aria-expanded", "false");
        parentItem && parentItem.classList.add("collapsed");
      }
    } else {
      target.classList.add("show");
      toggle.setAttribute("aria-expanded", "true");
      parentItem && parentItem.classList.remove("collapsed");
      openedMenusSet.add(menuId);
      saveOpenedMenus();
    }

    target.addEventListener("shown.bs.collapse", () => {
      openedMenusSet.add(menuId);
      saveOpenedMenus();
      parentItem && parentItem.classList.remove("collapsed");
      toggle.setAttribute("aria-expanded", "true");
    });

    target.addEventListener("hidden.bs.collapse", () => {
      openedMenusSet.delete(menuId);
      saveOpenedMenus();
      parentItem && parentItem.classList.add("collapsed");
      toggle.setAttribute("aria-expanded", "false");
    });
  });

  if (scrollContainer) {
    requestAnimationFrame(() => {
      const activeLink =
        sidebar.querySelector(".sub-menu .m-link.active") ||
        sidebar.querySelector(".menu-list > li > .m-link.active") ||
        sidebar.querySelector(".m-link.active");

      if (!activeLink) {
        return;
      }

      const containerRect = scrollContainer.getBoundingClientRect();
      const linkRect = activeLink.getBoundingClientRect();
      const needsScroll =
        linkRect.top < containerRect.top + 24 || linkRect.bottom > containerRect.bottom - 24;

      if (needsScroll) {
        activeLink.scrollIntoView({ block: "center", inline: "nearest" });
        saveScroll();
      }
    });
  }
})();
