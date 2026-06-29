(function () {
  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  function setMenu(open) {
    const toggle = qs("[data-menu-toggle]");
    const drawer = qs("[data-mobile-nav]");
    const backdrop = qs("[data-drawer-backdrop]");
    if (!toggle || !drawer) return;
    drawer.classList.toggle("is-open", open);
    document.body.classList.toggle("menu-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    drawer.setAttribute("aria-hidden", String(!open));
    if (backdrop) {
      if (open) {
        backdrop.hidden = false;
        requestAnimationFrame(() => backdrop.classList.add("is-visible"));
      } else {
        backdrop.classList.remove("is-visible");
        window.setTimeout(() => { backdrop.hidden = true; }, 320);
      }
    }
  }

  function closeMobileMenu() {
    setMenu(false);
  }

  function initNavigation() {
    const toggle = qs("[data-menu-toggle]");
    const drawer = qs("[data-mobile-nav]");
    if (!toggle || !drawer) return;

    toggle.addEventListener("click", () => setMenu(!drawer.classList.contains("is-open")));
    qs("[data-drawer-backdrop]")?.addEventListener("click", closeMobileMenu);
    qsa("a", drawer).forEach((link) => link.addEventListener("click", closeMobileMenu));
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && drawer.classList.contains("is-open")) closeMobileMenu();
    });

    qsa('a[href^="#"]').forEach((link) => {
      link.addEventListener("click", (event) => {
        const target = qs(link.getAttribute("href"));
        if (!target) return;
        event.preventDefault();
        closeMobileMenu();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  function initHeaderFade() {
    const header = qs("[data-header]");
    if (!header || !document.body.classList.contains("landing-home")) return;

    let ticking = false;
    const update = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 24);
      ticking = false;
    };
    const requestUpdate = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(update);
    };

    update();
    window.addEventListener("scroll", requestUpdate, { passive: true });
  }

  function initComparisonSliders() {
    qsa(".comparison-slider").forEach((slider) => {
      const input = qs('input[type="range"]', slider);
      if (!input) return;
      const update = () => slider.style.setProperty("--position", `${input.value}%`);
      input.addEventListener("input", update);
      update();
    });
  }

  function initRevealAnimations() {
    const items = qsa(".reveal");
    if (!items.length) return;

    if (!("IntersectionObserver" in window)) {
      items.forEach((item) => item.classList.add("is-visible"));
      return;
    }

    document.body.classList.add("animations-ready");
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -30px" }
    );

    items.forEach((item) => observer.observe(item));
  }

  function initYear() {
    qsa("[data-year]").forEach((year) => {
      year.textContent = new Date().getFullYear();
    });
  }

  initNavigation();
  initHeaderFade();
  initComparisonSliders();
  initRevealAnimations();
  initYear();
})();
