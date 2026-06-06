(function () {
  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  function closeMobileMenu() {
    document.body.classList.remove("menu-open");
    qs("[data-mobile-nav]")?.classList.remove("is-open");
    qs("[data-menu-toggle]")?.setAttribute("aria-expanded", "false");
    qs("[data-menu-toggle]")?.setAttribute("aria-label", "Open menu");
  }

  function initNavigation() {
    const toggle = qs("[data-menu-toggle]");
    const nav = qs("[data-mobile-nav]");
    if (!toggle || !nav) return;

    toggle.addEventListener("click", () => {
      const isOpen = nav.classList.toggle("is-open");
      document.body.classList.toggle("menu-open", isOpen);
      toggle.setAttribute("aria-expanded", String(isOpen));
      toggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
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
  initComparisonSliders();
  initRevealAnimations();
  initYear();
})();
